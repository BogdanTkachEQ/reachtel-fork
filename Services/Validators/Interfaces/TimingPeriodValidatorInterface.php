<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Interfaces;

use Models\Entities\TimingGroup;

/**
 * Class TimingPeriodValidatorInterface
 */
interface TimingPeriodValidatorInterface extends DateTimeValidatorInterface
{
    /**
     * @param TimingGroup $timingGroup
     * @return $this
     */
    public function setTimingGroup(TimingGroup $timingGroup);
}
