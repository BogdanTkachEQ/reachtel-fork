<?php
// This report lists the count of specific voice responses received
//(Responses that need to be filtered can be passed via tags)
require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if (!isset($tags['groupids']) || !trim($tags['groupids'])) {
    print 'Failed because groupsids tag is not set or is empty';
    exit;
}

$actions = [];

$maxActions = 3;
$i = 0;

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

$total = api_reports_get_voice_responses_count($groupIds, $actions, $start, $end);

if ($total === false) {
    print "Exiting since there is some issue with the sql";
    exit;
}

print "Total: " . $total . "\n";

print "Sending email...\n";

if (!isset($tags['reporting-destination'])) {
    print "Failed to send email since the tag reporting-destination is not set";
    exit;
}

$content = "Following are the action value pairs based on which the metrics is generated.\n";
foreach ($actions as $action) {
    $content .= $action['action'] . ' : ' . implode(',', $action['value']) . "\n";
}
$content .= "\n\n";

$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] " . (isset($tags['subject']) ? $tags['subject'] : '') . " voice response metrics for date between " . $start->format('d-m-Y H:i:s') . ' and ' . $end->format('d-m-Y H:i:s');
$email["textcontent"] = "Hello,\n" . $content . "Total contacts: $total\n";
$email["htmlcontent"] = "Hello,\n" . $content . "Total contacts: $total<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

api_email_template($email);

print 'Email Sent';
