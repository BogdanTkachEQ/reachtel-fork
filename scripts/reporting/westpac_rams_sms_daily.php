#!/usr/bin/php
<?php

require_once ("Morpheus/api.php");

$cronid = 93;
$daysback = 4;

$tags = api_cron_tags_get($cronid);
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
	'start' => 'yesterday 17:00:01',
	'end' => 'today 17:00:00'
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

$filename = "ReachTEL-RAMS-SMS-" . date("Ymd") . "-Daily.csv";

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] RAMS SMS Responses - Daily Summary Report";
$email["textcontent"] = "Hello,\nPlease find attached the RAMS SMS Responses - Daily Summary Report.";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => $filename);

api_email_template($email);
