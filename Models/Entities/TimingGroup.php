<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Models\Day;
use Services\Utils\TimingPeriodUtils;

/**
 * Class TimingGroup
 */
class TimingGroup
{
    /** @var integer */
    private $id;

    /** @var string */
    private $name;

    /** @var TimingPeriod[] */
    private $timingPeriods;

    /** @var Region[] */
    private $regions;

    /**
     * TimingGroup constructor.
     */
    public function __construct()
    {
        $this->timingPeriods = new ArrayCollection();
        $this->regions = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection TimingPeriod[]
     */
    public function getTimingPeriods()
    {
        return $this->timingPeriods;
    }

    /**
     * @param Collection $timingPeriods
     * @return $this
     */
    public function setTimingPeriods(Collection $timingPeriods)
    {
        $this->timingPeriods = $timingPeriods;
        return $this;
    }

    /**
     * @param TimingPeriod $timingPeriod
     * @return $this
     */
    public function addTimingPeriod(TimingPeriod $timingPeriod)
    {
        $this->timingPeriods->add($timingPeriod);
        return $this;
    }

    /**
     * @return Collection Region[]
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * @param Collection $regions
     * @return $this
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;
        return $this;
    }

    /**
     * @param Region $region
     * @return TimingGroup
     */
    public function addRegion(Region $region)
    {
        $this->regions->add($region);
        return $this;
    }

    /**
     * @param \DateTime $dateTime
     * @return TimingPeriod|null
     */
    public function getTimingPeriodByDateTime(\DateTime $dateTime)
    {
        if (!$this->getTimingPeriods()->count()) {
            return null;
        }

        $day = Day::byDateTime($dateTime);

        $periods = $this
            ->getTimingPeriods()
            ->filter(function (TimingPeriod $period) use ($day) {
                return $period->getDay()->is($day);
            });

        if (!$periods->count()) {
            return null;
        }

        return $periods->first();
    }
}
