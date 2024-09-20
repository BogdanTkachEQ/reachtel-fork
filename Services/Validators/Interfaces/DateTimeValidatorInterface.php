<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Interfaces;

/**
 * interface DateTimeValidatorInterface
 */
interface DateTimeValidatorInterface
{
    /**
     * @param \DateTime $dateTime
     * @return boolean
     * @throws \Exception
     */
    public function isValidDateTime(\DateTime $dateTime);
}
