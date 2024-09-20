<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Sms;

/**
 * Class YabbrSmsReceiptStatus
 */
class YabbrSmsReceiptStatus extends AbstractSmsReceiptStatus
{
    /**
     * @return array
     */
    protected static function getTranslationMap()
    {
        return [
            'delivered' => self::DELIVERED(),
            'undelivered' => self::UNDELIVERED(),
            'expired' => self::EXPIRED(),
            'rejected' => self::UNDELIVERED()
        ];
    }
}
