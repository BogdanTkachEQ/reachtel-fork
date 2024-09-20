#!/usr/bin/php
<?php

require_once __DIR__ . "/../../api.php";

$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);

if (!isset($tags['reporting-destination'])) {
	die("Cron tag 'reporting-destination' was not found\n");
}

// campaign name from autoload boq-ci-sms.php
$campaignname = sprintf("BOQ-CI-SMS-%s-LPFReminder", date('dFy'));
$campaignid = api_campaigns_nametoid($campaignname);

if(!$campaignid) {
	die("Campaign '{$campaignname}' not found\n");
}

$data = api_campaigns_report_summary_sms_array($campaignid);

if (!$data) {
	die("No records found for campaign '{$campaignname}'\n");
}
// add headers
array_unshift($data, array_keys($data[0]));

$email["to"]      = $tags["reporting-destination"];
$email["subject"] = "[ReachTEL] {$campaignname} report";
$email["content"] = "Hello,<br /><br />Please find attached the {$campaignname} campaign report.<br /><br />";
$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
$email["attachments"][] = [
	"content" => api_csv_string($data),
	"filename" => "{$campaignname}.csv",
];

api_email_template($email);

print "Email Sent\n";
