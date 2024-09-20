<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Interfaces;

use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Validators\Interfaces\DateTimeValidatorInterface;

/**
 * Interface CampaignTimingRangeInterface
 */
interface CampaignTimingRangeInterface extends DateTimeValidatorInterface
{
    /**
     * @return boolean
     * @throws ValidatorRuntimeException
     */
    public function validate();
}
