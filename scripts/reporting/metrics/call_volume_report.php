<?php

require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

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

$groupIds = isset($tags['group-ids']) ? explode(',', $tags['group-ids']) : [];
$totalAnsweredCall = api_reports_overall_answered_call_volume($groupIds, $start, $end, false);
$totalDuration = api_reports_get_total_call_duration($groupIds, $start, $end);

$data = [
    ['Total number of calls answered', $totalAnsweredCall],
    ['Total billable outbound seconds', $totalDuration['outbound']['billable']],
    ['Total billable callback seconds', $totalDuration['callback']['billable']],
    ['Total raw outbound seconds', $totalDuration['outbound']['raw']],
    ['Total raw callback seconds', $totalDuration['callback']['raw']],
];
print "Generating csv\n";
$contents = api_csv_string($data);

$filename = "ReachTEL-Call-Volume-Report-" . $start->format('Fy') . ".csv";

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] Call volume report";
$email["textcontent"] = "Hello,\nPlease find attached the call volume report for the time period " . $start->format(DATE_ATOM) . ' until ' . $end->format(DATE_ATOM);
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => $filename);

print "Sending report as email\n";
api_email_template($email);

print "Report sent";
