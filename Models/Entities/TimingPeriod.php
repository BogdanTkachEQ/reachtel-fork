<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use DateTime;
use Models\Day;

/**
 * Class TimingPeriod
 */
class TimingPeriod
{
    /** @var integer */
    private $id;

    /** @var Day */
    private $day;

    /** @var DateTime */
    private $start;

    /** @var DateTime */
    private $end;

    /** @var TimingGroup */
    private $timingGroup;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Day
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * @param Day $day
     * @return $this
     */
    public function setDay(Day $day)
    {
        $this->day = $day;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param DateTime $start
     * @return $this
     */
    public function setStart(DateTime $start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param DateTime $end
     * @return $this
     */
    public function setEnd(DateTime $end)
    {
        $this->end = $end;
        return $this;
    }

    /**
     * @return TimingGroup
     */
    public function getTimingGroup()
    {
        return $this->timingGroup;
    }

    /**
     * @param TimingGroup $timingGroup
     * @return $this
     */
    public function setTimingGroup(TimingGroup $timingGroup)
    {
        $this->timingGroup = $timingGroup;
        return $this;
    }

    /**
     * @param DateTime $dateTime
     * @return DateTime
     */
    public function getStartByDate(DateTime $dateTime)
    {
        if (!$this->getStart()) {
            return $this->getStart();
        }

        $periodDateTime = clone $dateTime;
        return $periodDateTime
            ->setTime(
                $this->getStart()->format('H'),
                $this->getStart()->format('i'),
                $this->getStart()->format('s')
            );
    }

    /**
     * @param DateTime $dateTime
     * @return DateTime
     */
    public function getEndByDate(DateTime $dateTime)
    {
        if (!$this->getEnd()) {
            return $this->getEnd();
        }

        $periodDateTime = clone $dateTime;
        return $periodDateTime
            ->setTime(
                $this->getEnd()->format('H'),
                $this->getEnd()->format('i'),
                $this->getEnd()->format('s')
            );
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     */
    public function inPeriod(\DateTime $dateTime)
    {
        if (!$this->getStartByDate($dateTime) || !$this->getEndByDate($dateTime)) {
            return false;
        }

        return ($dateTime >= $this->getStartByDate($dateTime)) &&
            ($dateTime <= $this->getEndByDate($dateTime));
    }
}
