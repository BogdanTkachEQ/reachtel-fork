<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading;

use Exception;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Hooks\Interfaces\PostHook;

/**
 * Class CascadingCampaignHook
 */
class CascadingCampaignHook implements PostHook
{

    /**
     * @var
     */
    private $campaignId;
    /**
     * @var bool
     */
    private $hasRun = false;
    /**
     * @var bool
     */
    private $errors = false;
    /**
     * @var null
     */
    private $userId;
    /**
     * @var CascadingCampaignCreatorFactory
     */
    private $factory;

    /**
     * CascadingCampaignHook constructor.
     * @param $campaignId
     * @param null $userId
     */
    public function __construct(CascadingCampaignCreatorFactory $factory, $campaignId, $userId = null)
    {
        $this->campaignId = $campaignId;
        $this->userId = $userId;
        $this->factory = $factory;
    }

    /**
     * @return false|int
     * @throws Exception
     */
    public function run()
    {
        $this->hasRun = true;
        // Check that the current campaign has next cascading template set, if not we're at the end of the line
        if (empty(trim(api_campaigns_setting_getsingle($this->campaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)))) {
            $this->errors = "No next campaign is set, finishing.";
            return null;
        }

        $creator = $this->factory->makeCreator($this->campaignId, null, $this->userId);
        try {
            $nextCampaignId = $creator->setupNextCampaign(true);
            if (!$nextCampaignId) {
                $this->errors = "No next cascading campaign created";
            }
            return $nextCampaignId;
        } catch (Exception $e) {
            $this->errors = "Could not create next campaign, " . $e->getMessage();
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function hasRun()
    {
        return $this->hasRun;
    }

    /**
     * @return bool
     */
    public function runWasSuccess()
    {
        return $this->hasRun() && empty($this->errors);
    }

    /**
     * @return bool
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }
}
