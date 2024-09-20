<?php
// Report that list total volume of sms sent from alphacode and numeric phone numbers and also total successful
// calls made excluding calls answered by ANSWERING MACHINE
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

$dids = [];

$numeric = 0;
$alphacode = 0;

print "Starting to fetch total sms send from Campaigns\n";

api_db_ping();
$data = api_reports_overall_sms_volume_from_campaigns($groupIds, $dids, $start, $end);

if ($data === false) {
    print "Exiting since there is some issue with the sql for campaign sms";
    exit;
}

$numeric += $data['numeric'];
$alphacode += $data['alphacode'];

// Retrieve the users that belong to the given groupids
$users = api_users_list_all_by_groupowners($groupIds);

if(empty($users)){
	print "Group id(s) ".$tags['groupids']." contain no users, skipping api counts...\n";
}else {
	api_db_ping();
	$data = api_reports_overall_sms_volume_from_legacy_api($users, $dids, $start, $end);

	if($data === false) {
		print "Exiting since there is some issue with the sql for sms_api_mapping";
		exit;
	}

	$numeric += $data['numeric'];
	$alphacode += $data['alphacode'];

	api_db_ping();
	$data = api_reports_overall_sms_volume_from_rest_api($users, $start, $end);

	if($data === false) {
		print "Exiting since there is some issue with the sql for sms_out";
		exit;
	}

	$numeric += $data['numeric'];
	$alphacode += $data['alphacode'];
}

print "Starting to fetch total Calls made\n";
api_db_ping();

$totalCalls = api_reports_overall_answered_call_volume($groupIds, $start, $end);

if ($totalCalls === false) {
    print "Exiting since there is some issue with the sql for voice calls";
    exit;
}

print 'SMS from alphacode:' . $alphacode . "\n";
print 'SMS from phone numbers:' . $numeric . "\n";
print 'Total calls made:' . $totalCalls . "\n";

print "Sending email...\n";

if (array_diff(['reporting-destination', 'subject'], array_keys($tags))) {
    print "Failed to send email since the tags reporting-destination and subject are not set";
    exit;
}

$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " Overall volume report for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = "Hello,\nListed below are the overall volumes.\nSMS from alphacode:$alphacode\nSMS from phone numbers:$numeric\nTotal calls made:$totalCalls";
$email["htmlcontent"] = "Hello,<br /><br />Listed below are the overall volumes.<br /><br />SMS from alphacode:$alphacode<br /><br />SMS from phone numbers:$numeric<br /><br />Total calls made:$totalCalls";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
