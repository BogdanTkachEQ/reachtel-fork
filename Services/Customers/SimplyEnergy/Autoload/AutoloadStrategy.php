<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\SimplyEnergy\Autoload;

use DateTime;
use Exception;
use Services\Autoload\AbstractLineItemProcessorStrategy;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\Campaign\Interfaces\CampaignCreatorFactoryInterface;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;
use Services\Hooks\Exceptions\HookDataException;
use Services\Hooks\Exceptions\TargetCreationException;

/**
 * Class AutoloadStrategy
 */
class AutoloadStrategy extends AbstractLineItemProcessorStrategy
{
    const PREFER_TIME_COLUMN_NAME = 'PREFER TIME';
    const DESTINATION_COLUMN_NAME = 'C1_PHONE1';
    const PACING_QUOTIENT = 4;
    const TOTAL_CASCADING_ATTEMPTS = 3;

    /**
     * @var array
     */
    private $campaignIds = [];

    /**
     * @var array
     */
    private $errorCampaigns = [];

    /**
     * @var DateTime
     */
    private $runDateTime;
    /**
     * @var CampaignCreatorFactoryInterface
     */
    private $campaignCreatorFactory;

    /**
     * AutoloadStrategy constructor.
     * @param AutoloadFileProcessorInterface $fileProcessor
     * @param DateTime|null $runDateTime
     * @param CampaignCreatorFactoryInterface $campaignCreatorFactory
     */
    public function __construct(
        AutoloadFileProcessorInterface $fileProcessor,
        DateTime $runDateTime = null,
        CampaignCreatorFactoryInterface $campaignCreatorFactory
    ) {
        $this->runDateTime = $runDateTime ?: new DateTime();
        parent::__construct($fileProcessor);
        $this->campaignCreatorFactory = $campaignCreatorFactory;
    }

    /**
     * @return boolean
     */
    protected function preProcessHook()
    {
        return true;
    }

    /**
     * @return boolean
     */
    protected function postProcessHook()
    {
        $this->addToLogs('Starting to dedupe and activate campaigns.');
        foreach ($this->campaignIds as $campaignId) {
            api_targets_dedupe($campaignId);

            // Set pacing
            $targets = api_data_target_status($campaignId);
            $sendRate = ceil(($targets['TOTAL'] * self::TOTAL_CASCADING_ATTEMPTS) / self::PACING_QUOTIENT);
            $this->addToLogs(sprintf('Setting pacing for campaignid %d to %d', $campaignId, $sendRate));
            api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_SEND_RATE, $sendRate);
            api_campaigns_setting_set(
                $campaignId,
                CAMPAIGN_SETTING_STATUS,
                CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE
            );
        }

        $this->addToLogs('Deduping and activating campaigns completed.');

        return true;
    }

    /**
     * Mandatory headers on the csv
     * @return array
     */
    protected function getRequiredColumns()
    {
        return [self::PREFER_TIME_COLUMN_NAME, self::DESTINATION_COLUMN_NAME];
    }

    /**
     * @param array $line
     * @return boolean
     * @throws Exception
     */
    protected function processLine(array $line)
    {
        $preferTime = $line[self::PREFER_TIME_COLUMN_NAME];
        switch ($preferTime) {
            case '8AM - 12PM':
                $templateCampaignName = 'SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-9-12-Contact1';
                break;

            case '12PM - 3PM':
                $templateCampaignName = 'SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-12-15-Contact1';
                break;

            case '3PM - 7PM':
                $templateCampaignName = 'SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-15-19-Contact1';
                break;

            default:
                $templateCampaignName = 'SimplyEnergy-YYYYMMDD-EarlyCollectionsIVR-9-19-Contact1';
                break;
        }

        $campaignId = $this->buildCampaignFromTemplate($templateCampaignName);

        if (is_null($campaignId)) {
            return false;
        }

        return api_targets_add_single(
            $campaignId,
            $line[self::DESTINATION_COLUMN_NAME],
            null,
            null,
            $line
        );
    }

    /**
     * @param $templateCampaignName
     * @return mixed
     * @throws CampaignValidationException
     * @throws CampaignCreationException
     * @throws HookDataException
     * @throws TargetCreationException
     */
    private function buildCampaignFromTemplate($templateCampaignName)
    {
        if (!isset($this->campaignIds[$templateCampaignName]) &&
            !in_array($templateCampaignName, $this->errorCampaigns)) {
            $templateCampaignId = api_campaigns_checknameexists($templateCampaignName);

            if (!$templateCampaignId) {
                $this->addToLogs(
                    "Unable to create campaign from template {$templateCampaignName}. No template found, failing..."
                );
                throw new CampaignCreationException(
                    "Unable to create campaign from template {$templateCampaignName}. No template found, failing..."
                );
            }

            $desiredCampaignName = str_replace("YYYYMMDD", $this->runDateTime->format('Ymd'), $templateCampaignName);

            $id = api_campaigns_checknameexists($desiredCampaignName);
            if ($id) {
                $this->campaignIds[$desiredCampaignName] = $id;
            } else {
                $campaignCreator = $this->campaignCreatorFactory->makeCreator(
                    $templateCampaignId,
                    $desiredCampaignName
                );
                $id = $campaignCreator->setupNextCampaign(false);
                if (!$id) {
                    $this->errorCampaigns[] = $templateCampaignName;
                    $this->campaignIds[$templateCampaignName] = null;
                } else {
                    $this->campaignIds[$templateCampaignName] = $id;
                }
            }
        }
        return $this->campaignIds[$templateCampaignName];
    }
}
