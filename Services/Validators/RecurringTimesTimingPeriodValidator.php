<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignRecurringTime;
use Models\Day;
use Models\Interfaces\CampaignTimingRangeInterface;
use Services\Exceptions\Validators\InvalidRecurringTimeException;
use Services\Exceptions\Validators\TimingPeriodException;

/**
 * Class RecurringTimesTimingPeriodValidator
 */
class RecurringTimesTimingPeriodValidator extends AbstractCampaignTimingPeriodValidator
{
    /** @var CampaignRecurringTime[] */
    private $recurringTimes;

    /**
     * RecurringTimesTimingPeriodValidator constructor.
     * @param TimingPeriodValidator $periodValidator
     */
    public function __construct(TimingPeriodValidator $periodValidator)
    {
        $this->recurringTimes = new ArrayCollection();
        parent::__construct($periodValidator);
    }

    /**
     * @param ArrayCollection CampaignRecurringTime[] $recurringTimes
     * @return $this
     */
    public function setRecurringTimes(ArrayCollection $recurringTimes)
    {
        $this->recurringTimes = $recurringTimes;
        return $this;
    }

    /**
     * @return boolean
     * @throws InvalidRecurringTimeException
     * @throws TimingPeriodException
     */
    public function isValid()
    {
        /** @var CampaignRecurringTime $recurringTime */
        foreach ($this->recurringTimes as $recurringTime) {
            $recurringTime->validate();
            if (parent::isValid()) {
                continue;
            }

            $days = $recurringTime->getActiveDays();
            foreach ($days as $day) {
                $startDateTime = $this->buildDateTime($day, $recurringTime->getStartTime());
                $endDateTime = $this->buildDateTime($day, $recurringTime->getEndTime());

                if (!$this->periodValidator->isValidDateTime($startDateTime) ||
                    !$this->periodValidator->isValidDateTime($endDateTime)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return CampaignRecurringTime[]
     */
    protected function getCampaignTimingRanges()
    {
        return $this->recurringTimes;
    }

    /**
     * @param Day       $day
     * @param \DateTime $dateTime
     * @return \DateTime
     */
    private function buildDateTime(Day $day, \DateTime $dateTime)
    {
        return new \DateTime($day->getDateTimeDayName() . ' ' . $dateTime->format('H:i:s'));
    }
}
