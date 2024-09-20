<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Models\Autoload\AutoloadDTO;
use Services\Autoload\Command\GenericLineProcessorCommand;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Campaign\Limits\SendRate\SendRateCalc;
use Services\Campaign\Name\Interfaces\CampaignNameInterface;

/**
 * Class GenericFileAutoloadStrategy
 *
 * Given a file containing one line per destination,
 * create a campaign per file based on the TokenDateTemplateCampaignName name
 *
 */
class GenericFileAutoloadStrategy extends AbstractLineItemProcessorStrategy
{
    /** @var boolean */
    protected $dedupe = false;

    /** @var integer */
    protected $campaignId;

    /**
     * @var CampaignNameInterface
     */
    protected $campaignNamer;
    /**
     * @var DateTimeZone
     */
    protected $timeZone;
    /**
     * @var CampaignCreatorInterface
     */
    protected $campaignCreator;

    /**
     * @var SendRateCalc
     */
    protected $sendRateCalculator;

    /** @var AutoloadDTO */
    protected $autoloadDto;

    /** @var GenericLineProcessorCommand */
    protected $lineItemProcessorCommand;

    /** @var boolean */
    protected $activateCampaign = true;

    public function __construct(
        CampaignNameInterface $campaignNamer,
        AutoloadFileProcessorInterface $fileProcessor,
        CampaignCreatorInterface $campaignCreator,
        AutoloadDTO $autoloadDTO,
        SendRateCalc $sendRateCalculator,
        DateTimeZone $timeZone = null,
        GenericLineProcessorCommand $command
    ) {
        parent::__construct($fileProcessor);
        if ($timeZone === null) {
            $timeZone = new DateTimeZone('Australia/Sydney');
        }
        $this->timeZone = $timeZone;
        $this->campaignCreator = $campaignCreator;
        $this->campaignNamer = $campaignNamer;
        $this->sendRateCalculator = $sendRateCalculator;
        $this->autoloadDto = $autoloadDTO;

        if (!$this->autoloadDto->getDestinationColumnName()) {
            $this->autoloadDto->setDestinationColumnName('Destination');
        }

        $this->lineItemProcessorCommand = $command;
    }

    /**
     * @return bool
     */
    public function mustDedupe()
    {
        return $this->dedupe;
    }

    /**
     * @param bool $dedupe
     */
    public function setDedupe($dedupe)
    {
        $this->dedupe = $dedupe;
    }

    /**
     * @param boolean $activateCampaign
     * @return $this
     */
    public function setActivateCampaign($activateCampaign)
    {
        $this->activateCampaign = $activateCampaign;
        return $this;
    }

    /**
     * @return boolean
     */
    public function shouldActivateCampaign()
    {
        return $this->activateCampaign;
    }

    /**
     * @param array $line
     * @return boolean
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function processLine(array $line)
    {
        return $this->lineItemProcessorCommand->execute($line);
    }

    /**
     * @return boolean
     */
    protected function preProcessHook()
    {
        $campaignName = $this->campaignNamer->getName();
        $this->addToLogs("Trying to create: {$campaignName}");

        $previousCampaigns = api_campaigns_list_all(
            true,
            null,
            null,
            ["search" => $this->campaignNamer->getSearchableName()]
        );

        $previousCampaignId = key($previousCampaigns);
        if (!$previousCampaignId) {
            $this->addToLogs("Could not find a previous campaign for {$this->campaignNamer->getSearchableName()}");
            return false;
        }

        $this->campaignId = $this->campaignCreator->create($campaignName, $previousCampaignId);

        if ($this->campaignId) {
            $this->addToLogs("Created campaign {$this->campaignId}");

            // If we don't have a target key column set, try get one from the campaign
            if (!$this->autoloadDto->getTargetKeyColumnName()) {
                $defaultTargetKey = api_campaigns_setting_getsingle($this->campaignId, "defaulttargetkey");
                if ($defaultTargetKey) {
                    $this->autoloadDto->setTargetKeyColumnName($defaultTargetKey);
                }
            }

            $this->lineItemProcessorCommand->setCampaignId($this->campaignId);

            return true;
        }
        $this->addToLogs("Could not create {$campaignName}");
        return false;
    }

    /**
     * @return boolean
     */
    protected function postProcessHook()
    {
        //Dedupe
        $this->dedupeCampaign();

        //set send rate
        $this->setCampaignSendRate();

        //Activating Campaign
        $this->activateCampaign();

        return true;
    }

    /**
     * Sets the send rate based on the number of targets and modified by sendRateHourBuffer
     *
     * @return float|integer
     */
    protected function setCampaignSendRate()
    {
        $this->addToLogs('Setting pacing for campaign ' . $this->campaignId);
        $this->sendRateCalculator->setCampaignId($this->campaignId);
        $sendRate = $this->sendRateCalculator->calculateRate($this->autoloadDto->getSendRateHourBuffer());
        api_campaigns_setting_set($this->campaignId, "sendrate", $sendRate);
        $this->addToLogs(sprintf('Send rate for campaign %d set to %d', $this->campaignId, $sendRate));
        return $sendRate;
    }

    /**
     * @return boolean
     */
    protected function dedupeCampaign()
    {
        if ($this->mustDedupe()) {
            $this->addToLogs('Deduping campaign id: ' . $this->campaignId);
            api_targets_dedupe($this->campaignId);
            return $this->addToLogs('Campaign id: ' . $this->campaignId . ' Deduplicated');
        }

        return true;
    }

    /**
     * @return boolean
     */
    protected function activateCampaign()
    {
        if (!$this->shouldActivateCampaign()) {
            return true;
        }

        if (!api_campaigns_setting_set(
            $this->campaignId,
            CAMPAIGN_SETTING_STATUS,
            CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE
        )) {
            return $this->addToLogs('Failed to activate campaign ' . $this->campaignId);
        }

        return $this->addToLogs('Activated campaign ' . $this->campaignId);
    }

    /**
     * Mandatory headers on the csv
     * @return array
     */
    protected function getRequiredColumns()
    {
        if ($this->autoloadDto->getCallDateColumnName()) {
            return [
                $this->autoloadDto->getDestinationColumnName(),
                $this->autoloadDto->getCallDateColumnName()
            ];
        }
        return [$this->autoloadDto->getDestinationColumnName()];
    }

    public function setLogger(AutoloadLogger $logger)
    {
        parent::setLogger($logger);
        $this->lineItemProcessorCommand->setLogger($logger);
    }
}
