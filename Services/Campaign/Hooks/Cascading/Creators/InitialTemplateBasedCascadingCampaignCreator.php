<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading\Creators;

use Services\Campaign\Cloner\Interfaces\CampaignClonerInterface;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;

/**
 * Class BasicCascadingCampaign
 *
 * Builds the initial campaign for a template based cascading campaign
 *
 * Cascading campaigns will be named $initialCampaignName-call-X where is X the current iteration
 * E.g InitialCampaignName = SimplySMS-20190201 and there are three campaign templates
 * will ultimately result in the campaigns:
 *
 * SimplySMS-20190201-call-1 <-- This class created this campaign based on $initialTemplateCampaignId
 * SimplySMS-20190201-call-2 <-- TemplateBasedCascadingCampaignCreator creates all subsequent campaigns
 * SimplySMS-20190201-call-3 <-- TemplateBasedCascadingCampaignCreator creates all subsequent campaigns
 *
 * There's a CascadingCampaignFactory which will return the correct class, use that.
 *
 */
class InitialTemplateBasedCascadingCampaignCreator extends TemplateBasedCascadingCampaignCreator
{
    //The suffix to append to the first campaign in a cascading series
    const INITIAL_CASCADING_NAME_SUFFIX = self::CASCADING_NAME_SUFFIX . "1";

    /**
     * @var null
     */
    private $initialCampaignName;

    /**
     * InitialTemplateBasedCascadingCampaignCreator constructor.
     * @param $initialTemplateCampaignId
     * @param CampaignClonerInterface $campaignCloner
     * @param $initialCampaignName
     */
    public function __construct(
        $initialTemplateCampaignId,
        CampaignClonerInterface $campaignCloner,
        $initialCampaignName
    ) {
        parent::__construct($initialTemplateCampaignId, $campaignCloner);
        $this->initialCampaignName = $initialCampaignName;
    }

    /**
     * Clone the given campaign.
     *
     * This overrides the parent as initial campaigns (call-1) don't
     * want to clone passed in campaign's "next template field" but use the
     * passed in $initialTemplateCampaignId's settings
     *
     * @return false|int|mixed
     * @throws CampaignCreationException
     */
    protected function cloneCampaign()
    {
        $this->log("Creating a new cascading campaign using template campaign: {$this->currentCampaignId}");

        $newCampaignId = $this->campaignCloner->cloneCampaign(
            $this->currentCampaignId,
            $this->getCurrentCampaignName()
        );
        if (!$newCampaignId) {
            throw new CampaignCreationException(
                "Could not create cascading campaign from campaign {$this->currentCampaignId}"
            );
        }
        // Set the new campaign's iteration value
        api_campaigns_setting_set($newCampaignId, CAMPAIGN_SETTING_CASCADING_ITERATION, 1);
        api_campaigns_setting_set($newCampaignId, CAMPAIGN_SETTING_CASCADING_TEMPLATE_ID, $this->currentCampaignId);

        $this->log(
            "Campaign {$this->currentCampaignId} cloned to {$newCampaignId} from template {$this->currentCampaignId}"
        );
        return $newCampaignId;
    }

    /**
     * Get the desired initial campaign name which has the suffix "-call-1" appended to it
     * All subsequent campaigns will increase this number
     *
     * @return mixed|string
     */
    public function getCurrentCampaignName()
    {
        if (preg_match(
            "/-" . self::INITIAL_CASCADING_NAME_SUFFIX . "$/",
            $this->initialCampaignName
        )) {
            return $this->initialCampaignName;
        }
        return $this->initialCampaignName . "-" . self::INITIAL_CASCADING_NAME_SUFFIX;
    }

    /**
     * @return int|mixed
     */
    public function getCurrentCampaignIteration()
    {
        return 1;
    }

    /**
     * @return bool|int|mixed
     */
    public function getPreviousCampaignIteration()
    {
        return false;
    }

    /**
     * @return bool|false|int
     */
    public function getFirstCampaign()
    {
        return false;
    }

    /**
     * @return mixed|void
     */
    public function getFirstCampaignName()
    {
        return $this->getCurrentCampaignName();
    }
}
