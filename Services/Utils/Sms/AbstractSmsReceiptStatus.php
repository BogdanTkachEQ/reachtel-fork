<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Sms;

use MabeEnum\Enum;

/**
 * Class AbstractSmsReceiptStatus
 */
abstract class AbstractSmsReceiptStatus extends Enum
{
    const SUBMITTED = 'SUBMITTED';
    const DELIVERED = 'DELIVERED';
    const EXPIRED = 'EXPIRED';
    const UNDELIVERED = 'UNDELIVERED';
    const UNKNOWN = 'UNKNOWN';

    /**
     * @return array
     */
    abstract protected static function getTranslationMap();

    /**
     * @param $string
     * @return AbstractSmsReceiptStatus
     */
    public static function translate($string)
    {
        if (isset(static::getTranslationMap()[$string])) {
            return static::getTranslationMap()[$string];
        }

        return self::UNKNOWN();
    }
}
