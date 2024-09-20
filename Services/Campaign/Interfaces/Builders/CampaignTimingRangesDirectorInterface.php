<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Interfaces\Builders;

use Doctrine\Common\Collections\Collection;

/**
 * Interface CampaignTimingRangesDirectorInterface
 */
interface CampaignTimingRangesDirectorInterface
{
    /**
     * @param integer $campaignId
     * @return Collection CampaignTimingRageInterface[]
     */
    public function buildFromCampaignId($campaignId);

    /**
     * @param array $settings
     * @return Collection CampaignTimingRageInterface[]
     */
    public function buildFromArray(array $settings);
}
