#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);
$groupid = 384;

$hour = date("H");

if(isset($_SERVER['HTTP_HOST'])){

	if(empty($_SERVER['HTTPS']) OR ($_SERVER['HTTPS'] != "on")){

    	header("Location: https://scripts.reachtel.com.au");
    	exit;

	}

	$start = date("Y-m-d H:i:s", time() - 3600);
	$end = date("Y-m-d H:i:s", time());

} else if($hour == 17){

	$start = date("Y-m-d 12:00:00");
	$end = date("Y-m-d 16:59:59");

} else if($hour == 12){

	$start = date("Y-m-d 07:00:00");
	$end = date("Y-m-d 11:59:59");

} else if($hour == 7) {

	$start = date("Y-m-d 17:00:00", time() - 86400);
	$end = date("Y-m-d 06:59:59");

} else if((date("G") == 4) AND (date("N") == 1)){ // Monday at 4am

        $start = date("Y-m-d 00:00:00", strtotime("last monday"));
        $end = date("Y-m-d 23:59:59", strtotime("yesterday"));

} else exit;

$messages = array();

foreach(api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid) if(api_security_check(120, null, true, $userid) OR api_security_check(125, null, true, $userid)) $userdata[$userid] = api_users_setting_getall($userid);

// Get the received messages

$sql = "SELECT UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `sms_account`, `from`, `contents` FROM `sms_received` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `sms_account` IN (?, ?, ?) ORDER BY `timestamp` ASC";
$rs = api_db_query_read($sql, array(strtotime($start), strtotime($end), 300, 318, 327));

while(!$rs->EOF){

	$messages[$rs->Fields("timestamp")][] = array("direction" => "received", "username" => null, "from" => api_data_format($rs->Fields("from"), "sms"), "destination" => api_data_format(api_sms_dids_setting_getsingle($rs->Fields("sms_account"), "name"), "sms"), "message" => $rs->Fields("contents"), "status" => "received");

	$rs->MoveNext();

}

// Get the API messages

$sql = "SELECT `id`, UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `userid`, `from`, `destination`, `message` FROM `sms_out` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `userid` IN (";
$variables = array(strtotime($start), strtotime($end));

foreach($userdata as $userid => $user){

	$sql .= "?, ";
	$variables[] = $userid;

}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

while(!$rs->EOF){

        $sql = "SELECT `status` FROM `sms_out_status` WHERE `id` = ? ORDER BY `timestamp` DESC";
        $rs2 = api_db_query_read($sql, array($rs->Fields("id")));

        if($rs2->RecordCount() > 0) $status = $rs2->Fields("status");
        else $status = "SENT";

        $messages[$rs->Fields("timestamp")][] = array("username" => $userdata[$rs->Fields("userid")]["username"], "from" => $rs->Fields("from"), "destination" => $rs->Fields("destination"), "status" => $status, "message" => $rs->Fields("message"));

        $rs->MoveNext();

}

// Get the email to SMS messages

$sql = "SELECT `sms_api_mapping`.`rid`, UNIX_TIMESTAMP(`sms_api_mapping`.`timestamp`) as `timestamp`, `sms_api_mapping`.`userid`, `sms_sent`.`sms_account`, `sms_sent`.`to`, `sms_sent`.`contents` FROM `sms_api_mapping`, `sms_sent` WHERE `sms_api_mapping`.`rid` = `sms_sent`.`eventid` AND `sms_api_mapping`.`timestamp` > FROM_UNIXTIME(?) AND `sms_api_mapping`.`timestamp` < FROM_UNIXTIME(?) AND `sms_api_mapping`.`userid` IN (";
$variables = array(strtotime($start), strtotime($end));

foreach($userdata as $userid => $user){

	$sql .= "?, ";
	$variables[] = $userid;

}

$sql = substr($sql, 0, -2) . ") ORDER BY `sms_api_mapping`.`timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

while(!$rs->EOF){

	$sql = "SELECT `status` FROM `sms_status` WHERE `eventid` = ? ORDER BY `timestamp` DESC";
	$rs2 = api_db_query_read($sql, array($rs->Fields("rid")));

	if($rs2->RecordCount() > 0) $status = strtolower($rs2->Fields("status"));
	else $status = "sent";

	$messages[$rs->Fields("timestamp")][] = array("direction" => "sent", "username" => $userdata[$rs->Fields("userid")]["username"], "from" => api_data_format(api_sms_dids_setting_getsingle($rs->Fields("sms_account"), "name"), "sms"), "destination" => api_data_format($rs->Fields("to"), "sms"), "message" => $rs->Fields("contents"), "status" => $status);

	$rs->MoveNext();

}

ksort($messages);
$contents = "timestamp,Day/Hour,username,from,destination,Count,status,message\n";

$duplicatemessagecache = array();

foreach($messages as $timestamp => $messagesAtTimestamp) foreach($messagesAtTimestamp as $message) {

	if(isset($duplicatemessagecache[$message["destination"]][$message["message"]])) $previousdestinationmatch = "1";
	else $previousdestinationmatch = "0";

	$duplicatemessagecache[$message["destination"]][$message["message"]] = true;

	$contents .= date("Y-m-d H:i:s", $timestamp) . ",\"" . date("D, H", strtotime($timestamp)) .  "\"," . $message["username"] . "," . $message["from"] . "," . $message["destination"] . "," . $previousdestinationmatch . "," . $message["status"] . ",\"" . $message["message"] . "\"\n";

}

$email["to"]	      = api_cron_tags_get(45, "reporting-destination");
$email["subject"]     = "[ReachTEL] SMS traffic report - " . date("H:i:s d/m/Y", strtotime($start)) . " to " . date("H:i:s d/m/Y", strtotime($end));
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL SMS traffic report for the period between " . date("H:i:s d/m/Y", strtotime($start)) . " and " . date("H:i:s d/m/Y", strtotime($end)) . ".\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL SMS traffic report for period between " . date("H:i:s d/m/Y", strtotime($start)) . " and " . date("H:i:s d/m/Y", strtotime($end)) . ".<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "ReachTEL-SMS-Traffic-" . date("Ymd-His") . ".csv");

api_email_template($email);

if(isset($_SERVER['HTTP_HOST'])){

	print "Report sent.";
	exit;

}