#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Melbourne";
date_default_timezone_set($timezone);

$groupid = 461;

$campaigns = array(); // Holds a all the campaign data we gather

$mergedata = array(); // Holds all the merge_data columns we see

$yesterday = strtotime("yesterday");

$delimiter = 0;

foreach(api_campaigns_list_all(true, null, 50, array("search" => "DnB-VRS")) as $campaignid => $name){

	$settings = api_campaigns_setting_getall($campaignid);
	$tags = api_campaigns_tags_get($campaignid);

	// Check if the campaign has already been returned, is still active or isn't a phone campaign and skip if so
	if(!empty($tags["RETURNTIMESTAMP"]) OR ($settings["type"] != "phone")) continue;

	api_campaigns_setting_set($campaignid, "status", "DISABLED");

	$campaigns[$campaignid] = api_data_responses_phone_report($campaignid);

	// Keep a list of all the merge_data fields we see
	$campaignmergedata = api_data_merge_stats($campaignid);

	if(is_array($campaignmergedata)) {

		foreach($campaignmergedata as $item) {

			if(!in_array($item["element"], $mergedata)) $mergedata[] = $item["element"];
		}
	}

	$delimiter = isset($settings["filedelimiter"]) ? $settings["filedelimiter"] : 0;

}

if(empty($campaigns)) {

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Reporting error - DnB VRS";
	$email["textcontent"] = "Hello,\n\nWe couldn't find any campaigns to process. The reporting process has stopped.";
	$email["htmlcontent"] = "Hello,\n\nWe couldn't find any campaigns to process. The reporting process has stopped.";

	api_email_template($email);

	print "No campaigns found to process\n";
	exit;
}

// Build the header row
$output =  api_data_delimit("EventID", $delimiter);
$output .= api_data_delimit("UNIQUEID", $delimiter);
$output .= api_data_delimit("DESTINATION", $delimiter);
$output .= api_data_delimit("STATUS", $delimiter);
$output .= api_data_delimit("DISCONNECTED", $delimiter);
$output .= api_data_delimit("0_AMD", $delimiter);
$output .= api_data_delimit("0_AMD_TIME", $delimiter);
$output .= api_data_delimit("1_INTRODUCTION", $delimiter);
$output .= api_data_delimit("2_VALIDATION", $delimiter);
$output .= api_data_delimit("3_DEBT_OPTIONS", $delimiter);
$output .= api_data_delimit("4_PAYMENT", $delimiter);
$output .= api_data_delimit("4_INVOICE", $delimiter);
$output .= api_data_delimit("4_DISPUTE", $delimiter);
$output .= api_data_delimit("5_PAYMENT_ARRANGEMENT", $delimiter);
$output .= api_data_delimit("5_ARRANGEMENTAMOUNT", $delimiter);
$output .= api_data_delimit("5_ARRANGEMENT_PASS", $delimiter);
$output .= api_data_delimit("6_PAYMENT_ARRANGEMENT", $delimiter);
$output .= api_data_delimit("6_PAYMENT_ARRANGEMENT_SETUP", $delimiter);
$output .= api_data_delimit("6_BSB", $delimiter);
$output .= api_data_delimit("6_ACCOUNT", $delimiter);
$output .= api_data_delimit("6_DIRECT_DEBIT_TC_ACCEPT", $delimiter);
$output .= api_data_delimit("7_DDTOKEN", $delimiter);
$output .= api_data_delimit("7_CCTRANSSTATUS", $delimiter);
$output .= api_data_delimit("7_CCREFERENCE", $delimiter);
$output .= api_data_delimit("TRANSFER_TO_AGENT", $delimiter);
$output .= api_data_delimit("1_TRANSCALLTIME", $delimiter);
$output .= api_data_delimit("1_TRANSDEST", $delimiter);
$output .= api_data_delimit("1_TRANSDUR", $delimiter);
$output .= api_data_delimit("PAYMENT_ARRANGEMENT_START_DATE", $delimiter);
$output .= api_data_delimit("TIME_ON_HOLD", $delimiter);
$output .= api_data_delimit("CALL_PROGRESS", $delimiter);
$output .= api_data_delimit("CALL_OUTCOME", $delimiter);
$output .= api_data_delimit("EVENT_CALL_OUTCOME", $delimiter);
$output .= api_data_delimit("COST", $delimiter);
$output .= api_data_delimit("DURATIONS ->", $delimiter);
$output .= api_data_delimit("", $delimiter);
$output .= api_data_delimit("", $delimiter);
$output .= api_data_delimit("Active arr flag", $delimiter);
$output .= api_data_delimit("Brand", $delimiter);
$output .= api_data_delimit("CCB Default flag", $delimiter);
$output .= api_data_delimit("Client Account ID", $delimiter);
$output .= api_data_delimit("Client name", $delimiter);
$output .= api_data_delimit("Copy Invoice flag", $delimiter);
$output .= api_data_delimit("Data account", $delimiter);
$output .= api_data_delimit("Debt amount", $delimiter);
$output .= api_data_delimit("Debtor day of birth", $delimiter);
$output .= api_data_delimit("Debtor email address", $delimiter);
$output .= api_data_delimit("Debtor First Name", $delimiter);
$output .= api_data_delimit("Debtor Full Name", $delimiter);
$output .= api_data_delimit("Debtor Last Name", $delimiter);
$output .= api_data_delimit("Debtor mailing address", $delimiter);
$output .= api_data_delimit("Debtor month of birth", $delimiter);
$output .= api_data_delimit("Debtor year of birth", $delimiter);
$output .= api_data_delimit("DIR only Client flag", $delimiter);
$output .= api_data_delimit("Dispute flag", $delimiter);
$output .= api_data_delimit("DnB contact number", $delimiter);
$output .= api_data_delimit("DnB email address - Call Back", $delimiter);
$output .= api_data_delimit("DnB email address - dispute", $delimiter);
$output .= api_data_delimit("DnB team transfer number", $delimiter);
$output .= api_data_delimit("Entry Date", $delimiter);
$output .= api_data_delimit("GP Code", $delimiter);
$output .= api_data_delimit("Next installment date", $delimiter);
$output .= api_data_delimit("Orig Debt Amount", $delimiter);
$output .= api_data_delimit("SDMI Flag", $delimiter);
$output .= api_data_delimit("Send.date", $delimiter);
$output .= api_data_delimit("Sent.time", $delimiter);
$output .= api_data_delimit("Timezone", $delimiter);
$output .= api_data_delimit("Trading name", $delimiter);
$output .= api_data_delimit("Unique Key", $delimiter);
$output .= "\n";

foreach($campaigns as $campaignid => $data) {

	if(empty($data)) {
		// Campaign is empty...skip
		continue;
	}

	foreach($data as $targetid => $target) {

		if(!empty($target["events"])) {

			foreach($target["events"] as $eventid => $results) {

				$responsedata = api_data_responses_getall($targetid, $eventid);

				$events = array($results);

				if(api_misc_hasevent($events, "ANSWER")) $eventcalloutcome = "ANSWER";
				elseif(api_misc_hasevent($events, "NOANSWER")) {

					$eventcalloutcome = "NOANSWER";
					$responsedata["CALL_OUTCOME"] = 1;

				} elseif(api_misc_hasevent($events, "CANCEL")) {

					$eventcalloutcome = "CANCEL";
					$responsedata["CALL_OUTCOME"] = 1;

				} elseif(api_misc_hasevent($events, "CONGESTION")) {

					$eventcalloutcome = "CONGESTION";
					$responsedata["CALL_OUTCOME"] = 1;

				} elseif(api_misc_hasevent($events, "BUSY")) {

					$eventcalloutcome = "BUSY";
					$responsedata["CALL_OUTCOME"] = 1;

				} elseif(api_misc_hasevent($events, "CHANUNAVAIL")) {

					$eventcalloutcome = "CHANUNAVAIL";
					$responsedata["CALL_OUTCOME"] = 1;

				} else $eventcalloutcome = "";

				if($target["disconnected"] == "DISCONNECTED") {
					$responsedata["CALL_OUTCOME"] = 2;
				}

				if(($target["status"] == "ABANDONED") AND empty($responsedata["CALL_OUTCOME"])) {
					$responsedata["CALL_OUTCOME"] = 3;
				}

				if(empty($responsedata["0_AMD_TIME"]) AND api_misc_hasevent($events, "GENERATED")) {
					$responsedata["0_AMD_TIME"] = date("Ymd-His", api_misc_oldestevent($events));
				}

				$output .= api_data_delimit($eventid, $delimiter);
				$output .= api_data_delimit($target["targetkey"], $delimiter);
				$output .= api_data_delimit($target["destination"], $delimiter);
				$output .= api_data_delimit($target["status"], $delimiter);
				$output .= api_data_delimit($target["disconnected"] ? "DISCONNECTED" : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["0_AMD"]) ? $responsedata["0_AMD"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["0_AMD_TIME"]) ? $responsedata["0_AMD_TIME"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["1_INTRODUCTION"]) ? $responsedata["1_INTRODUCTION"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["2_VALIDATION"]) ? $responsedata["2_VALIDATION"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["3_DEBT_OPTIONS"]) ? $responsedata["3_DEBT_OPTIONS"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["4_PAYMENT"]) ? $responsedata["4_PAYMENT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["4_INVOICE"]) ? $responsedata["4_INVOICE"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["4_DISPUTE"]) ? $responsedata["4_DISPUTE"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["5_PAYMENT_ARRANGEMENT"]) ? $responsedata["5_PAYMENT_ARRANGEMENT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["5_ARRANGEMENTAMOUNT"]) ? $responsedata["5_ARRANGEMENTAMOUNT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["5_ARRANGEMENT_PASS"]) ? $responsedata["5_ARRANGEMENT_PASS"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["6_PAYMENT_ARRANGEMENT"]) ? $responsedata["6_PAYMENT_ARRANGEMENT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["6_PAYMENT_ARRANGEMENT_SETUP"]) ? $responsedata["6_PAYMENT_ARRANGEMENT_SETUP"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["6_BSB"]) ? $responsedata["6_BSB"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["6_ACCOUNT"]) ? $responsedata["6_ACCOUNT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["6_DIRECT_DEBIT_TC_ACCEPT"]) ? $responsedata["6_DIRECT_DEBIT_TC_ACCEPT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["7_DDTOKEN"]) ? $responsedata["7_DDTOKEN"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["7_CCTRANSSTATUS"]) ? $responsedata["7_CCTRANSSTATUS"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["7_CCREFERENCE"]) ? $responsedata["7_CCREFERENCE"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["TRANSFER_TO_AGENT"]) ? $responsedata["TRANSFER_TO_AGENT"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["1_TRANSCALLTIME"]) ? $responsedata["1_TRANSCALLTIME"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["1_TRANSDEST"]) ? $responsedata["1_TRANSDEST"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["1_TRANSDUR"]) ? $responsedata["1_TRANSDUR"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["PAYMENT_ARRANGEMENT_START_DATE"]) ? $responsedata["PAYMENT_ARRANGEMENT_START_DATE"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["TIME_ON_HOLD"]) ? $responsedata["TIME_ON_HOLD"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["CALL_PROGRESS"]) ? $responsedata["CALL_PROGRESS"] : "", $delimiter);
				$output .= api_data_delimit(isset($responsedata["CALL_OUTCOME"]) ? $responsedata["CALL_OUTCOME"] : "", $delimiter);
				$output .= api_data_delimit($eventcalloutcome, $delimiter);
				$output .= api_data_delimit('', $delimiter);
				$output .= api_data_delimit(isset($results["duration"]) ? $results["duration"] : "", $delimiter);
				$output .= api_data_delimit("", $delimiter);
				$output .= api_data_delimit("", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Active arr flag"]) ? $target["merge_data"]["Active arr flag"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Brand"]) ? trim($target["merge_data"]["Brand"]) : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["CCB Default flag"]) ? $target["merge_data"]["CCB Default flag"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Client Account ID"]) ? $target["merge_data"]["Client Account ID"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Client name"]) ? $target["merge_data"]["Client name"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Copy Invoice flag"]) ? $target["merge_data"]["Copy Invoice flag"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Data account"]) ? $target["merge_data"]["Data account"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debt amount"]) ? $target["merge_data"]["Debt amount"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor day of birth"]) ? $target["merge_data"]["Debtor day of birth"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor email address"]) ? $target["merge_data"]["Debtor email address"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor First Name"]) ? $target["merge_data"]["Debtor First Name"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor Full Name"]) ? $target["merge_data"]["Debtor Full Name"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor Last Name"]) ? $target["merge_data"]["Debtor Last Name"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor mailing address"]) ? $target["merge_data"]["Debtor mailing address"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor month of birth"]) ? $target["merge_data"]["Debtor month of birth"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Debtor year of birth"]) ? $target["merge_data"]["Debtor year of birth"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["DIR only Client flag"]) ? $target["merge_data"]["DIR only Client flag"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Dispute flag"]) ? $target["merge_data"]["Dispute flag"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["DnB contact number"]) ? $target["merge_data"]["DnB contact number"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["DnB email address - Call Back"]) ? $target["merge_data"]["DnB email address - Call Back"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["DnB email address - dispute"]) ? $target["merge_data"]["DnB email address - dispute"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["DnB team transfer number"]) ? $target["merge_data"]["DnB team transfer number"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Entry Date"]) ? $target["merge_data"]["Entry Date"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["GP Code"]) ? $target["merge_data"]["GP Code"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Next installment date"]) ? $target["merge_data"]["Next installment date"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Orig Debt Amount"]) ? $target["merge_data"]["Orig Debt Amount"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["SDMI Flag"]) ? $target["merge_data"]["SDMI Flag"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Send.date"]) ? $target["merge_data"]["Send.date"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Sent.time"]) ? $target["merge_data"]["Sent.time"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Timezone"]) ? $target["merge_data"]["Timezone"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Trading name"]) ? $target["merge_data"]["Trading name"] : "", $delimiter);
				$output .= api_data_delimit(isset($target["merge_data"]["Unique Key"]) ? $target["merge_data"]["Unique Key"] : "", $delimiter);
				$output .= "\n";

			}

		} else {
				// Ignore targets with no events
				continue;

		}
	}

}

$tempfname = tempnam("/tmp", "dnb-vrs");

if(!file_put_contents($tempfname, $output)) die("Failed to write file");

$filename = "ReachTel_Outputfile_" . date("Ymd", $yesterday) . ".txt";

$cron = api_cron_tags_get(48);

$options = array("hostname"  => $cron["sftp-hostname"],
	"port" => $cron["sftp-port"],
	"username"  => $cron["sftp-username"],
	"password"  => $cron["sftp-password"],
	"localfile" => $tempfname,
	"remotefile" => $cron["sftp-path-out"] . $filename);

$result = api_misc_sftp_put($options);

unlink($tempfname);

if(!$result) {

	$error = api_error_printiferror(array("return" => true));

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Reporting error - DnB VRS";
	$email["textcontent"] = "Hello,\n\nFailed to upload the file. " . $error;
	$email["htmlcontent"] = "Hello,\n\nFailed to upload the file. " . $error;

	api_email_template($email);

	print "Failed to upload to SFTP\n";
	exit;

} else print "Upload succeeded!\n";

foreach(array_keys($campaigns) as $campaignid) api_campaigns_tags_set($campaignid, array("RETURNTIMESTAMP" => time()));
