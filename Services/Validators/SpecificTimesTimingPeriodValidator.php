<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSpecificTime;
use Services\Exceptions\Validators\InvalidSpecificTimeException;
use Services\Exceptions\Validators\TimingPeriodException;

/**
 * Class SpecificTimesTimingPeriodValidator
 */
class SpecificTimesTimingPeriodValidator extends AbstractCampaignTimingPeriodValidator
{
    /** @var CampaignSpecificTime[] */
    private $specificTimes;

    /**
     * SpecificTimesTimingPeriodValidator constructor.
     * @param TimingPeriodValidator $periodValidator
     */
    public function __construct(TimingPeriodValidator $periodValidator)
    {
        $this->specificTimes = new ArrayCollection();
        parent::__construct($periodValidator);
    }

    /**
     * @param ArrayCollection CampaignSpecificTime[] $specificTimes
     * @return $this
     */
    public function setSpecificTimes(ArrayCollection $specificTimes)
    {
        $this->specificTimes = $specificTimes;
        return $this;
    }

    /**
     * @return boolean
     * @throws InvalidSpecificTimeException
     * @throws TimingPeriodException
     */
    public function isValid()
    {
        /** @var CampaignSpecificTime $specificTime */
        foreach ($this->specificTimes as $specificTime) {
            $specificTime->validate();

            if (parent::isValid()) {
                continue;
            }

            if ($specificTime->getStatus() === CampaignSpecificTime::STATUS_PAST) {
                continue;
            }

            if (!$this->periodValidator->isValidDateTime($specificTime->getStartDateTime()) ||
                !$this->periodValidator->isValidDateTime($specificTime->getEndDateTime())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return CampaignSpecificTime[]
     */
    protected function getCampaignTimingRanges()
    {
        return $this->specificTimes;
    }
}
