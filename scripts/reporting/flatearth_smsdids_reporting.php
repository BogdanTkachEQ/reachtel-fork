#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

$tags = api_cron_tags_get(80);

if(empty($tags["dids"])) {
    print "No DIDs to report on";
    exit;
}

if(empty($tags["reporting-destination"])) {
    print "No email to send report to";
    exit;
}

// Workout Last Week's Monday as Start Date and Sunday as End Date
$tmpDay = strtotime("- 7 days");
$startDateTimestamp = (date('w', $tmpDay) == 1) ? $tmpDay : strtotime('last monday', $tmpDay);
$startdate = date('Y-m-d', $startDateTimestamp);
$endDateTimestamp = strtotime('next sunday', $startDateTimestamp);
$enddate = date('Y-m-d', $endDateTimestamp);

$dids = array_map("trim", explode(",", $tags["dids"]));

$contents = "Timestamp, From, Content\n";
$entries = 0;

foreach($dids as $did) {
    
    $use = api_sms_dids_setting_getsingle($did, "use");
    $name = api_sms_dids_setting_getsingle($did, "name");
    
    $traffic = api_sms_dids_messagehistory($did, ['direction' => 'inbound', 'starttime' => date("Y-m-d 00:00:00", $startDateTimestamp), 'endtime' => date("Y-m-d 23:59:59", $endDateTimestamp)]);
    
    if(empty($traffic)) {
        print "No entries to return for '{$use}'\n";
        continue;
    }
    
    foreach($traffic as $message) {
        $contents .= $message["timestamp"]. "," . $message["number"]. "," . $message["contents"]. "\n";
        
        $entries++;
    }
}

$resultstring  = ""; 

$email = [];
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] Inbound SMS weekly report - " . date("d/m/Y", $startDateTimestamp);
$email["textcontent"] = "Hello,\n\nPlease find attached weekly report of inbound messages between " . date("d/m/Y", $startDateTimestamp) . " and " . date("d/m/Y", $endDateTimestamp) . "\n" . nl2br($resultstring);
$email["htmlcontent"] = "Hello,<br /><br />Please find attached weekely report of inbound messages between " . date("d/m/Y", $startDateTimestamp). " and " . date("d/m/Y", $endDateTimestamp) . "<br />" . $resultstring;
$email["attachments"][] = ["content" => $contents, "filename" => "ReachTEL-SMS-Weekly-report-" . date("Y-m-d", $startDateTimestamp) . ".csv"];

api_email_template($email);

print "Returned " . $entries . " rows.\n";


    