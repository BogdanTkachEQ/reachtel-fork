<?php
// This report lists the total call attempts. If the call was generated but had issues, it won't be counted as attempt
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

$total = api_reports_total_call_attempts($groupIds, $start, $end);

if ($total === false) {
    print "Exiting since there is some issue with the sql";
    exit;
}

print "Total: " . $total;

print "Sending email...\n";

if (!isset($tags['reporting-destination'])) {
    print "Failed to send email since the tag reporting-destination is not set";
    exit;
}

$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " voice call attempts metrics for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = "Hello,\nTotal voice call attempts(this includes all generated calls excluding those ended up with issues that would have stopped us from attempting the call): $total\n";
$email["htmlcontent"] = "Hello,<br /><br />Total voice call attempts(this includes all generated calls excluding those ended up with issues that would have stopped us from attempting the call): $total<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
