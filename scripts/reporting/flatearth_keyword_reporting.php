#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(getenv(CRON_ID_ENV_KEY));
$mandatory = [
	'reporting-start',
	'dids'
];
foreach($mandatory as $k) {
	if (!array_key_exists($k, $tags)) {
		print "ERROR: Cron tag '{$k}' is missing.";
		exit;
	}
}

$timezone = isset($tags['timezone']) ? $tags['timezone'] : "Australia/Sydney";
date_default_timezone_set($timezone);

$startdate = new DateTime($tags['reporting-start'], new DateTimeZone($timezone));
$enddate = new DateTime('yesterday', new DateTimeZone($timezone));

if(empty($tags["dids"])) {
	print "No DIDs to report on";
	exit;
}

$dids = array_map("trim", explode(",", $tags["dids"]));

foreach($dids as $did) {

	$entries = [];

	$use = api_sms_dids_setting_getsingle($did, "use");
	$name = api_sms_dids_setting_getsingle($did, "name");

	$smsdidtags = api_sms_dids_tags_get($did);

	if(empty($smsdidtags['reporting-destination-custom'])) continue;

	$keywords = (isset($smsdidtags["autoreply-tags"]) ? array_map("trim", explode(",", $smsdidtags["autoreply-tags"])) : array());

	$traffic = api_sms_dids_messagehistory(
		$did,
		[
			'direction' => 'inbound',
			'starttime' => $startdate->format("Y-m-d 00:00:00"),
			'endtime' => $enddate->format("Y-m-d 23:59:59")
		]
	);

	if(empty($traffic)) {
		print "No entries to return for '{$use}'\n";
		continue;
	}

	foreach($traffic as $message) {
		$date = new DateTime($message["timestamp"], new DateTimeZone($timezone));
		if (preg_match("/^(stop|opt out|optout|do not text|unsubscribe)/i", $message["contents"])) {
			$entries[$date->format("Y-m-d")]["optout"][] = $message;
		} else {
			foreach($keywords as $keyword) {
				if(preg_match("/^" . $keyword . "/i", trim($message["contents"]))) {
					$entries[$date->format("Y-m-d")][$keyword][] = $message;
				}
			}
		}

	}

	$contents = "keyword,shortcode,date,total responses,unique responses by date\n";

	ksort($entries);

	$resultstring = "Unique responses since " . $startdate->format("d/m/Y") . ":\n";

	$unique = [];
	$uniquebykeyword = [];
	$sum = 0;

	foreach($entries as $date => $keywords){
		foreach($keywords as $keyword => $rows) {
			foreach($rows as $row) {
				if(empty($unique[$keyword][$date]) || !in_array($row["number"], $unique[$keyword][$date])) {
					$unique[$keyword][$date][] = $row["number"];
				}
				if(empty($uniquebykeyword[$keyword]) || !in_array($row["number"], $uniquebykeyword[$keyword])) {
					$uniquebykeyword[$keyword][] = $row["number"];
				}
			}
			$contents .= $keyword . "," . $name . "," . $date . "," . count($rows) . "," . count($unique[$keyword][$date]) . "\n";
		}

	}

	foreach($uniquebykeyword as $keyword => $numbers) {
		$resultstring .= " " . count($numbers) . " '{$keyword}' unique responses.\n";
		$sum += count($numbers);
	}

	$resultstring .= "\n " . $sum . " responses in total.\n";

	$email = [];

	$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["to"]          = $smsdidtags['reporting-destination-custom'];
	$email["subject"]     = "[ReachTEL] Keyword report for '{$use}' - " . $enddate->format("d/m/Y");
	$email["textcontent"] = "Hello,\n\nPlease find attached the keyword report for {$use}.\n" . nl2br($resultstring);
	$email["htmlcontent"] = "Hello,<br /><br />Please find attached the keyword report for {$use}.<br />" . $resultstring;

	$email["attachments"][] = ["content" => $contents, "filename" => "ReachTEL-keyword-report-{$use}-" . $enddate->format("Y-m-d") . ".csv"];

	api_email_template($email);

	print "Returned " . count($entries) . " rows.\n";

}
