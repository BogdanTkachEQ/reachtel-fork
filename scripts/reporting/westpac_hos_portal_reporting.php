#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$groupid = 375;
$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT * FROM `sms_out` WHERE `timestamp` > ? AND `timestamp` < ? AND `userid` IN (";
$variables = array($start, $end);

foreach(api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid)
if(api_security_check(120, null, true, $userid)){

	$sql .= "?, ";
	$variables[] = $userid;

	$userdata[$userid] = api_users_setting_getall($userid);

}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

$contents = "timestamp,username,from,destination,status,message\n";

while(!$rs->EOF){

        $sql = "SELECT `status` FROM `sms_out_status` WHERE `id` = ? ORDER BY `timestamp` DESC";
        $rs2 = api_db_query_read($sql, array($rs->Fields("id")));

        if($rs2->RecordCount() > 0) $status = $rs2->Fields("status");
        else $status = "SENT";

        $contents .= $rs->Fields("timestamp") . "," . $userdata[$rs->Fields("userid")]["username"] . "," . $rs->Fields("from") . "," . $rs->Fields("destination") . "," . $status . ",\"" . $rs->Fields("message") . "\"\n";

        $rs->MoveNext();

}

$email["to"]	      = api_cron_tags_get(46, "reporting-destination");
$email["subject"]     = "[ReachTEL] HOS portal SMS traffic report - " . date("d/m/Y", strtotime($start)) . " to " . date("d/m/Y", strtotime($end));
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL HOS portal SMS traffic report for the period between " . date("d/m/Y", strtotime($start)) . " and " . date("d/m/Y", strtotime($end)) . ".\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL HOS portal SMS traffic report for period between " . date("d/m/Y", strtotime($start)) . " and " . date("d/m/Y", strtotime($end)) . ".<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "ReachTEL-HOS-Portal-SMS-Traffic-" . date("Ymd") . ".csv");

api_email_template($email);