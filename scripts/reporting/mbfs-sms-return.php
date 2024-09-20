#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Melbourne";
date_default_timezone_set($timezone);

$tags = api_cron_tags_get(59);

$campaigns = array();

$yesterday = time() - 86400;

$rows = 0;

$content = "UNIQUEID,Name,Asset Type (PC/CV),Payment (PMT) method,Automatic DD,Contract Start Date,Direct Debit dishonour date,SMS_Code,SMS_Message,DESTINATION,STATUS,SENT,DELIVERED,UNDELIVERED,UNKNOWN,DUPLICATE,EXPIRED,REMOVED,RESPONSE,PAYNOW,CALLME,Address,Overdue_Amount,Overdue_Days,Payment_Reference_Number,Banking_Details,BPay,Suppression_End_Date,Date_of_Birth,Gender,Age,Contact_Email,COST\n";

foreach(api_campaigns_list_all(true, null, 15, array("search" => "MBFS-SMS-*-SFTP")) as $campaignid => $name){

	$settings = api_campaigns_setting_getall($campaignid);

	if($settings["created"] < $yesterday) continue;

	$data = api_data_responses_sms_report($campaignid);

	if(!empty($data)) foreach($data as $targetid => $result) {

		$rows++;

		$content .= $result["targetkey"] . ",";
		$content .= (!empty($result["merge_data"]["Name"]) ? $result["merge_data"]["Name"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Asset Type (PC/CV)"]) ? $result["merge_data"]["Asset Type (PC/CV)"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Payment (PMT) method"]) ? $result["merge_data"]["Payment (PMT) method"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Automatic DD"]) ? $result["merge_data"]["Automatic DD"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Contract Start Date"]) ? $result["merge_data"]["Contract Start Date"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Direct Debit dishonour date"]) ? $result["merge_data"]["Direct Debit dishonour date"] : "") . ",";
		$content .= (!empty($result["merge_data"]["SMS_Code"]) ? $result["merge_data"]["SMS_Code"] : "") . ",";
		$content .= (!empty($result["merge_data"]["SMS_Message"]) ? "\"" . $result["merge_data"]["SMS_Message"] . "\"" : "") . ",";
		$content .= $result["destination"] . ",";
		$content .= (!empty($result["status"]) ? $result["status"] : "") . ",";
		$content .= (!empty($result["response_data"]["SENT"]) ? $result["response_data"]["SENT"] : "") . ",";
		$content .= (!empty($result["response_data"]["DELIVERED"]) ? $result["response_data"]["DELIVERED"] : "") . ",";
		$content .= (!empty($result["response_data"]["UNDELIVERED"]) ? $result["response_data"]["UNDELIVERED"] : "") . ",";
		$content .= (!empty($result["response_data"]["UNKNOWN"]) ? $result["response_data"]["UNKNOWN"] : "") . ",";
		$content .= (!empty($result["response_data"]["DUPLICATE"]) ? $result["response_data"]["DUPLICATE"] : "") . ",";
		$content .= (!empty($result["response_data"]["EXPIRED"]) ? $result["response_data"]["EXPIRED"] : "") . ",";
		$content .= (!empty($result["response_data"]["REMOVED"]) ? $result["response_data"]["REMOVED"] : "") . ",";
		$content .= (!empty($result["response_data"]["RESPONSE"]) ? preg_replace("/[\r\n\t,|]/", " ", $result["response_data"]["RESPONSE"]) : "") . ",";
		$content .= (!empty($result["response_data"]["PAYNOW"]) ? $result["response_data"]["PAYNOW"] : "") . ",";
		$content .= (!empty($result["response_data"]["CALLME"]) ? $result["response_data"]["CALLME"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Address"]) ? str_replace(",", " ", $result["merge_data"]["Address"]) : "") . ",";
		$content .= (!empty($result["merge_data"]["Overdue_Amount"]) ? $result["merge_data"]["Overdue_Amount"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Overdue_Days"]) ? $result["merge_data"]["Overdue_Days"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Payment_Reference_Number"]) ? $result["merge_data"]["Payment_Reference_Number"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Banking_Details"]) ? $result["merge_data"]["Banking_Details"] : "") . ",";
		$content .= (!empty($result["merge_data"]["BPay"]) ? $result["merge_data"]["BPay"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Suppression_End_Date"]) ? $result["merge_data"]["Suppression_End_Date"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Date_of_Birth"]) ? $result["merge_data"]["Date_of_Birth"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Gender"]) ? $result["merge_data"]["Gender"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Age"]) ? $result["merge_data"]["Age"] : "") . ",";
		$content .= (!empty($result["merge_data"]["Contact_Email"]) ? str_replace(",", " ", $result["merge_data"]["Contact_Email"]) : "") . ",";
		$content .= (!empty($result["cost"]) ? $result["cost"] : "") . "\n";

	}

	$campaigns[] = $campaignid;
}

if(empty($campaigns) OR !$rows) {
	print "No records to return.";
	exit;
}

$tempfname = tempnam("/tmp", "mbfs-sms");

if(!empty($tags["pgpkeys"])) {

	$content = api_misc_pgp_encrypt($content, $tags["pgpkeys"]);

	if(empty($report)) {

		print "Failed to PGP encrypt report\n";
		exit;

	}

	$filename .= $filename . ".pgp";

}

if(!file_put_contents($tempfname, $content)) die("Failed to write file");

$filename = "MBFS-SMS-REACHTEL-" . date("dmY-His") . ".csv";

$options = array("hostname"  => $tags["sftp-hostname"],
	"username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
	"localfile" => $tempfname,
	"remotefile" => $tags["sftp-path"] . $filename);

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {

	print "Failed to upload to SFTP\n";
	exit;

}
