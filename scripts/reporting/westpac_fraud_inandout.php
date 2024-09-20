#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);
$groupid = 384;

$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$stats = array("sent" => 0, "responses" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0);

foreach(api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid) if(api_security_check(120, null, true, $userid) OR api_security_check(125, null, true, $userid)) $userdata[$userid] = api_users_setting_getall($userid);

$sql = "SELECT UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `sms_account`, `from`, `contents` FROM `sms_received` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `sms_account` IN (?, ?, ?) ORDER BY `timestamp` ASC";
$rs = api_db_query_read($sql, array(strtotime($start), strtotime($end), 300, 318, 327));

while(!$rs->EOF){

	$stats["responses"]++;

	if(preg_match("/^yes(.|!)?$/i", $rs->Fields("contents")) OR preg_match("/^y$/i", $rs->Fields("contents"))) $stats["yesreplies"]++;
	else if(preg_match("/^no(.|!)?$/i", $rs->Fields("contents")) OR preg_match("/^n$/i", $rs->Fields("contents"))) $stats["noreplies"]++;
	else if(preg_match("/^fraud(.|!)?$/i", $rs->Fields("contents")) OR preg_match("/^n$/i", $rs->Fields("contents"))) $stats["fraudreplies"]++;

	$messages[$rs->Fields("timestamp")][] = array("direction" => "received", "timestamp" => date("Y-m-d H:i:s", $rs->Fields("timestamp")), "username" => null, "from" => api_data_format($rs->Fields("from"), "sms"), "destination" => api_data_format(api_sms_dids_setting_getsingle($rs->Fields("sms_account"), "name"), "sms"), "message" => $rs->Fields("contents"), "status" => null);

	$rs->MoveNext();

}

$sql = "SELECT `id`, UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `userid`, `from`, `destination`, `message` FROM `sms_out` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `userid` IN (";
$variables = array(strtotime($start), strtotime($end));

foreach($userdata as $userid => $user){

	$sql .= "?, ";
	$variables[] = $userid;

}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

while(!$rs->EOF){

	$stats["sent"]++;

	$sql = "SELECT `status` FROM `sms_out_status` WHERE `id` = ? ORDER BY `timestamp` DESC";
	$rs2 = api_db_query_read($sql, array($rs->Fields("id")));

	if($rs2->RecordCount() > 0) $status = strtolower($rs2->Fields("status"));
	else $status = "sent";

	$messages[$rs->Fields("timestamp")][] = array("direction" => "sent", "timestamp" => date("Y-m-d H:i:s", $rs->Fields("timestamp")), "username" => $userdata[$rs->Fields("userid")]["username"], "from" => api_data_format($rs->Fields("from"), "sms"), "destination" => api_data_format($rs->Fields("destination"), "sms"), "message" => $rs->Fields("message"), "status" => $status);

	$rs->MoveNext();

}

$sql = "SELECT `sms_api_mapping`.`rid`, UNIX_TIMESTAMP(`sms_api_mapping`.`timestamp`) as `timestamp`, `sms_api_mapping`.`userid`, `sms_sent`.`sms_account`, `sms_sent`.`to`, `sms_sent`.`contents` FROM `sms_api_mapping`, `sms_sent` WHERE `sms_api_mapping`.`rid` = `sms_sent`.`eventid` AND `sms_api_mapping`.`timestamp` > FROM_UNIXTIME(?) AND `sms_api_mapping`.`timestamp` < FROM_UNIXTIME(?) AND `sms_api_mapping`.`userid` IN (";
$variables = array(strtotime($start), strtotime($end));

foreach($userdata as $userid => $user){

	$sql .= "?, ";
	$variables[] = $userid;

}

$sql = substr($sql, 0, -2) . ") ORDER BY `sms_api_mapping`.`timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

while(!$rs->EOF){

	$stats["sent"]++;

	$sql = "SELECT `status` FROM `sms_status` WHERE `eventid` = ? ORDER BY `timestamp` DESC";
	$rs2 = api_db_query_read($sql, array($rs->Fields("rid")));

	if($rs2->RecordCount() > 0) $status = strtolower($rs2->Fields("status"));
	else $status = "sent";

	$messages[$rs->Fields("timestamp")][] = array("direction" => "sent", "timestamp" => date("Y-m-d H:i:s", $rs->Fields("timestamp")), "username" => $userdata[$rs->Fields("userid")]["username"], "from" => api_data_format(api_sms_dids_setting_getsingle($rs->Fields("sms_account"), "name"), "sms"), "destination" => api_data_format($rs->Fields("to"), "sms"), "message" => $rs->Fields("contents"), "status" => $status);

	$rs->MoveNext();

}

ksort($messages);
$contents = "timestamp,direction,username,from,destination,status,message\n";

foreach($messages as $timestamp => $messagesAtTimestamp) foreach($messagesAtTimestamp as $message) $contents .= $message["timestamp"] . "," . $message["direction"] . "," . $message["username"] . "," . $message["from"] . "," . $message["destination"] . "," . $message["status"] . ",\"" . $message["message"] . "\"\n";

$email["to"]	      = api_cron_tags_get(43, "reporting-destination");
$email["subject"]     = "[ReachTEL] SMS traffic report - " . date("H:i:s d/m/Y", strtotime($start)) . " to " . date("H:i:s d/m/Y", strtotime($end));
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL SMS traffic report for the period between " . date("H:i:s d/m/Y", strtotime($start)) . " and " . date("H:i:s d/m/Y", strtotime($end)) . ".\n\nSMS sent: " . $stats["sent"] . "\nResponses: " . $stats["responses"] . "\nYes replies: " . $stats["yesreplies"] . "\nNo replies: " . $stats["noreplies"] . "\nFraud replies: " . $stats["fraudreplies"] . "\nResponse rate: " . sprintf("%01.1f", ($stats["responses"] / $stats["sent"])*100) . "%";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL SMS traffic report for period between " . date("H:i:s d/m/Y", strtotime($start)) . " and " . date("H:i:s d/m/Y", strtotime($end)) . ".<br /><br />SMS sent: " . $stats["sent"] . "\nResponses: " . $stats["responses"] . "\nYes replies: " . $stats["yesreplies"] . "\nNo replies: " . $stats["noreplies"] . "\nFraud replies: " . $stats["fraudreplies"] . "\nResponse rate: " . sprintf("%01.1f", ($stats["responses"] / $stats["sent"])*100) . "%";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "ReachTEL-SMS-Traffic-" . date("Ymd-His") . ".csv");

api_email_template($email);