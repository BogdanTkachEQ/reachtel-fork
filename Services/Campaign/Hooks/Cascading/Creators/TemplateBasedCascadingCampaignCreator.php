<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading\Creators;

use InvalidArgumentException;
use Services\Campaign\Cloner\Interfaces\CampaignClonerInterface;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Exceptions\CampaignValidationException;

/**
 * Class BasicCascadingCampaign
 * @package Services\Hooks\Campaign
 *
 * Give the currentCampaignId has CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE set,
 * creates a new campaign cloning the template from CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE
 * and the relevant targets from $currentCampaignId
 *
 * The class will create cascading campaigns named with an integer as the last characters after a hypen: e.g
 * example-campaign-step-1
 * example-campaign-step-2
 * example-campaign-step-3
 *
 */
class TemplateBasedCascadingCampaignCreator extends AbstractGenericCascadingCampaignCreator
{
    /**
     * TemplateCascadingCampaignCreator constructor.
     * @param int $currentCampaignId
     * @param CampaignClonerInterface $campaignCloner
     */
    public function __construct($currentCampaignId, CampaignClonerInterface $campaignCloner)
    {
        parent::__construct($currentCampaignId, $campaignCloner);
    }

    /**
     *
     * Clone the next campaign based on the template defined in $currentCampaign's
     * CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE setting
     *
     * @return false|int|mixed
     * @throws CampaignCreationException Campaign failed to create.
     * @throws CampaignValidationException
     */
    protected function cloneCampaign()
    {
        $this->log(
            "Cloning a cascading campaign for the next iteration"
                    ."{$this->getNextCampaignIteration()}, campaign id: {$this->currentCampaignId}"
        );

        $nextTemplateCampaignId = $this->getNextTemplate();

        /**
         * Check if the desired cascading campaign already exists with the same name
         * If so we use it as long as it's a cascading campaign and has the same template id
         * Otherwise clone the template and create a new campaign
         */
        if ($newCampaignId = $this->nextCampaignExists($this->getNextCampaignName())) {
            if (!api_campaigns_setting_getsingle($newCampaignId, CAMPAIGN_SETTING_CASCADING_CAMPAIGN) ||
                api_campaigns_setting_getsingle(
                    $newCampaignId,
                    CAMPAIGN_SETTING_CASCADING_TEMPLATE_ID
                ) != $nextTemplateCampaignId
            ) {
                throw new CampaignValidationException("Must only reuse a cascading campaign with the same template id");
            }
        } else {
            $newCampaignId = $this->campaignCloner->cloneCampaign(
                $nextTemplateCampaignId,
                $this->getNextCampaignName()
            );
        }

        if (!$newCampaignId) {
            throw new CampaignCreationException(
                "Could not create cascading campaign from campaign {$this->currentCampaignId}"
            );
        }

        $this->log("Cloned campaign, the next iteration's campaign id is {$newCampaignId}");

        /**
         * ! These are critical settings for cascading campaigns
         */
        // Set the new campaign's iteration value
        api_campaigns_setting_set(
            $newCampaignId,
            CAMPAIGN_SETTING_CASCADING_ITERATION,
            $this->getNextCampaignIteration()
        );
        // Set the template this campaign is based upon
        api_campaigns_setting_set($newCampaignId, CAMPAIGN_SETTING_CASCADING_TEMPLATE_ID, $nextTemplateCampaignId);
        // Set the campaign that ran before this iteration
        api_campaigns_setting_set(
            $newCampaignId,
            CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID,
            $this->currentCampaignId
        );

        $this->log(
            "Campaign {$this->currentCampaignId} cloned (cascaded) to "
                    ."campaign: {$newCampaignId} from template {$nextTemplateCampaignId}"
        );
        return $newCampaignId;
    }

    /**
     * Find the template for the next campaign.  This is a campaign setting
     *
     * @return false|int
     * @throws CampaignCreationException
     */
    public function getNextTemplate()
    {
        $nextTemplate = api_campaigns_setting_getsingle(
            $this->currentCampaignId,
            CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE
        );
        if (!$nextTemplate) {
            throw new CampaignCreationException(
                "No template is set for the campaign {$this->currentCampaignId}"
            );
        }
        $this->log("Campaign {$this->currentCampaignId} using template campaign: to {$nextTemplate}");
        $nextTemplateCampaignId = api_campaigns_nametoid($nextTemplate);
        if (!$nextTemplateCampaignId) {
            throw new InvalidArgumentException("The template {$nextTemplate} could not be found");
        }
        return $nextTemplateCampaignId;
    }

    /**
     * @return mixed|string
     * @throws CampaignCreationException
     */
    public function getNextCampaignName()
    {
        $nextIteration = $this->getNextCampaignIteration();
        $currentName = $this->getCurrentCampaignName();
        return static::buildCampaignNameWithIteration($currentName, $nextIteration);
    }

    /**
     * @param string  $campaignName
     * @param integer $iterationNumber
     * @return mixed | string
     */
    public static function buildCampaignNameWithIteration($campaignName, $iterationNumber)
    {
        if (strpos($campaignName, self::CASCADING_NAME_SUFFIX) === false) {
            return $campaignName . "-" . self::CASCADING_NAME_SUFFIX . $iterationNumber;
        }
        return preg_replace(
            "/" . preg_quote(self::CASCADING_NAME_SUFFIX, "/") . "[0-9]+$/",
            self::CASCADING_NAME_SUFFIX . $iterationNumber,
            $campaignName
        );
    }

    /**
     * @return mixed
     * @throws CampaignCreationException No iteration set.
     */
    public function getCurrentCampaignIteration()
    {
        $iteration = api_campaigns_setting_getsingle($this->currentCampaignId, CAMPAIGN_SETTING_CASCADING_ITERATION);
        if (!is_numeric($iteration)) {
            throw new CampaignCreationException(
                "The campaign " . $this->currentCampaignId . " has no cascade iteration set"
            );
        }
        return $iteration;
    }

    /**
     * @return int|mixed
     * @throws CampaignCreationException No iteration set.
     */
    public function getNextCampaignIteration()
    {
        $campaignNumber = $this->getCurrentCampaignIteration();
        return (int)$campaignNumber + 1;
    }

    /**
     * @return mixed
     */
    public function getCurrentCampaignName()
    {
        return api_campaigns_setting_getsingle($this->currentCampaignId, CAMPAIGN_SETTING_NAME);
    }

    /**
     * @return bool|mixed
     * @throws CampaignCreationException No iteration set.
     */
    public function getPreviousCampaignName()
    {
        if ($this->getCurrentCampaignIteration() <= 1) {
            return false;
        }
        $previousCampaignId = api_campaigns_setting_getsingle(
            $this->currentCampaignId,
            CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID
        );
        return api_campaigns_setting_getsingle($previousCampaignId, CAMPAIGN_SETTING_NAME);
    }

    /**
     * @return bool|int|mixed
     * @throws CampaignCreationException
     */
    public function getPreviousCampaignIteration()
    {
        $previousCampaignId = api_campaigns_setting_getsingle(
            $this->currentCampaignId,
            CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID
        );
        if (!$previousCampaignId) {
            $this->log("Could not find previous campaign for this campaign");
            return false;
        }
        return api_campaigns_setting_getsingle($previousCampaignId, CAMPAIGN_SETTING_CASCADING_ITERATION);
    }

    /**
     * @return bool
     * @throws CampaignCreationException
     */
    private function nextCampaignExists()
    {
        return api_campaigns_checknameexists($this->getNextCampaignName());
    }
}
