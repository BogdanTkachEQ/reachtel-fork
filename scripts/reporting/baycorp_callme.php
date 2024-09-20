#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$campaignid = api_campaigns_checknameexists("Baycorp-" . date("Fy", strtotime("yesterday")) . "-CallMe");

if($campaignid == false) exit;

$sql = "SELECT * FROM `response_data` WHERE `campaignid` = ? AND `timestamp` > ? AND `timestamp` < ?";
$rs = api_db_query_read($sql, array($campaignid, $start, $end));

if($rs->RecordCount() == 0) exit;

$groupowner = array();

while(!$rs->EOF){

	if(empty($groupowner[$rs->Fields("campaignid")])) $groupowner[$rs->Fields("campaignid")] = api_campaigns_setting_getsingle($rs->Fields("campaignid"), "groupowner");

	if($groupowner[$rs->Fields("campaignid")] == 12){

	        $target = api_targets_getinfo($rs->Fields("targetid"));

	        if($target != FALSE){

	                $md = api_data_merge_get_all($target["campaignid"], $target["targetkey"]);
	                if($md) foreach($md as $element => $value) $mde[$element] = 1;

	                $rd = api_data_responses_getall($target["targetid"]);
	                if($rd) foreach($rd as $element => $value) $rde[$element] = 1;

	                $response[$target["targetid"]] = $target;
			$response[$target["targetid"]]["merge_data"] = $md;
			$response[$target["targetid"]]["response_data"] = $rd;

	        }
	}


        $rs->MoveNext();
}

$contents =  "\"destination\",";

if($mde) foreach($mde as $key => $value) $contents .= "\"" . $key . "\",";
if($rde) foreach($rde as $key => $value) $contents .= "\"" . $key . "\",";

$contents .= "\n";

if($response == null) exit;

foreach($response as $sms => $value) {
        $contents .= "\"" . $value["destination"] . "\",";

	if($mde)
        foreach($mde as $k => $v) {
		if(isset($value["merge_data"][$k])) $contents .= "\"" . $value["merge_data"][$k] . "\",";
		else $contents .= ",";
	}

	if($rde)
        foreach($rde as $k => $v) {
		if(isset($value["response_data"][$k])) $contents .= "\"" . $value["response_data"][$k] . "\",";
		else $contents .= ",";
	}
        $contents .= "\n";
}

$email["to"]          = api_cron_tags_get(22, "reporting-destination");
$email["subject"]     = "[ReachTEL] Baycorp - Daily CALLME and Handset Delivery report - " . date("Y-m-d", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the CALLME and handset delivery report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the CALLME and handset delivery report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "Baycorp-Daily-CALLME-handsetdelivery-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);