<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Builders;

use Models\CampaignRecurringTime;
use Models\Day;

/**
 * Class RecurringTimeBuilder
 */
class RecurringTimeBuilder
{
    /** @var CampaignRecurringTime */
    private $recurringTime;

    /**
     * RecurringTimeBuilder constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->recurringTime = new CampaignRecurringTime();
        return $this;
    }

    /**
     * @param \DateTime $time
     * @return $this
     */
    public function setStartTime(\DateTime $time)
    {
        $this->recurringTime->setStartTime($time);
        return $this;
    }

    /**
     * @param \DateTime $time
     * @return $this
     */
    public function setEndTime(\DateTime $time)
    {
        $this->recurringTime->setEndTime($time);
        return $this;
    }

    /**
     * @param Day $day
     * @return $this
     */
    public function addActiveDay(Day $day)
    {
        $this->recurringTime->addActiveDay($day);
        return $this;
    }

    /**
     * @return CampaignRecurringTime
     */
    public function getRecurringTime()
    {
        return $this->recurringTime;
    }
}
