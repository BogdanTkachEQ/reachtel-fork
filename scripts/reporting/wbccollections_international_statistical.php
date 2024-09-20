#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$campaignid = 34409;

if((date("G") == 1) AND (date("j") == 1)){ // First of month at 1am

	$start = strtotime(date("Y-m-01 00:00:00", strtotime("last day of last month")));
	$end = strtotime(date("Y-m-t 23:59:59", strtotime("last day of last month")));

} elseif((date("G") == 2) AND (date("N") == 7)) { // Every Sunday at 2am

	$start = strtotime(date("Y-m-d 00:00:00", strtotime("last sunday")));
	$end = strtotime(date("Y-m-d 23:59:59", strtotime("yesterday")));

} else exit;

date_default_timezone_set('Australia/Sydney');

$report = api_data_responses_phone_report($campaignid, date("Y-m-d H:i:s", $start), date("Y-m-d H:i:s", $end));

$events = array("loaded" => 0, "liveanswer" => 0, "rightparty" => 0, "dobvalidate" => 0, "ctapaid" => 0, "ctapromise" => 0, "ctahangup" => 0);

if(!empty($report))
foreach($report as $targetid => $result){

	$events["loaded"]++;

	if(isset($result["response_data"]["0_AMD"]) AND ($result["response_data"]["0_AMD"] == "HUMAN")) $events["liveanswer"]++;

	if(isset($result["response_data"]["1_OPTION"]) AND ($result["response_data"]["1_OPTION"] == "ISCUSTOMER")) $events["rightparty"]++;

	if(isset($result["response_data"]["2_DOBVALIDATE"]) AND ($result["response_data"]["2_DOBVALIDATE"] == "PASS")) $events["dobvalidate"]++;

	if(isset($result["response_data"]["3_OPTION"])){

		if($result["response_data"]["3_OPTION"] == "PAID") $events["ctapaid"]++;
		elseif($result["response_data"]["3_OPTION"] == "Pay_Promise_5_days") $events["ctapromise"]++;
		else $events["ctahangup"]++;
	}
}

$report = $events["loaded"] . " numbers loaded.\n" . $events["liveanswer"] . " live answers.\n" . $events["rightparty"] . " right party contacts.\n" . $events["dobvalidate"] . " passed date of birth validation.\n" . $events["ctapaid"] . " PAID call to action.\n" . $events["ctapromise"] . " PROMISE call to action.\n" . $events["ctahangup"] . " HANGUP call to action.\n";

$email["to"]          = api_cron_tags_get(41, "reporting-destination");
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["subject"]     = "[ReachTEL] International VRS report - statistical report - " . date("Y-m-d", $start) . " to " . date("Y-m-d", $end);
$email["textcontent"] = "Hello,\n\nPlease find the report for the period of " . date("Y-m-d", $start) . " to " . date("Y-m-d", $end) . " listed below.\n\n" . $report;
$email["htmlcontent"] = "Hello,<br /><br />Please find the report for the period of " . date("Y-m-d", $start) . " to " . date("Y-m-d", $end) . " listed below.<br /><br />" . $report;

if(empty($email["to"])) {

	print "Failed to find any recipients\n";
	exit;

}

api_email_template($email);