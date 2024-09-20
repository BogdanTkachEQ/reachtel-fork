<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Yabbr;

use Services\Utils\Sms\AbstractSmsReceiptStatus;
use Services\Utils\Sms\YabbrSmsReceiptStatus;

/**
 * Class SmsFunctions
 */
class SmsFunctions
{
    /**
     * @param array $receipts
     * @return AbstractSmsReceiptStatus
     */
    public static function getStatusFromReceipts(array $receipts)
    {
        foreach ($receipts as $name => $date) {
            if ($name === 'simulated') {
                continue;
            }

            return YabbrSmsReceiptStatus::translate($name);
        }

        return YabbrSmsReceiptStatus::UNKNOWN();
    }

    /**
     * @param array $receipts
     * @return \DateTime | null
     */
    public static function getStatusUpdateDateTimeFromReceipts(array $receipts)
    {
        foreach ($receipts as $name => $date) {
            if ($name === 'simulated') {
                continue;
            }

            return \DateTime::createFromFormat('Y-m-d\TH:i:s.uO', $date) ? : null;
        }

        return null;
    }
}
