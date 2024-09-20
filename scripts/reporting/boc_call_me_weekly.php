#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$cronId = 103;
$tags = api_cron_tags_get($cronId);

$regexp = sprintf(
	'^BOC-CallMe-(%s|%s)$',
	date('FY'),
	date('FY', strtotime('last month'))
);
$campaignIds = api_campaigns_list_all(false, false, 2, ['regex' => $regexp]);

if (!$campaignIds) {
	print 'No campaign ids found';
	exit;
}

$data = api_campaigns_report_cumulative(
	$campaignIds,
	'phone',
	[
		'start' => '1 week ago midnight',
		'end' => 'today -1sec'
	]
);

if (!$data) {
	print 'No data to return';
	exit;
}

$email["to"]	      = $tags['destination-email'];
$email["subject"]     = "[ReachTEL] BOC Weekly CallMe report - " . date("d-m-Y");
$email["textcontent"] = "Hello,\n\nPlease find attached your CallMe report for this week.\n\n";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $data, "filename" => "BOC-REACHTEL-CALLME-" . date("dmY") . ".csv");

api_email_template($email);
