<?php

namespace Services\Cron\SmsReceiptReconciler;

use \DateTime;

abstract class AbstractSmsSentProvider
{
    /**
     * Get an array of sms_sent / sms_out that don't have a corresponding delivery receipt
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return array
     */
    abstract public function getUnresolvedSms(DateTime $startDate, DateTime $endDate);

    /**
     * Update SMS receipt from raw sms receipts log
     *
     * @param array $receipt
     */
    public function updateReceipt(array $receipt)
    {
        $dr = [
            'supplieruid' => $receipt['supplierid'],
            'supplier' => $receipt['supplier'],
            'status' => $receipt['status'],
            'code' => $receipt['code'],
            'supplierdate' => strtotime($receipt['supplierdate']),
        ];
        api_sms_receive_dr($dr);
    }
}
