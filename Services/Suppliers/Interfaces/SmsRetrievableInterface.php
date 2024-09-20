<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Interfaces;

use Models\Sms;
use Services\Exceptions\Suppliers\SmsServiceException;

/**
 * Interface SmsRetrievableInterface
 */
interface SmsRetrievableInterface extends SmsServiceInterface
{
    /**
     * @param string | integer $messageId
     * @return Sms
     * @throws SmsServiceException
     */
    public function retrieveSms($messageId);
}
