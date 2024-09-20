#!/usr/bin/php
<?php

require_once ("Morpheus/api.php");

$cronid = 95;
$daysback = 4;
$dayslastmonth = date('t', strtotime("last day of last month"));

$tags = api_cron_tags_get($cronid);
$timezone = api_cron_setting_getsingle($cronid, "timezone");

if (! empty($timezone)) date_default_timezone_set($timezone);

$dates = [];
$dates[] = date('jFy', strtotime("last day of last month"));
for ($x = 1; $x < ($dayslastmonth + $daysback); $x++) {
    $dates[] = date('jFy', strtotime("{$x} days ago", strtotime("last day of last month")));
}
$regexp = sprintf(
    '^RAMS-SMS-(%s)-SMS(1|4)$',
    implode('|', $dates)
);

$campaignMap = [];
$targetcount = [
    'SMS1' => 0,
    'SMS4' => 0,
];
$campaigns = api_campaigns_list_all(true, null, null, ['regex' => $regexp]);
$summaryData = [];
$summaryHeaders = [];

foreach($campaigns as $id => $name) {
    $created = date('Y-m-d H:i:s', api_campaigns_setting_getsingle($id, 'created'));
    $campaignMap[$name] = [
        'id' => $id,
        'created' => $created
    ];

    $targets = api_data_target_status_sms($id);

    $mergeData = array_merge(
        ['CAMPAIGN_NAME' => $name, 'DATE' => $created],
        $targets
    );

    $summaryHeaders = array_unique(array_merge($summaryHeaders, array_keys($mergeData)));

    $summaryData[] = $mergeData;

    if (strpos($name, 'SMS1') !== false) {
        $targetcount['SMS1'] += $targets['SENT'];
    } elseif (strpos($name, 'SMS4')) {
        $targetcount['SMS4'] += $targets['SENT'];
    }
}

$summaryData = reArrangeSummaryDataByHeaders($summaryData, $summaryHeaders);

$ids = array_map(function($campaign) {
    return $campaign['id'];
}, $campaignMap);

$options = [
	'start' => date("Y-m-01 00:00:00", strtotime("last day of last month")),
	'end' => date("Y-m-t 23:59:59", strtotime("last day of last month"))
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
$summaryContent = api_csv_string($summaryData);

$sql = "SELECT COUNT(*) count FROM response_data WHERE campaignid IN (SELECT id FROM key_store WHERE type = 'CAMPAIGNS' AND item = 'name' AND value LIKE ?) AND action='OPTION' AND value IN (1,2)";
$rs = api_db_query_read($sql, array('RAMS%'.date("Fy", strtotime("last day of last month")).'-SMS1'));
if ($array = $rs->FetchRow()) {
	$sms1Promises = $array["count"];
} else {
	$sms1Promises = 'unknown';
}
$rs = api_db_query_read($sql, array('RAMS%'.date("Fy", strtotime("last day of last month")).'-SMS4'));
if ($array = $rs->FetchRow()) {
	$sms4Promises = $array["count"];
} else {
	$sms4Promises = 'unknown';
}

$filename = "ReachTEL-RAMS-SMS-" . date("Ym", $start_timestamp) . "-Monthly.csv";

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] RAMS SMS Responses - Monthly Summary Report";
$email["textcontent"] = "Hello,\nPlease find attached the RAMS SMS Responses - Monthly Summary Report.";
$email["textcontent"] .= "\nTotal SMS1 sent: ".$targetcount['SMS1']."\nTotal SMS1 promises: ".$sms1Promises."\nTotal SMS4 sent: ".$targetcount['SMS4']."\nTotal SMS4 promises: ".$sms4Promises;
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => $filename);
$email["attachments"][] = [
    'content' => $summaryContent,
    'filename' => "ReachTEL-RAMS-SMS-" . date("Ym", $start_timestamp) . "-Monthly-Summary.csv"
];

api_email_template($email);

function reArrangeSummaryDataByHeaders($summaryData, $summaryHeaders) {
	$updatedSummaryData = [];
	foreach ($summaryData as $data) {
		$updatedData = [];
		foreach ($summaryHeaders as $header) {
			$updatedData[] = isset($data[$header]) ? $data[$header] : null;
		}
		$updatedSummaryData[] = $updatedData;
	}

    array_unshift($updatedSummaryData, $summaryHeaders);

	return $updatedSummaryData;
}
