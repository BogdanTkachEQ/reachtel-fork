<?php
// This report lists the count of specific sms responses received
//(Responses that need to be filtered can be passed via tags)
require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if (array_diff(['did-name', 'sms-response'], array_keys($tags))) {
    print "Exiting since required tags [did-name, sms-response] not set";
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

$responses = array_unique(explode(',', $tags['sms-response']));

try {
    $count = api_reports_get_sms_responses_count($tags['did-name'], $responses, $start, $end);
} catch (Exception $exception) {
    print $exception->getMessage();
    exit;
}

if ($count === false) {
    print "Exiting since there is some issue with the sql";
    exit;
}

print 'Total sms: ' . $count . "\n";

print "Sending email...\n";

if (array_diff(['reporting-destination', 'subject'], array_keys($tags))) {
    print "Failed to send email since the tags reporting-destination and subject are not set";
    exit;
}

$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " sms responses for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = "Hello,\n\nTotal SMS responses for did " . $tags['did-name']  . " with responses " . $tags['sms-response'] . ": $count\n";
$email["htmlcontent"] = "Hello,<br /><br />Total SMS responses for did " . $tags['did-name']  . " with responses " . $tags['sms-response'] . ": $count<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
