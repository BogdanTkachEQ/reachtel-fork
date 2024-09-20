#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(23);

$sql = "SELECT *  FROM `sms_out` WHERE `userid` = ? AND `timestamp` > ? AND `timestamp` < ?";
$rs = api_db_query_read($sql, array(126, date("Y-m-d 00:00:00", strtotime("yesterday")), date("Y-m-d 23:59:59", strtotime("yesterday"))));

if($rs->RecordCount() == 0) exit;

$contents =  "timestamp,destination,message,delivered,undelivered,unknown,expired\n";

while(!$rs->EOF){

	$sql = "SELECT `status`, `timestamp` FROM `sms_out_status` WHERE `id` = ?";
	$rs2 = api_db_query_read($sql, array($rs->Fields("id")));

	$events = $rs2->GetAssoc();

	$contents .= $rs->Fields("timestamp") . "," . $rs->Fields("destination") . ",\"" . $rs->Fields("message") . "\"," . (!empty($events["delivered"]) ? $events["delivered"] : "") . "," .  (!empty($events["undelivered"]) ? $events["undelivered"] : "") . "," . (!empty($events["unknown"]) ? $events["unknown"] : "") . "," . (!empty($events["expired"]) ? $events["expired"] : "") . "\n";

	$rs->MoveNext();
}

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] Baycorp Web Portal - Daily SMS response report - " . date("Y-m-d", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the SMS response report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the SMS response report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "BaycorpWebPortal-Daily-SMS-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);

print "Email Sent";
