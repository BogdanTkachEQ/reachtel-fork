#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(83);

$smsdids = explode(',', $tags['reporting-smsdids']);

$time = [
	'start' => date("Y-m-d 00:00:00", strtotime("yesterday")),
	'finish' => date("Y-m-d 23:59:9", strtotime("yesterday")),
];

$messages = [];
$sql = "SELECT * FROM `sms_received` WHERE `sms_account` IN (" . implode(', ', array_fill(0, count($smsdids), '?')) . ") AND `timestamp` >= ? AND `timestamp` <= ?";
$rs = api_db_query_read($sql, array_merge($smsdids, array($time["start"], $time["finish"])));

while($message = $rs->FetchRow()) {
	$messages[strtotime($message["timestamp"])][] = [
		'direction' => 'inbound',
		'timestamp' => $message['timestamp'],
		'user' => 'customer',
		'source' => $message['from'],
		'destination' => $message['sms_account'],
		'content' => $message['contents'],
	];
}

if(empty($messages) OR count($messages) == 0) {
	print "No records to export\n";
	exit;
}

ksort($messages);

$contents = "direction,timestamp,user,source,destination,content\n";

foreach($messages as $timestamp) foreach($timestamp as $message) $contents .= $message["direction"] . "," . $message["timestamp"] . "," . $message["user"] . "," . $message["source"] . "," . $message["destination"] . ",\"" . $message["content"] . "\"\n";

$email = [];

$email["to"]          = $tags['reporting-destination'];
$email["subject"]     = "[ReachTEL] Daily inbound SMS report - CBUS - " . date("Y-m-d");
$email["textcontent"] = "Hello,\n\nPlease find attached the inbound SMS traffic report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the inbound SMS traffic report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "CBUS-trafficreport-" . date("Ymd") . ".csv");

api_email_template($email);
