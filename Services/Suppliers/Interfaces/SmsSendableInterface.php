<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Interfaces;

use Models\Sms;
use Services\Exceptions\Suppliers\SmsServiceException;

/**
 * Interface SmsSendableInterface
 */
interface SmsSendableInterface extends SmsServiceInterface
{
    /**
     * @param Sms     $sms
     * @param integer $eventId
     * @return string | integer
     * @throws SmsServiceException
     */
    public function sendSms(Sms $sms, $eventId);
}
