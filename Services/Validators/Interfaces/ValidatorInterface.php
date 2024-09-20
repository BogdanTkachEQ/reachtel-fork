<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Interfaces;

use Services\Exceptions\Validators\ValidatorRuntimeException;

/**
 * Class CampaignValidatorInterface
 */
interface ValidatorInterface
{
    /**
     * @return boolean
     * @throws ValidatorRuntimeException
     */
    public function isValid();
}
