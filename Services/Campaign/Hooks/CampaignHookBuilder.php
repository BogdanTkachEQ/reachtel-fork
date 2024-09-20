<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks;

use Services\Campaign\Hooks\Cascading\CascadingCampaignHook;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Hooks\Hooks;

/**
 * Class CampaignHookBuilder
 */
class CampaignHookBuilder
{

    /**
     * Setup the hooks for the given campaign
     *
     * @param $campaignId
     * @param $userId
     * @return Hooks
     */
    public static function build($campaignId, $userId = null)
    {
        $hooks = new Hooks();
        if (api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_CASCADING_CAMPAIGN)) {
            $hooks->addPostHook(new CascadingCampaignHook(new CascadingCampaignCreatorFactory(), $campaignId, $userId));
        }
        if (!empty(api_campaigns_tags_get($campaignId, "post-completion-hook"))) {
            $hooks->addPostHook(new TagCampaignHook($campaignId));
        }
        return $hooks;
    }
}
