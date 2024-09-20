#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$campaignid = 34308;

$options = array("start" => date("Y-m-d 00:00:00", time()-604800), "end" => date("Y-m-d 23:59:59", time() - 86400));

$report = api_campaigns_report_summary_phone($campaignid, $options);

$email["to"]          = api_cron_tags_get(31, "reporting-destination");
$email["subject"]     = "[ReachTEL] Settlement letter delivery report - " . date("Y-m-d", time() - 86400);
$email["textcontent"] = "Hello,\n\nPlease find attached the Settlement letter delivery report for last week.\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the Settlement letter delivery report for last week.<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $report["content"], "filename" => "SettlementLetter-" . date("Ymd", time()-86400) . ".csv");

api_email_template($email);