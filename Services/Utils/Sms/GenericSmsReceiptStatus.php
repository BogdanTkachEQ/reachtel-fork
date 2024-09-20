<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Sms;

/**
 * Class GenericSmsReceiptStatus
 */
class GenericSmsReceiptStatus extends AbstractSmsReceiptStatus
{
    /**
     * @return array
     */
    protected static function getTranslationMap()
    {
        return [
            'DELIVERED' => self::DELIVERED(),
            'SUBMITTED' => self::SUBMITTED(),
            'UNDELIVERED' => self::UNDELIVERED(),
            'EXPIRED' => self::EXPIRED(),
            'UNKNOWN' => self::UNKNOWN()
        ];
    }
}
