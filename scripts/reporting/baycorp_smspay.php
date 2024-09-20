#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT * FROM `response_data` WHERE `action` = ? AND `timestamp` > ? AND `timestamp` < ?";
$rs = api_db_query_read($sql, array("PAY", $start, $end));

if($rs->RecordCount() == 0) exit;

while(!$rs->EOF){

	if(!is_integer($groupowner[$rs->Fields("campaignid")])) $groupowner[$rs->Fields("campaignid")] = api_campaigns_setting_getsingle($rs->Fields("campaignid"), "groupowner");

	if($groupowner[$rs->Fields("campaignid")] == 12){

	        $target = api_targets_getinfo($rs->Fields("targetid"));

	        if($target != FALSE){

	                $md = api_data_merge_get_all($target["campaignid"], $target["targetkey"]);
	                if($md) foreach($md as $element => $value) $elements[$element] = 1;

	                $response[$target["targetid"]] = $target;
			$response[$target["targetid"]]["merge_data"] = $md;
			$response[$target["targetid"]]["campaign"] = api_campaigns_setting_getsingle($rs->Fields("campaignid"), "name");
			$response[$target["targetid"]]["timestamp"] = $rs->Fields("timestamp");

	        }
	}


        $rs->MoveNext();
}

$contents =  "\"targetkey\",\"destination\",\"campaign\",\"timestamp\",";

if($elements) foreach($elements as $key => $value) $contents .= "\"" . $key . "\",";

$contents .= "\n";

if($response == null) exit;

foreach($response as $sms => $value) {
        $contents .= "\"" . $value["targetkey"] . "\",\"" . $value["destination"] . "\",\"" . $value["campaign"] . "\",\"" . $value["timestamp"] . "\",";
        if($elements) foreach($elements as $k => $v) $contents .= "\"" . $value["merge_data"][$k] . "\",";
        $contents .= "\n";
}

$email["to"]          = api_cron_tags_get(24, "reporting-destination");
$email["subject"]     = "[ReachTEL] Baycorp - Daily SMS PAY report - " . date("Y-m-d", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the SMS PAY report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the SMS PAY report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "Baycorp-Daily-SMSPAY-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);