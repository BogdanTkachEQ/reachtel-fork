#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

date_default_timezone_set("Australia/Melbourne");

$tags = api_cron_tags_get(75);

$yesterday = strtotime("yesterday");
$today = time();

$activities["tempname"] = tempnam("/tmp", "momentum-creditagility");
$activities["filename"] = "mme_EEAccountActivitiesImportFileReachTelVRS_" . date("Ymd", $today) . ".txt";
$activities["handle"] = fopen($activities["tempname"], "w");
$activities["rows"] = 0;

$header = [ 'EEAccountImportFile', 'HDR', date("Ymd", $today), date("H:i:s"), date("Ymd", $today) ];
fputcsv($activities["handle"], $header, "|");

$notes["tempname"] = tempnam("/tmp", "momentum-creditagility");
$notes["filename"] = "mme_Notes_" . date("Ymd", $today) . ".txt";
$notes["handle"] = fopen($notes["tempname"], "w");
$notes["rows"] = 0;

$header = [ 'DataExportFile', 'HDR', date("Ymd", $today), date("H:i:s", $today), date("Ymd", $today) ];
fputcsv($notes["handle"], $header, "|");

foreach(api_campaigns_list_all(true, null, 15, array("search" => "MomentumEnergy-" . date("jFy", $yesterday) . "-VRS-CreditAgility-" . $tags["environment"] . "-Overdue-")) as $campaignid => $name) {

	print "Processing: " . api_campaigns_setting_getsingle($campaignid, "name") . "...\n";

	$data = api_data_responses_phone_report($campaignid);

	foreach($data as $targetid => $record){

		// If there are no events, move on as there is nothing to report on
		if(empty($record["events"])) continue;

		//$recordstatus = $record["status"];
		$recordstatus = '';

		$status = $recordstatus . "," . $record["destination"] . "," . ((isset($record["response_data"]["0_AMD"])) ? $record["response_data"]["0_AMD"] : "") . "," . ((isset($record["response_data"]["3_OPTION"])) ? $record["response_data"]["3_OPTION"] : "") . "," . ((isset($record["response_data"]["REMOVED"])) ? $record["response_data"]["REMOVED"] : "");

		$noterow = [
			'Task',
			$record["targetkey"],
			date("YmdHis", $yesterday),
			'',
			'Collection Cycle',
			$status,
			'',
			'',
			'',
			'ReachTel'
		];

		fputcsv($notes["handle"], $noterow, "|");

		$notes["rows"]++;

		if(isset($record["response_data"]["3_OPTION"]) && $record["response_data"]["3_OPTION"] == "MAKE_PAYMENT_NEXT_SEVEN_DAYS") {

			$reason["code"] = "PROMISE";
			$reason["description"] = "Payment within 7 days";

		} else if(isset($record["response_data"]["1_OPTION"]) && $record["response_data"]["1_OPTION"] == "ISCUSTOMER") {

			$reason["code"] = "RPC";
			$reason["description"] = "Customer Confirmed";

		} else continue;

		$activityrow = [
			'PutBack',
			date("YmdHis", $yesterday),
			'',
			$record["targetkey"],
			date("YmdHis", $yesterday),
			$reason["code"],
			$reason["description"],

		];

		fputcsv($activities["handle"], $activityrow, "|");

		$activities["rows"]++;
	}

}

$footer = [ 'EEAccountImportFile', 'FTR', $activities["rows"] ];
fputcsv($activities["handle"], $footer, "|");

$footer = [ 'DataExportFile', 'FTR', $notes["rows"] ];
fputcsv($notes["handle"], $footer, "|");

foreach([$activities, $notes] as $file) {

	fclose($file["handle"]);

	$options = array("hostname"  => $tags["sftp-hostname"],
		"username"  => $tags["sftp-username"],
	    "password"  => $tags["sftp-password"],
		"localfile" => $file["tempname"],
		"remotefile" => $tags["sftp-path"] . "/" . $tags["environment"] . "/fromReachtel/" . $file["filename"]);

	print "Uploading '{$file["filename"]}'...";

	$result = api_misc_sftp_put_safe($options);

	unlink($file["tempname"]);

	if(!$result) {

		print "Failed to upload to SFTP\n";
		exit;

	}

	print "OK\n\n";

}

print "OK!";