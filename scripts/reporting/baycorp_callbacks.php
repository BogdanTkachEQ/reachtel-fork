#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT * FROM `response_data` WHERE `action` = ? AND `timestamp` > ? AND `timestamp` < ?";
$rs = api_db_query_read($sql, array("CALLBACK", $start, $end));

if($rs->RecordCount() == 0) exit;

$groupowner = array();

$elements = array();

while(!$rs->EOF){

	if(!isset($groupowner[$rs->Fields("campaignid")])) $groupowner[$rs->Fields("campaignid")] = api_campaigns_setting_getsingle($rs->Fields("campaignid"), "groupowner");

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

if(empty($response)) exit;

foreach($response as $sms => $value) {
        $contents .= "\"" . $value["targetkey"] . "\",\"" . $value["destination"] . "\",\"" . $value["campaign"] . "\",\"" . $value["timestamp"] . "\",";
        if($elements) {
        	foreach($elements as $k => $v) {
        		if(isset($value["merge_data"][$k])) $contents .= "\"" . $value["merge_data"][$k] . "\"";

        		$contents .= ",";
        	}
        }
        $contents .= "\n";
}

$email["to"]          = api_cron_tags_get(21, "reporting-destination");
$email["subject"]     = "[ReachTEL] Baycorp - Daily Callback report - " . date("Y-m-d", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the callback report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the callback report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "Baycorp-Daily-Callback-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);