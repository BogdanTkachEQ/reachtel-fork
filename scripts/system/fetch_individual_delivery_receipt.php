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

$expectedTags = [
    'supplier_id',
    'sms_id'
];

if (!$tags) {
    print "No tags set for cron, required tags are: " . implode(", ", $expectedTags);
    exit(1);
}

$missingTags = array_diff($expectedTags, array_keys($tags));
if ($missingTags) {
    print "There are required tags missing: " . implode(", ", $missingTags);
    exit(1);
}

try {
    $service = SmsServiceFactory::getSmsService($tags['supplier_id']);

    if (!($service instanceof SmsRetrievableInterface)) {
        print "Unable to fetch receipt for this supplier";
        exit(1);
    }
} catch (SmsServiceException $exception) {
    print "Unable to fetch receipt for this supplier";
    exit(1);
}

try {
    $retrievedSms = $service->retrieveSms($tags['sms_id']);
} catch (SmsServiceException $exception) {
    print "SMS could not be retrieved\n";
    exit(1);
}

$receipt = $retrievedSms->getDeliveryReceipt();

if (is_null($receipt->getStatus())) {
    print "No receipt status found\n";
    exit(1);
}

$smsReceiptProcessor = new \Services\Sms\SmsReceiptProcessor();

if (!$smsReceiptProcessor->saveReceipt($receipt)) {
    print "Failed processing delivery receipt\n";
    exit;
}

print "Delivery receipt saved";
