<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models;

use Doctrine\Common\Collections\ArrayCollection;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Class CampaignSettings
 */
class CampaignSettings
{
    /** @var integer */
    private $id;

    /** @var CampaignType */
    private $type;

    /** @var ArrayCollection<CampaignSpecificTime> */
    private $specificTimes ;

    /** @var ArrayCollection<CampaignRecurringTime> */
    private $recurringTimes;

    /** @var CampaignClassificationEnum */
    private $classificationEnum;

    /** @var Region */
    private $region;

    /** @var TimingDescriptor */
    private $timingDescriptor;

    /** @var \DateTimeZone */
    private $timeZone;

    /** @var boolean */
    private $callerIdWithHeld = false;

    public function __construct()
    {
        $this->specificTimes = new ArrayCollection();
        $this->recurringTimes = new ArrayCollection();
    }

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
     * @return CampaignType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param CampaignType $type
     * @return $this
     */
    public function setType(CampaignType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return ArrayCollection<CampaignSpecificTime>
     */
    public function getSpecificTimes()
    {
        return $this->specificTimes;
    }

    /**
     * @param ArrayCollection<CampaignSpecificTime> $specificTimes
     * @return $this
     */
    public function setSpecificTimes(ArrayCollection $specificTimes)
    {
        $this->specificTimes = $specificTimes;
        return $this;
    }

    /**
     * @return ArrayCollection<CampaignRecurringTime>
     */
    public function getRecurringTimes()
    {
        return $this->recurringTimes;
    }

    /**
     * @param ArrayCollection<CampaignRecurringTime> $recurringTimes
     * @return $this
     */
    public function setRecurringTimes(ArrayCollection $recurringTimes)
    {
        $this->recurringTimes = $recurringTimes;
        return $this;
    }

    /**
     * @return CampaignClassificationEnum
     */
    public function getClassificationEnum()
    {
        return $this->classificationEnum;
    }

    /**
     * @param CampaignClassificationEnum $classificationEnum
     * @return $this
     */
    public function setClassificationEnum(CampaignClassificationEnum $classificationEnum)
    {
        $this->classificationEnum = $classificationEnum;
        return $this;
    }

    /**
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param Region $region
     * @return $this
     */
    public function setRegion(Region $region = null)
    {
        $this->region = $region;
        return $this;
    }

    /**
     * @return TimingDescriptor
     */
    public function getTimingDescriptor()
    {
        return $this->timingDescriptor;
    }

    /**
     * @param TimingDescriptor $timingDescriptor
     * @return $this
     */
    public function setTimingDescriptor(TimingDescriptor $timingDescriptor)
    {
        $this->timingDescriptor = $timingDescriptor;
        return $this;
    }

    /**
     * @return \DateTimeZone
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param \DateTimeZone $timeZone
     * @return $this
     */
    public function setTimeZone(\DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isCallerIdWithHeld()
    {
        return $this->callerIdWithHeld;
    }

    /**
     * @param mixed $callerIdWithHeld
     * @return $this
     */
    public function setCallerIdWithHeld($callerIdWithHeld)
    {
        $this->callerIdWithHeld = ($callerIdWithHeld === true || $callerIdWithHeld === 'on');
        return $this;
    }
}
