<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Limits\SendRate;

/**
 * Interface SendRateCalc
 * @package Services\Campaign\Limits\SendRate
 */
interface SendRateCalc
{
    public function calculateRate($modifier);
    public function setCampaignId($campaignId);
}
