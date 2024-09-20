#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

$tags = api_cron_tags_get(73);

$date = strtotime("yesterday");

$competitions = array_map("trim", explode(",", $tags["competitions"]));

if(empty($competitions)) {
	print "No competitions to report on\n";
	exit;
}

foreach($competitions as $competition) {

	print $competition . ": ";

	$entries = api_misc_competitions_getentries($competition, date("Y-m-d", $date), date("Y-m-d", $date));

	if(empty($entries)) {
		print "No entries to return\n";
		continue;
	}

	$contents = "MPR,timestamp\n";

	foreach($entries as $row) {
		$contents .= $row["entry"] . "," . $row["timestamp"] . "\n";
	}

	$tempfname = tempnam("/tmp", "amx-datacollector");
	$filename = $competition . "_" . date("dmYHis", $date) . ".csv";

	file_put_contents($tempfname, $contents);

	$options = array("hostname"  => $tags["sftp-hostname"],
			 "username"  => $tags["sftp-username"],
	         "password"  => $tags["sftp-password"],
			 "localfile" => $tempfname,
			 "remotefile" => $tags["sftp-path"] . $filename);

	$result = api_misc_sftp_put_safe($options);

	unlink($tempfname);

	if(!$result) {

		print "Failed to upload to SFTP\n";
		continue;

	}

	print "Report generated with " . count($entries) . " records.";
}