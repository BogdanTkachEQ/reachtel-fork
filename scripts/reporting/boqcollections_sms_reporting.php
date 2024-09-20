#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(72);

$smsdids = [
	711 => '61488822634',
	712 => '61488822635',
	713 => '61488822636',
];

$time = [
	'start' => date("Y-m-d 00:00:00", strtotime("yesterday")),
	'finish' => date("Y-m-d 23:59:9", strtotime("yesterday")),
];

$messages = [];

$sql = "SELECT * FROM `sms_received` WHERE `sms_account` IN (?, ?, ?) AND `timestamp` >= ? AND `timestamp` <= ?";
$rs = api_db_query_read($sql, array(711, 712, 713, $time["start"], $time["finish"]));

while($message = $rs->FetchRow()) {
	$messages[strtotime($message["timestamp"])][] = [
		'direction' => 'inbound',
		'timestamp' => $message['timestamp'],
		'user' => 'customer',
		'source' => $message['from'],
		'destination' => $smsdids[$message['sms_account']],
		'content' => $message['contents'],
	];
}

$sql = "SELECT * FROM `sms_out` WHERE `timestamp` >= ? AND `timestamp` <= ? AND `userid` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` = ?)";
$rs = api_db_query_read($sql, [ $time["start"], $time["finish"], "USERS", "groupowner", "178" ]);

while($message = $rs->FetchRow()) {
	$messages[strtotime($message["timestamp"])][] = [
		'direction' => 'outbound',
		'timestamp' => $message['timestamp'],
		'user' => api_users_setting_getsingle($message['userid'], "username"),
		'source' => $message['from'],
		'destination' => $message['destination'],
		'content' => $message['message'],
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
$email["subject"]     = "[ReachTEL] Daily SMS traffic report - BOQ Collections - " . date("Y-m-d");
$email["textcontent"] = "Hello,\n\nPlease find attached the SMS traffic report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the SMS traffic report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "BOQCollections-trafficreport-" . date("Ymd") . ".csv");

api_email_template($email);