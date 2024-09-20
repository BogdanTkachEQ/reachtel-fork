<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Models\Entities\TimingGroup;
use Services\Exceptions\Validators\TimingPeriodException;
use Services\Validators\Interfaces\TimingPeriodValidatorInterface;

/**
 * Class TimingPeriodValidator
 */
class TimingPeriodValidator implements TimingPeriodValidatorInterface
{
    /** @var TimingGroup */
    private $timingGroup;

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
     * @param \DateTime $dateTime
     * @return boolean
     * @throws TimingPeriodException
     */
    public function isValidDateTime(\DateTime $dateTime)
    {
        if (is_null($this->timingGroup)) {
            throw new TimingPeriodException('Missing timing group when attempting to validate');
        }

        $period = $this->timingGroup->getTimingPeriodByDateTime($dateTime);

        if (!$period) {
            // No specific rule for that day. So it is invalid.
            return false;
        }

        return $period->inPeriod($dateTime);
    }
}
