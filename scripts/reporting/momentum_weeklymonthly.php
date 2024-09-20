#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

if((date("G") == 1) AND (date("j") == 1)){ // First of month at 1am

	$starttime = strtotime(date("Y-m-01 00:00:00", strtotime("yesterday")));

} elseif((date("G") == 2) AND (date("N") == 7)) { // Every Sunday at 2am

	$starttime = strtotime(date("Y-m-d 00:00:00", strtotime("last monday")));

} else exit;

$finishtime = strtotime(date("Y-m-d 23:59:59", strtotime("yesterday")));

$sql = "SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` > ?";
$rs = api_db_query_read($sql, array("CAMPAIGNS", "created", $starttime));

$campaigns = array();

while(!$rs->EOF){

	if((api_campaigns_setting_getsingle($rs->Fields("id"), "groupowner") == 328) AND preg_match("/VRS/i", (api_campaigns_setting_getsingle($rs->Fields("id"), "name")))) $campaigns[] = $rs->Fields("id");

	$rs->MoveNext();
}

if(empty($campaigns)) exit;

$data = array("questions" => array(), "mergefields" => array(), "results" => array());

$disconnected = array();

foreach($campaigns as $campaignid){

	$questions = api_data_responses_getquestions($campaignid);

	if(is_array($questions)) {
		$data["questions"] = array_unique(array_merge($data["questions"], $questions));
	}

	$mergestats = api_data_merge_stats($campaignid);

	if(is_array($mergestats)) {
		foreach($mergestats as $field) if(!in_array($field["element"], $data["mergefields"])) $data["mergefields"][] = $field["element"];
	}

	$report = api_data_responses_phone_report($campaignid);

	if(!is_array($report)) continue;

	$data["results"] = $data["results"] + api_data_responses_phone_report($campaignid);

	$sql = "SELECT `targetid` FROM `call_results` WHERE `campaignid` = ? AND `value` = ?";
	$rs = api_db_query_read($sql, array($campaignid, "DISCONNECTED"));

	while ($array = $rs->FetchRow()) $disconnected[] = $array["targetid"];

}

$contents = "\"UNIQUEID\",\"DESTINATION\",\"STATUS\",\"DISCONNECTED\",";

if ($data["questions"]) foreach ($data["questions"] as $value) $contents .= "\"" . $value . "\",";
if ($data["mergefields"]) foreach ($data["mergefields"] as $value) $contents .= "\"" . $value . "\",";

$contents.= "\"COST\",\"DURATIONS ->\"\n";

foreach($data["results"] as $targetid => $result){

	if (in_array($targetid, $disconnected)) $dc = "YES";
	else $dc = "";

	if(empty($result["targetkey"])) continue;

	$contents .= "\"" . $result["targetkey"] . "\",\"" . $result["destination"] . "\",\"" . $result["status"] . "\",\"" . $dc . "\",";

	if (isset($data["questions"])) {
		foreach ($data["questions"] as $value){

			if (isset($result["response_data"]) AND (isset($result["response_data"][$value]))) {
				$contents.= "\"" . $result["response_data"][$value] . "\",";
			} else {
				$contents.= ",";
			};
		}
	}

	if (isset($data["mergefields"]) AND is_array($data["mergefields"])) {
		foreach ($data["mergefields"] as $value) {
			if ((isset($result["merge_data"][$value])) AND ($result["merge_data"][$value] != null)) {
				$contents.= "\"" . $result["merge_data"][$value] . "\",";
			} else $contents.= ",";
		}
	}

	$contents.= ",";

	if (isset($result["events"])) {
		foreach ($result["events"] as $eventid => $event) {
			if (isset($event["billsec"]) AND ($event["billsec"] > 0)) $contents.= $event["billsec"] . ",";
			else $contents.= ",";
		}
	}

	$contents.= "\n";

}

$email["to"]          = api_cron_tags_get(35, "reporting-destination");
$email["subject"]     = "[ReachTEL] VRS report - " . date("Y-m-d", $starttime) . " to " . date("Y-m-d", $finishtime);
$email["textcontent"] = "Hello,\n\nPlease find attached the latest VRS report.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the latest VRS report.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "ReachTEL-VRS-" . date("Ymd", $starttime) . "-" . date("Ymd", $finishtime) . ".csv");

api_email_template($email);