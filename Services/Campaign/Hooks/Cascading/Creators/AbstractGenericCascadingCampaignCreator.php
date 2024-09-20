<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading\Creators;

use Services\Campaign\Cloner\Interfaces\CampaignClonerInterface;
use Services\Campaign\Hooks\Cascading\Interfaces\CascadingCampaignCreatorInterface;
use Services\Campaign\Hooks\Cascading\Targets\CascadingSingleDestinationCampaignTargetCopier;
use Services\Exceptions\CampaignValidationException;
use Services\Hooks\Exceptions\TargetCreationException;

/**
 * Class BasicCascadingCampaign
 */
abstract class AbstractGenericCascadingCampaignCreator implements CascadingCampaignCreatorInterface
{

    const CASCADING_NAME_SUFFIX = "step-";

    public static $ALLOWED_TARGET_STATUSES = ["ABANDONED"];
    public static $MAX_ITERATIONS = 10;

    /**
     * @var
     */
    protected $currentCampaignId;
    /**
     * @var CampaignClonerInterface
     */
    protected $campaignCloner;

    /**
     * BasicCascadingCampaignCreator constructor.
     * @param int $currentCampaignId
     * @param CampaignClonerInterface $campaignCloner
     */
    public function __construct($currentCampaignId, CampaignClonerInterface $campaignCloner)
    {
        $this->currentCampaignId = $currentCampaignId;
        $this->campaignCloner = $campaignCloner;
    }

    /**
     * @return mixed
     */
    abstract protected function cloneCampaign();

    /**
     * @param bool $makeActive
     * @return mixed
     * @throws CampaignValidationException Too many cascades
     * @throws TargetCreationException Target failed to copy.
     */
    public function setupNextCampaign($makeActive = false)
    {
        if ($this->overMaxIterations()) {
            $message = "Too many cascades in this cascade series, maximum number allowed is ".self::$MAX_ITERATIONS ;
            $this->log($message);
            throw new CampaignValidationException($message) ;
        }

        $this->log("Cloning campaign");
        $newCampaignId = $this->cloneCampaign();

        $delay = api_campaigns_setting_getsingle($this->currentCampaignId, CAMPAIGN_SETTING_CASCADING_DELAY);
        $nextCallDelayHours = $delay ? $delay : 0;

        $targetCount = $this->copyTargets($newCampaignId, $nextCallDelayHours);

        // Update send rate based on tag and number of campaigns
        $rateMod = api_campaigns_setting_getsingle($this->currentCampaignId, CAMPAIGN_SETTING_CASCADING_RATE_MODIFIER);
        if ($targetCount > 0 && $rateMod > 0) {
            api_campaigns_setting_set($newCampaignId, 'sendrate', (int)ceil($targetCount / $rateMod));
        }

        // Set campaign active if requested
        if ($makeActive) {
            api_campaigns_setting_set($newCampaignId, CAMPAIGN_SETTING_STATUS, CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE);
            $this->log("Activating campaign");
        }
        $this->log("Campaign created: {$newCampaignId}");
        return $newCampaignId;
    }

    /**
     * @param int $newCampaignId
     * @param int $callDelayHours
     * @return bool|int
     * @throws TargetCreationException Target failed to copy.
     */
    protected function copyTargets($newCampaignId, $callDelayHours)
    {
        // Retrieve the valid targets from the previous campaign
        $targets = api_targets_listall($this->currentCampaignId, static::$ALLOWED_TARGET_STATUSES);
        $potentialTargets = count($targets);
        $this->log("Copying " . $potentialTargets . " targets...");
        if (!is_array($targets)) {
            $this->log("No targets to copy");
            return false;
        }
        $targetCount = 0;
        $copier = new CascadingSingleDestinationCampaignTargetCopier(
            $this->getNextCampaignIteration(),
            $this->currentCampaignId,
            $this->getFirstCampaign()
        );
        foreach ($targets as $targetId => $destination) {
            $newTargetId = $copier->copy($targetId, $newCampaignId, $callDelayHours);
            $this->log("Copied target id {$targetId} to {$newTargetId}");
            $targetCount++;
        }
        $this->log("Copied {$targetCount} / " . count($targets) . " targets");
        return $targetCount;
    }

    /**
     *
     * Returns the first campaign name in the series
     *
     * @return mixed
     */
    public function getFirstCampaignName()
    {
        return api_campaigns_setting_getsingle($this->getFirstCampaign(), CAMPAIGN_SETTING_NAME);
    }

    /**
     * Walks up the cascade tree using CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID of each previous campaign
     * Returns the first campaign id in the cascading series (the base campaign - step-1)
     *
     * @return false|int
     */
    public function getFirstCampaign()
    {
        if ($this->getCurrentCampaignIteration() === 1) {
            return $this->currentCampaignId;
        }
        $parentCampaignId = $this->currentCampaignId;
        for ($i = $this->getCurrentCampaignIteration(); $i > 1; $i--) {
            $parentCampaignId = api_campaigns_setting_getsingle(
                $parentCampaignId,
                CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID
            );
        }
        return $parentCampaignId;
    }

    /**
     * @param $value
     */
    protected function log($value)
    {
        api_misc_audit('CASCADE_CAMPAIGN_CREATION_' . $this->currentCampaignId, $value);
    }

    /**
     * Check if we exceed the maximum number of cascade iterations
     * @return bool
     */
    protected function overMaxIterations()
    {
        $iteration = api_campaigns_setting_getsingle($this->currentCampaignId, CAMPAIGN_SETTING_CASCADING_ITERATION);
        return  $iteration > static::$MAX_ITERATIONS;
    }
}
