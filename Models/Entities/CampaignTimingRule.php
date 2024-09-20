<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

/**
 * Class CampaignTimingRule
 */
class CampaignTimingRule
{
    /** @var integer */
    private $id;

    /** @var TimingDescriptor */
    private $timingDescriptor;

    /** @var CampaignClassification */
    private $campaignClassification;

    /** @var TimingGroup */
    private $timingGroup;

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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return CampaignClassification
     */
    public function getCampaignClassification()
    {
        return $this->campaignClassification;
    }

    /**
     * @param CampaignClassification $campaignClassification
     * @return $this
     */
    public function setCampaignClassification(CampaignClassification $campaignClassification)
    {
        $this->campaignClassification = $campaignClassification;
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
}
