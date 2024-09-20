<?php

/**
 * Report retrieves the total number of contacts the given groupids made to destinations this month
 * that exceed the min-contact-attempts amount (or 10 if not given)
 *
 * Used for ensuring excessive contacts aren't being made to people by customers
 *
 */
require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if(!isset($tags['groupids']) || !trim($tags['groupids'])) {
	print 'Failed because groupsids tag is not set or is empty';
	exit;
}

$minAttempts = isset($tags['min-contact-attempts']) ? $tags['min-contact-attempts'] : 10;

$groupIds = explode(',', $tags['groupids']);
if(empty($groupIds)){
	print "Group ids must be supplied, exiting\n";
	exit;
}

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

$total = api_reports_overall_contacts_per_destination($groupIds, $start, $end, $minAttempts);
if($total === false){
	print "Total contacts query has failed, exiting.\n";
	exit;
}

$totalContacts = count($total);

print "----BEGIN_CONTACT_ATTEMPT_RESULTS----\n";
if($totalContacts) {
	foreach ($total as $results) {
		print $results['cleaned_destination'] . " " . $results['total_attempts'] . "\n";
	}
}else{
	"NONE\n";
}
print "----END_CONTACT_ATTEMPT_RESULTS----\n";

print "Total contacts for group id(s) '".$tags['groupids']."': ". $totalContacts."\n";

print "Sending email...\n";

$text = "Hello,\n\nTotal number of destinations that were contacted by group id '".$tags['groupids']."' more than ".$minAttempts." times: $totalContacts.\n\n" ;
$text .= "This includes all generated calls and SMS's through all channels excluding those with issues that would have stopped us from connecting.";

print $text . "\n";

if(!isset($tags['reporting-destination'])) {
	print "Failed to send email since the tag reporting-destination is not set";
	exit;
}

$email["to"] = $tags['reporting-destination'];
$email["subject"] = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " voice call attempts metrics for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = $text;
$email["htmlcontent"] = nl2br($text);
$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
