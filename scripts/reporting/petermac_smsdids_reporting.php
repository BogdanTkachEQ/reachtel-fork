#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(100);

if(empty($tags["dids"])) {
    print "No DIDs to report on";
    exit;
}

if(empty($tags["reporting-destination"])) {
    print "No email to send report to";
    exit;
}

$date = "yesterday";
if(isset($tags["date"])) {
	$date = $tags["date"];
}

$date = date('Y-m-d', strtotime($date));
$startdate = "{$date} 00:00:00";
$enddate = "{$date} 23:59:59";

$dids = array_map("trim", explode(",", $tags["dids"]));

$contents = "Timestamp, From, Content\n";
$entries = 0;

foreach($dids as $did) {

    $use = api_sms_dids_setting_getsingle($did, "use");
    $name = api_sms_dids_setting_getsingle($did, "name");

    $traffic = api_sms_dids_messagehistory($did, ['direction' => 'inbound', 'starttime' => $startdate, 'endtime' => $enddate]);

    if(empty($traffic)) {
        print "No entries to return for '{$use}'\n";
        continue;
    }

    foreach($traffic as $message) {
        $contents .= $message["timestamp"]. "," . $message["number"]. "," . $message["contents"]. "\n";

        $entries++;
    }
}

if(!$entries) {
	print "Empty report, not sending email.";
	exit;
}

$email = [];
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] Inbound SMS daily report - " . date("d/m/Y");
$email["textcontent"] = "Hello,\n\nPlease find attached daily report of inbound messages.\n";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["attachments"][] = ["content" => $contents, "filename" => "ReachTEL-SMS-Daily-report-" . date("Y-m-d") . ".csv"];

api_email_template($email);

print "Returned " . $entries . " rows.\n";
