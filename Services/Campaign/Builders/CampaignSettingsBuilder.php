<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignType;
use Models\CampaignSettings;
use Models\Entities\Country;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Class CampaignSettingsBuilder
 */
class CampaignSettingsBuilder
{
    /** @var CampaignSettings */
    private $campaignSettings;

    /**
     * CampaignSettingBuilder constructor.
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
        $this->campaignSettings = new CampaignSettings();
        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->campaignSettings->setId($id);
        return $this;
    }

    /**
     * @param CampaignType $type
     * @return $this
     */
    public function setType(CampaignType $type)
    {
        $this->campaignSettings->setType($type);
        return $this;
    }

    /**
     * @param ArrayCollection $specificTimes
     * @return $this
     */
    public function setSpecificTimes(ArrayCollection $specificTimes)
    {
        $this->campaignSettings->setSpecificTimes($specificTimes);
        return $this;
    }

    /**
     * @param ArrayCollection $recurringTimes
     * @return $this
     */
    public function setRecurringTimes(ArrayCollection $recurringTimes)
    {
        $this->campaignSettings->setRecurringTimes($recurringTimes);
        return $this;
    }

    /**
     * @param CampaignClassificationEnum $classificationEnum
     * @return $this
     */
    public function setClassificationEnum(CampaignClassificationEnum $classificationEnum)
    {
        $this->campaignSettings->setClassificationEnum($classificationEnum);
        return $this;
    }

    /**
     * @param Region $region
     * @return $this
     */
    public function setRegion(Region $region = null)
    {
        $this->campaignSettings->setRegion($region);
        return $this;
    }

    /**
     * @param TimingDescriptor $timingDescriptor
     * @return $this
     */
    public function setTimingDescriptor(TimingDescriptor $timingDescriptor)
    {
        $this->campaignSettings->setTimingDescriptor($timingDescriptor);
        return $this;
    }

    /**
     * @param \DateTimeZone $dateTimeZone
     * @return $this
     */
    public function setTimeZone(\DateTimeZone $dateTimeZone)
    {
        $this->campaignSettings->setTimeZone($dateTimeZone);
        return $this;
    }

    /**
     * @param mixed $callerIdWithHeld
     * @return $this
     */
    public function setCallerIdWithHeld($callerIdWithHeld)
    {
        $this->campaignSettings->setCallerIdWithHeld($callerIdWithHeld);
        return $this;
    }

    /**
     * @return CampaignSettings
     */
    public function getCampaignSettings()
    {
        return $this->campaignSettings;
    }
}
