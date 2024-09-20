#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$start = date("Y-m-d 00:00:00", strtotime("yesterday"));
$end = date("Y-m-d 23:59:59", strtotime("yesterday"));

$sql = "SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` > ? AND `value` < ?";
$rs = api_db_query_read($sql, array("CAMPAIGNS", "created", strtotime($start), strtotime($end)));

$campaigns = array();

while(!$rs->EOF){

	if((api_campaigns_setting_getsingle($rs->Fields("id"), "groupowner") == 178) AND (api_campaigns_setting_getsingle($rs->Fields("id"), "type") == "phone")) $campaigns[] = $rs->Fields("id");

	$rs->MoveNext();
}

$file = "campaign,records,calls,answered,human,machine,iscustomer,notcustomer,invalidcontact,promise,transfer,callbacks\n";

foreach($campaigns as $campaignid){

	$file .= api_campaigns_setting_getsingle($campaignid, "name") . ",";

	$targets = api_data_target_status($campaignid);
	$call_results = api_data_target_results($campaignid);

	$sql = "SELECT `action`, `value`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? GROUP BY `action`, `value`";
	$rs = api_db_query_read($sql, array($campaignid));

	$response_data = array();

	if($rs)	while ($array = $rs->FetchRow()) $response_data[$array["action"]][$array["value"]] = $array["count"];

	if (!isset($response_data["1_OPTION"]["NOTCUSTOMER"])) $response_data["1_OPTION"]["NOTCUSTOMER"] = "";

	$file .=
		(isset($targets["TOTAL"]) ? $targets["TOTAL"] : '') . "," .
		(isset($call_results["GENERATED"]) ? $call_results["GENERATED"] : '') . "," .
		(isset($call_results["ANSWER"]) ? $call_results["ANSWER"] : '') . "," .
		(isset($response_data["0_AMD"]["HUMAN"]) ? $response_data["0_AMD"]["HUMAN"] : '') . "," .
		(isset($response_data["0_AMD"]["MACHINE"]) ? $response_data["0_AMD"]["MACHINE"] : '') . "," .
		(isset($response_data["1_OPTION"]["ISCUSTOMER"]) ? $response_data["1_OPTION"]["ISCUSTOMER"] : '') . "," .
		(isset($response_data["1_OPTION"]["NOTCUSTOMER"]) ? $response_data["1_OPTION"]["NOTCUSTOMER"] : '') . ",0," .
		(isset($response_data["2_OPTION"]["PROMISE"]) ? $response_data["2_OPTION"]["PROMISE"] : '') . "," .
		(isset($response_data["2_OPTION"]["TRANSFER"]) ? $response_data["2_OPTION"]["TRANSFER"] : '') . "," .
		(isset($response_data["CALLBACK"]["CALLBACK"]) ? $response_data["CALLBACK"]["CALLBACK"] : '') . "\n";
}

$email["to"]          = api_cron_tags_get(26, "reporting-destination");
$email["subject"]     = "[ReachTEL] BOQ Collections - Daily report - " . date("Y-m-d", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the daily campaign report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the daily campaign report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $file, "filename" => "BOQ-Collections-Daily-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);