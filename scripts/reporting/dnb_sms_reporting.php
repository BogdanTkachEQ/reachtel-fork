#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$questions = array();
$mergedata = array();
$report = array();

$date = date("jFy", strtotime("yesterday"));

$campaigns = api_campaigns_list_all(true, null, null, array("search" => "DnB-SMS-" . $date));

if(empty($campaigns)) {
	print "No campaigns to report on.\n";
	exit;
}

$output = "UNIQUEID,DESTINATION,STATUS,SENT,DELIVERED,UNDELIVERED,UNKNOWN,DUPLICATE,CLICK,EXPIRED,REMOVED,COST\n";

foreach($campaigns as $campaignid => $value){

	print "Processing: " . api_campaigns_setting_getsingle($campaignid, "name");

	$data = api_data_responses_sms_report($campaignid);

	foreach($data as $targetid => $record){

		$output .= $record["targetkey"] . "," . $record["destination"] . "," . $record["status"] . ",";

		foreach(array("SENT", "DELIVERED", "UNDELIVERED", "UNKNOWN", "DUPLICATE", "CLICK", "EXPIRED", "REMOVED") as $question) {
			if(isset($record["response_data"][$question])) $output .= $record["response_data"][$question] . ",";
			else $output .= ",";
		}

		$output .= "\n";
	}

	print " OK\n";

}

print "OK\nDumping file...";

$tempfname = tempnam("/tmp", "dnb-sms");

if(!file_put_contents($tempfname, $output)) die("Failed to write file");

$filename = "DnB-SMS-" . $date . "-merged.csv";

$cron = api_cron_tags_get(65);

$options = array("hostname"  => $cron["sftp-hostname"],
	"port" => $cron["sftp-port"],
	"username"  => $cron["sftp-username"],
	"password"  => $cron["sftp-password"],
	"localfile" => $tempfname,
	"remotefile" => $cron["sftp-path"] . $filename);

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {

	$error = api_error_printiferror(array("return" => true));

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Reporting error - DnB SMS";
	$email["textcontent"] = "Hello,\n\nFailed to upload the file. " . $error;
	$email["htmlcontent"] = "Hello,\n\nFailed to upload the file. " . $error;

	api_email_template($email);

	print "Failed to upload to SFTP\n";
	exit;

} else print "Upload succeeded!\n";