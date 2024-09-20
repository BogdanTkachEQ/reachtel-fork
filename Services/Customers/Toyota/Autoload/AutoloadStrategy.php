<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Autoload;

use Services\Autoload\AbstractLineItemProcessorStrategy;
use Services\Autoload\Command\Customers\Toyota\LineProcessorCommand;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Customers\Toyota\Autoload\Traits\CampaignFinder;

/**
 * TECHNICAL DEBT: Replace this with GenericBrandBasedAutoloadStrategy https://jira/browse/CBS-1500
 * Class AutoloadStrategy
 */
class AutoloadStrategy extends AbstractLineItemProcessorStrategy
{
    use CampaignFinder;

    const DESTINATION_COLUMN_NAME = 'vchPhoneNumber';
    const ARREAR_DAYS_COLUMN_NAME = 'iArrearsDays';
    const STATE_COLUMN_NAME = 'vchState';
    const BRAND_COLUMN_NAME = 'vchFinancier';

    /**
     * @var array
     */
    protected $campaignsToDedupe = [];

    /**
     * @var array
     */
    private $brandCampaignMap;

    /**
     * @var integer
     */
    protected $sendRateQuotient = 4;

    /**
     * @var \DateTimeZone
     */
    private $timeZone;

    /** @var CampaignCreatorInterface */
    private $campaignCreator;

    /** @var LineProcessorCommand */
    private $lineProcessor;

    /**
     * AbstractAutoloadStrategy constructor.
     * @param AutoloadFileProcessorInterface $fileProcessor
     * @param array                          $brandCampaignMap
     * @param \DateTimeZone                  $timeZone
     * @param CampaignCreatorInterface       $campaignCreator
     * @param LineProcessorCommand           $lineProcessorCommand
     */
    public function __construct(
        AutoloadFileProcessorInterface $fileProcessor,
        array $brandCampaignMap,
        \DateTimeZone $timeZone = null,
        CampaignCreatorInterface $campaignCreator,
        LineProcessorCommand $lineProcessorCommand
    ) {
        $this->brandCampaignMap = $brandCampaignMap;
        if (is_null($timeZone)) {
            new \DateTimeZone('Australia/Sydney');
        }

        $this->timeZone = $timeZone;
        $this->campaignCreator = $campaignCreator;
        $this->lineProcessor = $lineProcessorCommand;
        parent::__construct($fileProcessor);
    }

    /**
     * @param integer $quotient
     * @throws \Exception
     */
    public function setSendRateQuotient($quotient)
    {
        if ($quotient <= 0) {
            throw new \Exception('Send rate quotient should be greater than 0');
        }
        $this->sendRateQuotient = $quotient;
    }

    /**
     * @param array $line
     * @return boolean
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function processLine(array $line)
    {
        $brand = $line[static::BRAND_COLUMN_NAME];
        $campaignId = $this->findCampaign($brand);

        $this->campaignsToDedupe[] = $campaignId;

        $nextAttempt = $this->getNextAttemptFromLine($line);

        return $this
            ->lineProcessor
            ->setLogger($this->logger)
            ->setCampaignId($campaignId)
            ->setNextAttempt($nextAttempt)
            ->execute($line);
    }

    /**
     * @param array $line
     * @return false|int
     */
    protected function getNextAttemptFromLine(array $line)
    {
        if (!isset($line[static::STATE_COLUMN_NAME]) || $line[static::STATE_COLUMN_NAME] !== 'WA') {
            return '09:30';
        }

        return '13:00';
    }

    /**
     * @return boolean
     */
    protected function preProcessHook()
    {
        //Can use this to start db transaction if future if required.
        return true;
    }

    /**
     * @return boolean
     */
    protected function postProcessHook()
    {
        foreach (array_unique($this->campaignsToDedupe) as $campaignId) {
            $this->addToLogs('Deduping campaign id: ' . $campaignId);
            api_targets_dedupe($campaignId);
            $this->addToLogs('Campaign id: ' . $campaignId . ' Deduplicated');

            //set send rate
            $this->addToLogs('Setting pacing for campaign ' . $campaignId);
            $targets = api_data_target_status($campaignId);
            $sendRate = ceil($targets["TOTAL"] / $this->sendRateQuotient) + 1;
            api_campaigns_setting_set($campaignId, "sendrate", $sendRate);
            $this->addToLogs(sprintf('Send rate for campaign %d set to %d', $campaignId, $sendRate));

            //Activating Campaign
            if (!api_campaigns_setting_set(
                $campaignId,
                CAMPAIGN_SETTING_STATUS,
                CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE
            )) {
                $this->addToLogs('Failed to activate campaign ' . $campaignId);
                return true;
            }

            $this->addToLogs('Activated campaign ' . $campaignId);
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getBrandCampaignMap()
    {
        return $this->brandCampaignMap;
    }

    protected function getRequiredColumns()
    {
        return [static::BRAND_COLUMN_NAME];
    }

    /**
     * @return CampaignCreatorInterface
     */
    protected function getCampaignCreator()
    {
        return $this->campaignCreator;
    }
}
