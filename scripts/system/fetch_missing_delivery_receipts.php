#!/usr/bin/php
<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

use Services\Exceptions\Suppliers\SmsServiceException;
use Services\Suppliers\Interfaces\SmsRetrievableInterface;
use Services\Suppliers\SmsServiceFactory;

require_once(__DIR__ . "/../../api.php");

$cronid = getenv('CRON_ID');
$tags = api_cron_tags_get($cronid);

$sms = api_sms_fetch_all_sms_id_without_receipts(
    (isset($tags['to']) ? DateTime::createFromFormat('Y-m-d H:i:s', $tags['to']) : null),
    (isset($tags['from']) ? DateTime::createFromFormat('Y-m-d H:i:s', $tags['from']) : null),
    (isset($tags['supplier']) ? $tags['supplier'] : null)
);

$smsServices = [];
$smsReceiptProcessor = new \Services\Sms\SmsReceiptProcessor();
$processed = 0;

print "Starting to fetched delivery receipts....\n";
foreach ($sms as $supplierId => $smsId) {
    if (!array_key_exists($supplierId, $smsServices)) {
        try {
            $smsServices[$supplierId] = SmsServiceFactory::getSmsService($supplierId);
            if (!($smsServices[$supplierId] instanceof SmsRetrievableInterface)) {
                $smsServices[$supplierId] = null;
            }
        } catch (Exception $exception) {
            $smsServices[$supplierId] = null;
        }
    }

    /** @var SmsRetrievableInterface $service */
    $service = $smsServices[$supplierId];
    if (is_null($service)) {
        continue;
    }

    try {
        $retrievedSms = $service->retrieveSms($smsId);
    } catch (SmsServiceException $exception) {
        print "SMS could not be retrieved (supplier: $supplierId, smsId: $smsId)\n";
        continue;
    }

    $receipt = $retrievedSms->getDeliveryReceipt();

    if (is_null($receipt->getStatus())) {
        print "No receipt status found (supplier: $supplierId, smsId: $smsId)\n";
        continue;
    }

    if (!$smsReceiptProcessor->saveReceipt($receipt)) {
        print "Failed processing delivery receipt (supplier: $supplierId, smsId: $smsId)\n";
    }

    $processed++;
}

print "Total delivery receipts fetched and processed: $processed\n";
