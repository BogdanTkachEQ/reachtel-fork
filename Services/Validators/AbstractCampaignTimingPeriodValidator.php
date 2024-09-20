<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Models\Entities\TimingGroup;
use Models\Interfaces\CampaignTimingRangeInterface;
use Services\Exceptions\Campaign\Validators\CampaignTimingRangeValidationFailure;
use Services\Validators\Interfaces\CampaignValidatorInterface;
use Services\Validators\Interfaces\TimingPeriodValidatorInterface as TpValidatorInterface;

/**
 * Class AbstractCampaignTimingRangesValidator
 */
abstract class AbstractCampaignTimingPeriodValidator implements CampaignValidatorInterface, TpValidatorInterface
{
    /** @var TimingPeriodValidator */
    protected $periodValidator;

    /** @var TimingGroup */
    protected $timingGroup;

    /**
     * @return CampaignTimingRangeInterface[]
     */
    abstract protected function getCampaignTimingRanges();

    /**
     * AbstractCampaignTimeTypeTimingPeriodValidator constructor.
     * @param TimingPeriodValidator $periodValidator
     */
    public function __construct(TimingPeriodValidator $periodValidator)
    {
        $this->periodValidator = $periodValidator;
    }

    /**
     * @param TimingGroup $timingGroup
     * @return $this
     */
    public function setTimingGroup(TimingGroup $timingGroup)
    {
        $this->timingGroup = $timingGroup;
        $this->periodValidator->setTimingGroup($timingGroup);
        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        if (!$this->timingGroup) {
            return true;
        }

        return false;
    }

    /**
     * @param \DateTime $dateTime
     * @return boolean
     * @throws CampaignTimingRangeValidationFailure
     * @throws \Exception
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        if (!is_null($this->timingGroup) && !$this->periodValidator->isValidDateTime($dateTime)) {
            return false;
        }

        foreach ($this->getCampaignTimingRanges() as $campaignTimingRange) {
            if ($campaignTimingRange->isValidDateTime($dateTime)) {
                return true;
            }
        }

        throw new CampaignTimingRangeValidationFailure('Time passed is out of range');
    }
}
