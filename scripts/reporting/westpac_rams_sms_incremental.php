#!/usr/bin/php
<?php

require_once ("Morpheus/api.php");

$cronid = 94;
$daysback = 4;

$tags = api_cron_tags_get($cronid);
$lastrun = api_cron_setting_getsingle($cronid, "lastrun");
$timezone = api_cron_setting_getsingle($cronid, "timezone");

if (! empty($timezone)) date_default_timezone_set($timezone);

$dates = [];
$dates[] = date('jFy');
for ($x = 1; $x <= $daysback; $x++) {
    $dates[] = date('jFy', strtotime("{$x} days ago"));
}
$regexp = sprintf(
    '^RAMS-SMS-(%s)-SMS(1|4)$',
    implode('|', $dates)
);

$campaignMap = [];
$campaigns = api_campaigns_list_all(true, null, null, ['regex' => $regexp]);
foreach($campaigns as $id => $name) {
    $campaignMap[$name] = [
        'id' => $id,
        'created' => date('Y-m-d H:i:s', api_campaigns_setting_getsingle($id, 'created'))
    ];
}

$ids = array_map(function($campaign) {
    return $campaign['id'];
}, $campaignMap);

$options = [
    'start' => date("Y-m-d H:00:01", $lastrun),
    'end' => date("Y-m-d H:00:00")
];
$start_timestamp = strtotime($options['start']);
$end_timestamp = strtotime($options['end']);
$data = api_campaigns_report_cumulative_array($ids, 'sms');
foreach($data as $i => $row) {
    if ($i == 0) {
        $data[$i][] = 'campaign_timestamp';
        continue;
    }

    if (
        empty($row['OPTION']) || 
        empty($row['OPTION_TIMESTAMP']) ||
        ($option_timestamp = strtotime($row['OPTION_TIMESTAMP'])) === false ||
        $option_timestamp < $start_timestamp ||
        $option_timestamp > $end_timestamp
       ) {
        unset($data[$i]);
        continue;
    }

    $data[$i][] = $campaignMap[$row['campaign']]['created'];
}

$contents = api_csv_string($data);

$filename = "ReachTEL-RAMS-SMS-" . date("Ymd-ga") . ".csv";

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] RAMS SMS Responses - Incremental Report";
$email["textcontent"] = "Hello,\nPlease find attached the \"RAMS SMS Responses - Incremental Report\" for ".$options['start']." - ".$options['end'].".";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => $filename);

api_email_template($email);
