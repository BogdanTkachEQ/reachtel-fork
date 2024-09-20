<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Builders;

use Models\CampaignSpecificTime;

/**
 * Class SpecificTimeBuilder
 */
class SpecificTimeBuilder
{
    /** @var CampaignSpecificTime */
    private $specificTime;

    /**
     * SpecificTimeBuilder constructor.
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
        $this->specificTime = new CampaignSpecificTime();
        return $this;
    }

    /**
     * @param \DateTime $dateTime
     * @return $this
     */
    public function setStartDateTime(\DateTime $dateTime)
    {
        $this->specificTime->setStartDateTime($dateTime);
        return $this;
    }

    /**
     * @param \DateTime $dateTime
     * @return $this
     */
    public function setEndDateTime(\DateTime $dateTime)
    {
        $this->specificTime->setEndDateTime($dateTime);
        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->specificTime->setStatus($status);
        return $this;
    }

    /**
     * @return CampaignSpecificTime
     */
    public function getSpecificTime()
    {
        return $this->specificTime;
    }
}
