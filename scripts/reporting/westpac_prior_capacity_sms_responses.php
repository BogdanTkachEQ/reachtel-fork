#!/usr/bin/php
<?php

require_once ("Morpheus/api.php");

$campaignId = 154518; //westpac-prior-capacity-sms
$cronId = '118';

$tags = api_cron_tags_get($cronId);
$cron_settings = api_cron_setting_get_multi_byitem($cronId, [CRON_SETTING_LAST_RUN, CRON_SETTING_TIMEZONE]);

$startTimestamp = (isset($cron_settings[CRON_SETTING_LAST_RUN]) && $cron_settings[CRON_SETTING_LAST_RUN]) ?
	$cron_settings[CRON_SETTING_LAST_RUN] :
	api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_CREATED_TIME);

if (isset($cron_settings[CRON_SETTING_TIMEZONE])) {
	date_default_timezone_set($cron_settings[CRON_SETTING_TIMEZONE]);
}

$options = [
	'start' => date('Y-m-d H:00:01', $startTimestamp),
	'end' => date('Y-m-d H:00:00')
];
print "Generating report\n";
$contentArray = api_campaigns_report_summary_sms_array($campaignId, $options);

if (!$contentArray) {
	print "No data retrieved\n";
	exit;
}

$headers = array_keys(current($contentArray));

foreach ($contentArray as $i => $content) {
	if (!isset($content['Response']) || $content['Response'] === '') {
		// We don't need the rows corresponding to sent messages and the ones with out response
		unset($contentArray[$i]);
	}
}

array_unshift($contentArray, $headers);

$content = api_csv_string($contentArray);

print "Csv generated\n";

$filename = "Westpac-prior-capacity-sms-responses-" . date("Ymd-ga") . ".csv";

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] Westpac prior capacity sms responses - Incremental Report";
$email["textcontent"] = "Hello,\nPlease find attached the \"Westpac prior capacity sms responses - Incremental Report\" for ".$options['start']." - ".$options['end'].".";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $content, "filename" => $filename);

api_email_template($email);

print "Report sent";
