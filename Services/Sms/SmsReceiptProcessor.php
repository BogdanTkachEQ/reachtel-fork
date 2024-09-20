<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Sms;

use Models\SmsDeliveryReceipt;
use Services\Utils\Sms\AbstractSmsReceiptStatus;

/**
 * Class SmsReceiptProcessor
 */
class SmsReceiptProcessor
{
    /**
     * @param SmsDeliveryReceipt $deliveryReceipt
     * @return boolean
     */
    public function saveReceipt(SmsDeliveryReceipt $deliveryReceipt)
    {
        $date = $deliveryReceipt->getStatusUpdateDateTime() ? : new \DateTime();
        // TODO: Update smpp-rx-default.php to call this function
        $dr = [
            "supplier" => $deliveryReceipt->getSupplierId(),
            "supplieruid" => $deliveryReceipt->getSmsId(),
            "status" => $deliveryReceipt->getStatus() ?
                $deliveryReceipt->getStatus()->getValue() :
                AbstractSmsReceiptStatus::UNKNOWN,
            "code" => $deliveryReceipt->getErrorCode(),
            "supplierdate" => $date->getTimestamp()
        ];

        return api_queue_add('smsdr', $dr);
    }
}
