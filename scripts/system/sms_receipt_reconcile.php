<?php

require_once(__DIR__ . '/../../api.php');

use Services\Cron\SmsReceiptReconciler;
use Services\Cron\SmsReceiptReconciler\SmsApiSentProvider;
use Services\Cron\SmsReceiptReconciler\SmsCampaignSentProvider;

$tags = api_cron_tags_get(125); // System - SMS Delivery Receipt Reconciler
$reporting_destination = isset($tags['reporting-destination']) ? $tags['reporting-destination'] : 'ReachTEL IT Support <AUReachTELITSupport@equifax.com>';
$previousDays = isset($tags['process-days']) ? $tags['process-days'] : '14';
$endDateTag = isset($tags['end-date']) ? strtotime($tags['end-date']) : strtotime('now');

// Process the reconcile
try {
	$endDate = DateTime::createFromFormat('U', $endDateTag)
		->setTimeZone(new DateTimeZone('Australia/Brisbane'))
		->setTime(23, 59);

	$startDate = clone $endDate;
	$startDate = $startDate
		->setTime(0, 0)
		->sub(new DateInterval('P' . $previousDays . 'D'));

	$reconciler = new SmsReceiptReconciler();
	echo "Start processing SmsApiSentProvider from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n";
	echo $reconciler->process(new SmsApiSentProvider(), $startDate, $endDate) // Run API (SMS_OUT) reconciler
		->getInterimStatusMessage() . "\n\n";

	echo "Start processing SmsCampaignSentProvider from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n";
	echo $reconciler->process(new SmsCampaignSentProvider(), $startDate, $endDate) // Run Campaign (SMS_SENT) reconciler
		->getInterimStatusMessage() . "\n\n";
} catch (Exception $e) {
	echo $e . "\n";
	api_error_printiferror();

	$statusMessage = $reconciler ? $reconciler->getStatusMessage() : '<none>';

	$email = [];
	$email['to'] = $reporting_destination;
	$email['subject'] = "[ReachTEL] SMS Receipt Reconcile FAILED for {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}";
	$email['content'] = "SMS Receipt Reconcile FAILED.\n\nStatus Message (if available):\n$statusMessage\n\nHere is the exception message:\n$e";

	api_email_template($email);
	exit();
}

echo "Success!\n";
echo $reconciler->getStatusMessage() . "\n";
