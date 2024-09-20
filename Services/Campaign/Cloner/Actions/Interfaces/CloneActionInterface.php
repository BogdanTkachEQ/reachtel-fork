<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Cloner\Actions\Interfaces;

/**
 * Interface CloneAction
 * @package Services\Campaign\Cloner\Actions
 */
interface CloneActionInterface
{
    /**
     *
     * Interface for Clone actions, the CampaignCloner accepts actions such as pre and post clone
     *
     * @param $sourceCampaignId
     * @param null $clonedCampaignId
     * @return mixed
     */
    public function apply($sourceCampaignId, $clonedCampaignId = null);
}
