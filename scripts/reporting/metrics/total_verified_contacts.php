<?php
/** This report lists the count of specific voice responses and all delivered sms's received
 *(Responses that need to be filtered can be passed via tags)
 */
/* $tags = [
	'action-2_DEBTOPTIONS'=>'1_PTP48HOURS,2_PTP7DAYS',
	'action-1_DEBTOPTIONS'=>'xyz',
	'min-contact-attempts'=>10,
	'groupids'=>"664,138"];
*/

require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if (!isset($tags['groupids']) || !trim($tags['groupids'])) {
	print 'Failed because groupids tag is not set or is empty';
	exit;
}

$minAttempts = isset($tags['min-contact-attempts']) ? $tags['min-contact-attempts'] : 10;
$maxActions = 3;

// Build the void call actions array
$i = 0;
$actions = [];
foreach ($tags as $name => $value) {
	if ($i === 3) {
		print "Could not proceed execution since number of actions are more than expected " . $maxActions;
		exit;
	}
	if (strpos($name, 'action-') === 0) {
		if (!trim($value)) {
			print "Could not proceed execution since the value for action is empty";
			exit;
		}
		$actions[] = ['action' => substr($name, 7), 'value' => explode(',', $value)];
		$i++;
	}
}

if (!$i) {
	print "Could not proceed since there are no actions set in tags";
	exit;
}

$groupIds = explode(',', $tags['groupids']);

if (isset($tags['run-date'])) {
	try {
		$start = new DateTime($tags['run-date']);
	} catch (Exception $e) {
		print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
} else {
	$start = new DateTime('First day of last month Midnight');
}

if (isset($tags['end-run-date'])) {
	try {
		$end = new DateTime($tags['end-run-date']);
	} catch (Exception $e) {
		print "Invalid end run date given:  '" . $tags['end-run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
} else {
	$end = new DateTime('Last day of last month 23:59:59');
}

print "Running from " . $start->format(DATE_ATOM) . " to " . $end->format(DATE_ATOM) . "\n";

$total = api_reports_overall_actioned_contacts_per_destination($groupIds, $start, $end, $minAttempts, $actions);

if ($total === false) {
	print "Exiting since there is some issue with the sql";
	exit;
}

$totalContacts = count($total);

print "----BEGIN_VERIFIED_CONTACT_ATTEMPT_RESULTS----\n";
if ($totalContacts > 0) {
	foreach ($total as $results) {
		print $results['cleaned_destination'] . " " . $results['total_attempts'] . "\n";
	}
} else {
	print "NONE\n";
}
print "----END_VERIFIED_CONTACT_ATTEMPT_RESULTS----\n";

print "Total verified contacts for group id(s) '" . $tags['groupids'] . "' with more than {$minAttempts} contacts: " . $totalContacts . "\n";

print "Sending email...\n";

$text = "Hello,\n\nTotal number of destinations that contacted and verified by group id(s) '" . $tags['groupids'] . "' more than " . $minAttempts . " times: $totalContacts.\n\n";
$text .= "This includes all generated calls in which the receiver verified themselves, and all SMS's through all channels marked as delivered.";

print $text . "\n";

if (!isset($tags['reporting-destination'])) {
	print "Failed to send email since the tag reporting-destination is not set";
	exit;
}

$email["to"] = $tags['reporting-destination'];
$email["subject"] = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " voice call attempts metrics for date between " . $start->format(
		'd-m-Y H:i:s'
	) . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = $text;
$email["htmlcontent"] = nl2br($text);
$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';

