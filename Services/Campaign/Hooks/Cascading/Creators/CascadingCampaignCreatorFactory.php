<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks\Cascading\Creators;

use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\Interfaces\CampaignCreatorFactoryInterface;

/**
 * Class CascadingCampaignCreatorFactory
 */
class CascadingCampaignCreatorFactory implements CampaignCreatorFactoryInterface
{

    /**
     * @param $sourceCampaignId If this is the first in a series of cascading campaigns,
     * provide the template campaign as source id and $initialName
     * @param null $initialName The name to give the series of cascading campaigns
     * if provided returns an initial campaign creator
     * @param null $ownerId - the user who will own the campaign
     * @return InitialTemplateBasedCascadingCampaignCreator|TemplateBasedCascadingCampaignCreator
     */
    public function makeCreator($sourceCampaignId, $initialName = null, $ownerId = null)
    {
        if ($initialName) {
            return new InitialTemplateBasedCascadingCampaignCreator(
                $sourceCampaignId,
                new GenericCampaignCloner($ownerId),
                $initialName
            );
        } else {
            return new TemplateBasedCascadingCampaignCreator($sourceCampaignId, new GenericCampaignCloner($ownerId));
        }
    }
}
