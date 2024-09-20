#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(84);

if (!isset($tags['reporting-smsdids'])) {
	print "ERROR: Cron tag 'reporting-smsdids' is missing or empty.\n";
	exit;
}

if (!isset($tags['reporting-userid'])) {
	print "ERROR: Cron tag 'reporting-userid' is missing or empty.\n";
	exit;
}

$username = api_users_setting_getsingle($tags['reporting-userid'], 'username');
if (!$username) {
	print "ERROR: Username for user id #{$tags['reporting-userid']} could not be found.\n";
	exit;
}

$smsdids = explode(',', $tags['reporting-smsdids']);

$time = [
	'start' => date("Y-m-d 00:00:00", strtotime("yesterday")),
	'finish' => date("Y-m-d 23:59:9", strtotime("yesterday")),
];

$messages = [];
$sql = "SELECT sr.*, ks.`value` destination FROM `sms_received` sr JOIN `key_store` ks ON sr.`sms_account` = ks.`id` WHERE sr.`sms_account` IN (" . implode(', ', array_fill(0, count($smsdids), '?')) . ") AND sr.`timestamp` BETWEEN ? AND ? AND ks.`type` = 'SMSDIDS' AND ks.`item` = 'name'";
$rs = api_db_query_read($sql, array_merge($smsdids, array($time["start"], $time["finish"])));

if ($rs->RecordCount() > 0) {
	while($message = $rs->FetchRow()) {
		$messages[strtotime($message["timestamp"])][] = [
			'direction' => 'inbound',
			'timestamp' => $message['timestamp'],
			'user' => 'customer',
			'source' => $message['from'],
			'destination' => $message['destination'],
			'content' => $message['contents'],
		];
	}
} else {
	print "No results have been found in `sms_received` for SMS DIDS: {$tags['reporting-smsdids']}.\n";
}

$sql = "SELECT * FROM `sms_out` WHERE `timestamp` BETWEEN ? AND ? AND `userid` = ?;";
$rs = api_db_query_read($sql, [ $time["start"], $time["finish"], $tags['reporting-userid'] ]);

if ($rs->RecordCount() > 0) {
	while($message = $rs->FetchRow()) {
		$messages[strtotime($message["timestamp"])][] = [
			'direction' => 'outbound',
			'timestamp' => $message['timestamp'],
			'user' => $username,
			'source' => (strpos($message['from'], '04') === 0 ? '61'.substr($message['from'], 1) : $message['from']),
			'destination' => $message['destination'],
			'content' => $message['message'],
		];
	}
} else {
	print "No results have been found in `sms_out` for user {$username} (id={$tags['reporting-userid']}).\n";
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
$email["subject"]     = "[ReachTEL] Daily inbound SMS report - Commercial Credit Control - " . date("Y-m-d");
$email["textcontent"] = "Hello,\n\nPlease find attached the inbound SMS traffic report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the inbound SMS traffic report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "CCC-trafficreport-" . date("Ymd") . ".csv");

api_email_template($email);

print "Email Sent";
