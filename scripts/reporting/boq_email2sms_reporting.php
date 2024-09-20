#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$groupid = 178;
$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT * FROM `sms_api_mapping` WHERE `timestamp` > ? AND `timestamp` < ? AND `userid` IN (";
$variables = array($start, $end);

foreach(api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid)
if(api_security_check(125, null, true, $userid)){

	$sql .= "?, ";
	$variables[] = $userid;

	$userdata[$userid] = api_users_setting_getall($userid);

}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

$contents = "timestamp,destination,sender,status,message\n";

while(!$rs->EOF){

        $sql = "SELECT * FROM `sms_sent` WHERE `eventid` = ?";
        $rs3 = api_db_query_read($sql, array($rs->Fields("rid")));

        $sql = "SELECT `status` FROM `sms_status` WHERE `eventid` = ? ORDER BY `timestamp` DESC";
        $rs2 = api_db_query_read($sql, array($rs->Fields("rid")));

        if($rs2->RecordCount() > 0) $status = $rs2->Fields("status");
        else $status = "SENT";

        $from = api_data_numberformat($rs3->Fields("to"));

        $contents .= $rs->Fields("timestamp") . "," . $from["fnn"] . "," . $userdata[$rs->Fields("userid")]["emailaddress"] . "," . $status . ",\"" . $rs3->Fields("contents") . "\"\n";

        $rs->MoveNext();

}

$email["to"]          = api_cron_tags_get(28, "reporting-destination");
$email["subject"]     = "[ReachTEL] Email-2-SMS - Weekly report - " . date("d/m/Y", strtotime($start)) . " to " . date("d/m/Y", strtotime($end));
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL Email-2-SMS report for the period between " . date("d/m/Y", strtotime($start)) . " and " . date("d/m/Y", strtotime($end)) . ".\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL Email-2-SMS report for period between " . date("d/m/Y", strtotime($start)) . " and " . date("d/m/Y", strtotime($end)) . ".<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "ReachTEL-Email2SMS-" . date("Ymd") . ".csv");

api_email_template($email);