#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

foreach(api_sms_dids_listall() as $didid => $name){

	$tags = api_sms_dids_tags_get($didid);

	if(
		empty($tags['reporting-destination']) &&
		!(
			isset($tags['sftp-path']) &&
			isset($tags['sftp-host']) &&
			isset($tags['sftp-username'])
		)
	) {
		continue;
	}

	$options = array("direction" => "inbound",
		"starttime" => date("Y-m-d 00:00:00", strtotime("yesterday")),
		"endtime" => date("Y-m-d 00:00:00"));

	$data = api_sms_dids_messagehistory($didid, $options);

	if(!is_array($data) OR count($data) == 0) continue;

	$contents = "source,timestamp,content\n";

	foreach($data as $entry) $contents .= "\"" . $entry["number"] . "\",\"" . $entry["timestamp"] . "\",\"" . $entry["contents"] . "\"\n";

	$filename = $name . "-trafficreport-" . date("Ymd", strtotime("yesterday")) . ".csv";

	if (isset($tags['reporting-destination'])) {
		$email = [];

		$subject = (!empty($tags['reporting-subjectoverride'])) ? $tags['reporting-subjectoverride'] : "[ReachTEL] Daily SMS traffic report - " . $name;

		$subject .= " - " . date("Y-m-d", strtotime("yesterday"));

		$email["to"]          = $tags['reporting-destination'];
		$email["subject"]     = $subject;
		$email["textcontent"] = "Hello,\n\nPlease find attached the SMS traffic report for " . date("d/m/Y", strtotime("yesterday")) . ".\n\n";
		$email["htmlcontent"] = "Hello,<br /><br />Please find attached the SMS traffic report for " . date("d/m/Y", strtotime("yesterday")) . ".<br /><br />";
		$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

		$email["attachments"][] = array("content" => $contents, "filename" => $filename);

		api_email_template($email);
	}

	if (isset($tags['sftp-path'])) {
		$tempfname = tempnam(FILEPROCESS_TMP_LOCATION, "sms-did-trafficreport");
		if (!file_put_contents($tempfname, $contents)) {
			print "Failed to write to temp file for sms did: " . $name . "\n";
			unlink($tempfname);
			continue;
		}

		$options = [
			"hostname"  => $tags['sftp-host'],
			"username"  => $tags['sftp-username'],
			"localfile" => $tempfname,
			"remotefile" => $tags['sftp-path'] . $filename
		];

		if (isset($tags['sftp_password'])) {
			$options["password"] = $tags['sftp-password'];
		}

		$result = api_misc_sftp_put_safe($options);

		unlink($tempfname);

		if (!$result) {
			print "Failed to upload to SFTP for DID: " . $name . "\n";
		}
	}
}
