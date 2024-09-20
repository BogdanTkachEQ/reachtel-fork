<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading\Targets;

use Services\Hooks\Exceptions\TargetCreationException;

/**
 * Class CascadingCampaignTargetCopier
 * @package Services\Campaign\Hooks\Cascading
 *
 * Class copies targets for cascading campaigns, if a target has no entry in the defaultdestinationfield attempt to
 * retrived its target from the given base campaign's target table by target key.
 *
 * (target keys are the same between cascading campaigns)
 *
 *
 */
class CascadingSingleDestinationCampaignTargetCopier
{

    const DEFAULT_DESTINATION_FIELD = "defaultdestination1";

    private $campaignIteration;
    private $sourceCampaignId;
    /**
     * @var array|false
     */
    private $sourceCampaignSettings;
    private $baseCampaignId;

    /**
     * CascadingSingleDestinationCampaignTargetCopier constructor.
     * @param $campaignIteration
     * @param $sourceCampaignId
     * @param $baseCampaignId
     */
    public function __construct($campaignIteration, $sourceCampaignId, $baseCampaignId)
    {
        $this->campaignIteration = $campaignIteration;
        $this->sourceCampaignId = $sourceCampaignId;
        $this->sourceCampaignSettings = api_campaigns_setting_getall($sourceCampaignId);
        $this->baseCampaignId = $baseCampaignId;
    }

    /**
     * @param $targetId
     * @param $destinationCampaignId
     * @param $nextCallDelayHours
     * @return bool
     * @throws TargetCreationException
     * @throws \Exception
     */
    public function copy($targetId, $destinationCampaignId, $nextCallDelayHours)
    {
        if (!isset($this->destinationCampaignSettings)) {
            $this->destinationCampaignSettings = api_campaigns_setting_getall($destinationCampaignId);
        }

        // Get unsuccessful calls
        $targetData = api_targets_getinfo($targetId);
        if (empty($targetData) || ($targetData['status'] !== 'ABANDONED')) {
            throw new TargetCreationException("The given target id is not valid for a cascading campaign");
        }

        // Add to next campaign
        $mergeData = api_data_merge_get_all($this->sourceCampaignId, $targetData['targetkey']);

        // Get the next destination, and replace next_destination with the following
        if (!is_array($mergeData)) {
            throw new TargetCreationException(
                sprintf(
                    'No merge data found for %s - %s',
                    $this->sourceCampaignId,
                    $targetData['targetkey']
                )
            );
        }

        $callResults = api_data_callresult_get_all_bytargetid($targetId);
        $generatedTime = isset($callResults['GENERATED']) ? $callResults['GENERATED'] : 'now';

        $nextDestination = $this->determinateDestination($targetData, $mergeData);

        if (!$nextDestination) {
            throw new TargetCreationException("Could not determine the next destination for target id {$targetId}");
        }

        // Add next call attempt time merge data
        // NB: This is for debugging in reports only, the 6th param below sets nextattempt which actually does the work
        $mergeData['next-call-attempt-aest'] = date(
            'Y-m-d H:i:00',
            strtotime("$generatedTime + $nextCallDelayHours hours")
        );

        return api_targets_add_single(
            $destinationCampaignId,
            $nextDestination,
            $targetData['targetkey'],
            $targetData['priority'],
            $mergeData,
            $mergeData['next-call-attempt-aest']
        );
    }

    /**
     * Determine the destination for the given targetid
     * Look first in the merge data for the field value defined in the
     * defaultdestination1 field in campaign settings.
     * If it's not found try and find the current target in the base campaign by its target key
     *
     * @param $targetData
     * @param $mergeData
     * @return bool|mixed
     */
    protected function determinateDestination($targetData, $mergeData)
    {
        // defaultdestination1=C1_PHONE_1, etc, etc
        $mergeDataDestinationField = $this->getDefaultDestinationFieldValue();
        $destination = $this->getDestinationFromMergeData($mergeDataDestinationField, $mergeData);
        if (!$destination) {
            $destination = $this->getFallbackDestination($targetData);
        }

        return $destination;
    }

    /**
     * @param $destinationField
     * @param $mergeData
     * @return bool|mixed
     */
    protected function getDestinationFromMergeData($destinationField, $mergeData)
    {
        return isset($mergeData[$destinationField]) ? $mergeData[$destinationField] : false;
    }

    /**
     * The defaultdestiniation1 field sets the merge_data field to look into for the destination address
     *
     * @return bool
     */
    protected function getDefaultDestinationFieldValue()
    {
        $settingDestinationField = self::DEFAULT_DESTINATION_FIELD;
        if (isset($this->destinationCampaignSettings[$settingDestinationField])) {
            return $this->destinationCampaignSettings[$settingDestinationField];
        }
        return false;
    }

    /**
     * Try and find the target's real destination in the base (initial) campaign and use that if there is nothing in
     * merge data.
     *
     * @param $targetData
     * @return bool|mixed
     */
    protected function getFallbackDestination($targetData)
    {
        $target = api_targets_get_target_by_campaign_target_key($this->baseCampaignId, $targetData['targetkey']);
        if (!empty($target)) {
            return $target['destination'];
        }
        return false;
    }
}
