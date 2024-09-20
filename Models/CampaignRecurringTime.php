<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Interfaces\CampaignTimingRangeInterface;
use Services\Exceptions\Validators\InvalidRecurringTimeException;

/**
 * Class CampaignTiming
 */
class CampaignRecurringTime implements CampaignTimingRangeInterface
{
    /** @var \DateTime */
    private $startTime;

    /** @var \DateTime */
    private $endTime;

    /** @var Day[] */
    private $activeDays;

    /**
     * CampaignRecurringTime constructor.
     */
    public function __construct()
    {
        $this->activeDays = new ArrayCollection();
    }

    /**
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $startTime
     * @return $this
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param \DateTime $endTime
     * @return $this
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getActiveDays()
    {
        return $this->activeDays;
    }

    /**
     * @param ArrayCollection Day[] $activeDays
     * @return $this
     */
    public function setActiveDays(ArrayCollection $activeDays)
    {
        $this->activeDays = $activeDays;
        return $this;
    }

    /**
     * @param Day $day
     * @return $this
     */
    public function addActiveDay(Day $day)
    {
        $this->activeDays->add($day);
        return $this;
    }

    /**
     * @return boolean
     * @throws InvalidRecurringTimeException
     */
    public function validate()
    {
        if (is_null($this->startTime) || is_null($this->endTime)) {
            throw new InvalidRecurringTimeException('Start time and end time can not be empty');
        }

        $startTime = \DateTime::createFromFormat('H:i:s', $this->startTime->format('H:i:s'));
        $endTime = \DateTime::createFromFormat('H:i:s', $this->endTime->format('H:i:s'));

        if ($startTime >= $endTime) {
            throw new InvalidRecurringTimeException('Start time can not be greater than or equal to end time');
        }

        return true;
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     * @throws \Exception
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        $this->validate();

        $day = Day::byDateTime($dateTime);
        if (!$this->getActiveDays()->contains($day)) {
            return false;
        }

        $startTime = \DateTime::createFromFormat('H:i:s', $this->startTime->format('H:i:s'));
        $endTime = \DateTime::createFromFormat('H:i:s', $this->endTime->format('H:i:s'));
        $dateTime = \DateTime::createFromFormat('H:i:s', $dateTime->format('H:i:s'));

        return ($startTime <= $dateTime && $endTime >= $dateTime);
    }
}
