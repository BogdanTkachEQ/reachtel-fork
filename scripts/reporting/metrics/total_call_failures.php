<?php
/**
 *
 * This report lists the total 'hard' failed call attempts.
 * Failed attempts being disconnects, network congestion, etc.
 *
 * usage:  export CRON_ID="2" ; php ./total_call_failures.php
 *
 */
require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if(!isset($tags['groupids']) || !trim($tags['groupids'])) {
	print 'Exiting: groupsids tag is not set or is empty';
	exit;
}

if(!isset($tags['reporting-destination'])) {
	print "Exiting: The tag reporting-destination must be set";
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

$total = api_reports_total_call_attempts($groupIds, $start, $end, true);

if($total === false) {
	print "Exiting: there is an issue with the sql";
	exit;
}

print "Total: " . $total . "\n";
print "Sending email...\n";

$text = "Hello,\n\nTotal failed voice call attempts (this includes all calls with issues that stopped us from attempting the call): $total\n\n";

$email["to"] = $tags['reporting-destination'];
$email["subject"] = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " failed voice call attempts metrics for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = $text;
$email["htmlcontent"] = nl2br($text);
$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
