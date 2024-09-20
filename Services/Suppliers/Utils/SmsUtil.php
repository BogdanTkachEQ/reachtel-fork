<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Utils;

use Services\Suppliers\Interfaces\SmsRetrievableInterface;
use Services\Suppliers\Interfaces\SmsSendableInterface;
use Services\Suppliers\Interfaces\SmsServiceInterface;

/**
 * Class SmsUtil
 */
class SmsUtil
{
    /**
     * @param SmsServiceInterface $service
     * @return boolean
     */
    public static function isSendable(SmsServiceInterface $service)
    {
        return $service instanceof SmsSendableInterface;
    }

    /**
     * @param SmsServiceInterface $service
     * @return boolean
     */
    public static function isRetrievable(SmsServiceInterface $service)
    {
        return $service instanceof SmsRetrievableInterface;
    }
}
