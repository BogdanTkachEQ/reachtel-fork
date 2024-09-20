#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

//$time = strtotime("2013-10-01");
//$time = time();

$sql = "SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` > ?";
$rs = api_db_query_read($sql, array("CAMPAIGNS", "created", strtotime('first day of previous month midnight')));

$campaigns = array();

while(!$rs->EOF){

	if((api_campaigns_setting_getsingle($rs->Fields("id"), "groupowner") == 178) AND (api_campaigns_setting_getsingle($rs->Fields("id"), "type") == "phone")) $campaigns[] = $rs->Fields("id");

	$rs->MoveNext();
}

$file = "campaign,contracts,contacts,workable_contracts,workable_contacts,penetration_rate,connect_rate,right_party_rate,live_transfer_rate,callback_rate,total_transfer_rate,ptp,ptp_rate,sms\n";

foreach($campaigns as $campaignid){

	$file .= api_campaigns_setting_getsingle($campaignid, "name") . ",";

	$report = api_data_responses_phone_report($campaignid);

	if ($report === false) {
		$report = [];
	}

	$row = array("contracts" => array(), "workable_contracts" => array(), "workable_contacts" => array(), "responses" => array(), "contacts" => array(), "penetration_rate" => array(), "connect_rate" => array(), "right_party_rate" => array(), "live_transfer" => array(), "callback_rate" => array(), "total_transfer" => array(), "ptp_rate" => array(), "ptp" => 0, "sms" => 0);

	foreach($report as $targetid => $results){

		$targetkey = $results["targetkey"];

		if(!in_array($targetkey, $row["contracts"])) $row["contracts"][] = $targetkey;
		$row["contacts"][] = $targetid;

		if(!in_array($targetkey, $row["workable_contracts"]) AND empty($results["response_data"]["REMOVED"]) AND empty($results["response_data"]["DUPLICATE"])) $row["workable_contracts"][] = $targetkey;
		if(!in_array($targetid, $row["workable_contacts"]) AND empty($results["response_data"]["REMOVED"]) AND empty($results["response_data"]["DUPLICATE"])) $row["workable_contacts"][] = $targetid;

		if(!in_array($targetkey, $row["penetration_rate"]) AND hasEvent($results["events"], "GENERATED")) $row["penetration_rate"][] = $targetkey;

		if(!in_array($targetkey, $row["connect_rate"]) AND hasEvent($results["events"], "ANSWER")) $row["connect_rate"][] = $targetkey;

		if(!in_array($targetkey, $row["right_party_rate"]) AND !empty($results["response_data"]["1_OPTION"]) AND ($results["response_data"]["1_OPTION"] == "ISCUSTOMER")) $row["right_party_rate"][] = $targetkey;

		if(!in_array($targetkey, $row["live_transfer"]) AND !empty($results["response_data"]["2_OPTION"]) AND ($results["response_data"]["2_OPTION"] == "TRANSFER")) $row["live_transfer"][] = $targetkey;

		if(!in_array($targetkey, $row["callback_rate"]) AND !empty($results["response_data"]["CALLBACK"])) $row["callback_rate"][] = $targetkey;

		if(!in_array($targetkey, $row["total_transfer"]) AND ((!empty($results["response_data"]["2_OPTION"]) AND ($results["response_data"]["2_OPTION"] == "TRANSFER")) OR !empty($results["response_data"]["CALLBACK"]))) $row["total_transfer"][] = $targetkey;

		if(!in_array($targetkey, $row["ptp_rate"]) AND !empty($results["response_data"]["2_OPTION"]) AND ($results["response_data"]["2_OPTION"] == "PROMISE")) $row["ptp_rate"][] = $targetkey;

		if(!empty($results["response_data"]["2_OPTION"]) AND ($results["response_data"]["2_OPTION"] == "PROMISE")) $row["ptp"]++;

		if(($results["status"] == "ABANDONED") AND !hasEvent($results["events"], "DISCONNECTED") AND empty($results["response_data"]["REMOVED"]) AND empty($results["response_data"]["DUPLICATE"])) $row["sms"]++;


	}

	// contracts
	$file .= count($row["contracts"]) . ",";

	// contacts
	$file .= count($row["contacts"]) . ",";

	// workable contracts
	$file .= count($row["workable_contracts"]) . ",";

	// workable_contacts
	$file .= count($row["workable_contacts"]) . ",";

	// penetration_rate
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["penetration_rate"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// connect_rate
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["connect_rate"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// right_party_rate
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["right_party_rate"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// live_transfer
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["live_transfer"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// callback_rate
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["callback_rate"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// total_transfer
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["total_transfer"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// ptp
	$file .= $row["ptp"] . ",";

	// ptp_rate
	$file .= sprintf("%01.2f", count($row["workable_contracts"]) ? (count($row["ptp_rate"]) / count($row["workable_contracts"]))*100 : 0) . "%,";

	// sms
	$file .= $row["sms"] . "\n";

}

//print $file; exit;

$email["to"]          = api_cron_tags_get(27, "reporting-destination");
$email["subject"]     = "[ReachTEL] BOQ Collections - Monthly report - " . date("Y-m-d");
$email["textcontent"] = "Hello,\n\nPlease find attached the monthly campaign report for today.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the monthly campaign report for today.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $file, "filename" => "BOQ-Collections-Monthly-" . date("Ymd") . ".csv");

api_email_template($email);

function hasEvent($events, $key){

	if(!is_array($events)) return false;

	foreach($events as $event) foreach($event as $item) if(!empty($item["value"]) AND ($item["value"] == $key)) return true;

	return false;

}