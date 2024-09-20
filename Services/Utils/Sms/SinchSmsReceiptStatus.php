<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Sms;

class SinchSmsReceiptStatus extends AbstractSmsReceiptStatus
{
    /**
     * @return array
     */
    protected static function getTranslationMap()
    {
        return [
            'Queued' => self::SUBMITTED(),
            'Delivered' => self::DELIVERED(),
            'Expired' => self::EXPIRED()
        ];
    }
}
