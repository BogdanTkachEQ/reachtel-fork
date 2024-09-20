<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Interfaces;

/**
 * Interface CampaignCreatorFactory
 */
interface CampaignCreatorFactoryInterface
{
    /**
     * @param $sourceCampaignId
     * @param null $initialName
     * @param null $ownerId
     * @return mixed
     */
    public function makeCreator($sourceCampaignId, $initialName = null, $ownerId = null);
}
