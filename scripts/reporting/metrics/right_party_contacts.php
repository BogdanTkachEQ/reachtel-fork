<?php
// This report lists the total sms that was delivered.
require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if (!isset($tags['groupids']) || !trim($tags['groupids'])) {
    print 'Failed because groupsids tag is not set or is empty';
    exit;
}

$groupIds = explode(',', $tags['groupids']);

if (isset($tags['run-date'])) {
	try {
		$start = new DateTime($tags['run-date']);
	}catch(\Exception $e){
		print "Invalid run date given: '".$tags['run-date']."' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
}else{
	$start = new DateTime('First day of last month Midnight');
}

if (isset($tags['end-run-date'])) {
	try {
		$end = new DateTime($tags['end-run-date']);
	}catch(\Exception $e){
		print "Invalid end run date given:  '".$tags['end-run-date']."' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
}else{
	$end = new DateTime('Last day of last month 23:59:59');
}

print "Running from ".$start->format(DATE_ATOM)." to ".$end->format(DATE_ATOM)."\n";

print "Starting to fetch sms for campaigns\n";

$deliveredSms = api_reports_total_sms_delivered_from_campaign($groupIds, $start, $end);

if ($deliveredSms === false) {
    print "Exiting since there is some issue with the sql for campaign sms";
    exit;
}
print "SMS delivered from campaign: $deliveredSms\n";

api_db_ping();

// Retrieve the users that belong to the given groupids
$users = api_users_list_all_by_groupowners($groupIds);

if(empty($users)) {
	print "Group id(s) ".$tags['groupids']." contain no users, skipping api counts...\n";
}else{
	$oldApiDeliveredSms = api_reports_total_sms_delivered_from_legacy_api($users, $start, $end);
	if($oldApiDeliveredSms === false) {
		print "Exiting since there is some issue with the sql for old api sms";
		exit;
	}
	print "SMS delivered from old api: $oldApiDeliveredSms\n";
	$deliveredSms += $oldApiDeliveredSms;

	api_db_ping();
	$restApiDeliveredSms = api_reports_total_sms_delivered_from_rest_api($users, $start, $end);

	if ($restApiDeliveredSms === false) {
		print "Exiting since there is some issue with the sql for rest sms";
		exit;
	}
	print "SMS delivered from rest api: $restApiDeliveredSms\n";
	$deliveredSms += $restApiDeliveredSms;
}

print 'Total Delivered SMS: ' . $deliveredSms . "\n";

print "Sending email...\n";

if (array_diff(['reporting-destination', 'subject'], array_keys($tags))) {
    print "Failed to send email since the tags reporting-destination and subject are not set";
    exit;
}

$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " right party contacts report for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = "Hello,\n\nTotal Delivered SMS: $deliveredSms\n";
$email["htmlcontent"] = "Hello,<br /><br />Total Delivered SMS: $deliveredSms<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
