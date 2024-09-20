<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Limits\SendRate;

/**
 * Class TimeBasedSendRateCalc
 * @package Services\Campaign\Limits\SendRate
 */
class TimeRemainingSendRateCalc implements SendRateCalc
{
    private $campaignId;


    public function __construct($campaignId = null)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return null
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param null $campaignId
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }
    /**
     * @param int $modifier
     * @return float|int
     */
    public function calculateRate($modifier = 0)
    {
        if (!$this->campaignId) {
            throw new \InvalidArgumentException("Campaign Id must be set");
        }
        $targets = api_data_target_status($this->campaignId);
        $secondsRemaining = api_restrictions_time_remaining($this->campaignId);
        if (!is_numeric($secondsRemaining) || ($secondsRemaining <= 0)) {
            $secondsRemaining = 60;
        }
        return ceil((($targets["READY"] + $targets["REATTEMPT"]) / ($secondsRemaining / 3600)) * 1.02) + $modifier;
    }
}
