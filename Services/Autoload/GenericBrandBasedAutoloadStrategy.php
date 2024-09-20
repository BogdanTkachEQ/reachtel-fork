<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Autoload\BrandBasedAutoloadDTO;
use Services\Autoload\Command\GenericLineProcessorCommand;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Campaign\Limits\SendRate\SendRateCalc;
use Services\Campaign\Name\CompositeCampaignNameCollection;
use Services\Exceptions\Campaign\CampaignNameRuntimeException;

/**
 * Class GenericBrandBasedAutoloadStrategy
 */
class GenericBrandBasedAutoloadStrategy extends GenericFileAutoloadStrategy
{
    /** @var ArrayCollection */
    private $campaignsSuccessful;

    /** @var ArrayCollection */
    private $brandCampaignIdMap = [];

    /** @var ArrayCollection */
    private $failedBrands;

    /** @var BrandBasedAutoloadDTO */
    protected $autoloadDto;

    /** @var CompositeCampaignNameCollection */
    protected $campaignNamer;

    /**
     * GenericBrandBasedAutoloadStrategy constructor.
     * @param CompositeCampaignNameCollection $campaignName
     * @param AutoloadFileProcessorInterface  $fileProcessor
     * @param CampaignCreatorInterface        $campaignCreator
     * @param BrandBasedAutoloadDTO           $autoloadDTO
     * @param SendRateCalc                    $sendRateCalc
     * @param \DateTimeZone                   $timeZone
     * @param GenericLineProcessorCommand     $lineProcessorCommand
     */
    public function __construct(
        CompositeCampaignNameCollection $campaignName,
        AutoloadFileProcessorInterface $fileProcessor,
        CampaignCreatorInterface $campaignCreator,
        BrandBasedAutoloadDTO $autoloadDTO,
        SendRateCalc $sendRateCalc,
        \DateTimeZone $timeZone,
        GenericLineProcessorCommand $lineProcessorCommand
    ) {
        parent::__construct(
            $campaignName,
            $fileProcessor,
            $campaignCreator,
            $autoloadDTO,
            $sendRateCalc,
            $timeZone,
            $lineProcessorCommand
        );

        $this->brandCampaignIdMap = new ArrayCollection();
        $this->failedBrands = new ArrayCollection();
        $this->campaignsSuccessful = new ArrayCollection();
    }

    /**
     * @return boolean
     */
    protected function preProcessHook()
    {
        if (!$this->autoloadDto->getBrandColumnName()) {
            $this->addToLogs('Brand column name is mandatory for the file to be processed');
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     */
    protected function postProcessHook()
    {
        foreach ($this->campaignsSuccessful->toArray() as $id) {
            $this->campaignId = $id;

            parent::postProcessHook();
        }

        return true;
    }

    /**
     * Mandatory headers on the csv
     * @return array
     */
    protected function getRequiredColumns()
    {
        $required = parent::getRequiredColumns();
        $required[] = $this->autoloadDto->getBrandColumnName();
        return $required;
    }

    /**
     * @param array $line
     * @return boolean
     * @throws \Exception
     */
    protected function processLine(array $line)
    {
        $brand = $line[$this->autoloadDto->getBrandColumnName()];
        $this->campaignId = $this->getCampaignId($brand);
        $this->lineItemProcessorCommand->setCampaignId($this->campaignId);

        if (!$this->campaignsSuccessful->contains($this->campaignId)) {
            $this->campaignsSuccessful->add($this->campaignId);
        }

        return parent::processLine($line);
    }

    /**
     * @param string $brand
     * @return integer null
     * @throws \Services\Exceptions\CampaignValidationException
     * @throws \Services\Exceptions\Campaign\CampaignCreationException
     */
    protected function getCampaignId($brand)
    {
        if ($this->failedBrands->contains($brand)) {
            throw new \InvalidArgumentException('Could not find or create campaign for brand: ' . $brand);
        }

        try {
            $this->campaignNamer->setItem($brand);
        } catch (CampaignNameRuntimeException $e) {
            throw new \InvalidArgumentException('Invalid brand name specified: ' . $brand);
        }

        if (!$this->brandCampaignIdMap->containsKey($brand)) {
            $previousCampaign = api_campaigns_list_all(
                null,
                null,
                1,
                ["search" => $this->campaignNamer->getSearchableName()]
            );

            if (!$previousCampaign) {
                $this->failedBrands->add($brand);
                throw new \InvalidArgumentException('Failed to find previous campaign for brand: ' . $brand);
            }

            $campaignId = $this
                ->campaignCreator
                ->create(
                    $this->campaignNamer->getName(),
                    $previousCampaign[0]
                );

            $this->brandCampaignIdMap->set($brand, $campaignId);
        }

        return $this->brandCampaignIdMap->get($brand);
    }
}
