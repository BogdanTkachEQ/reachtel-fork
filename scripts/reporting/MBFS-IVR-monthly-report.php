#!/usr/bin/php
<?php

require_once(__DIR__ . "/../../api.php");

// cron 139
$cron_id = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cron_id);

$reporting_run_date_tag = 'reporting-run-date';
$reporting_destination_tag = 'reporting-destination';

$tags_diff = array_diff([$reporting_destination_tag], (array) array_keys($tags));
if ($tags_diff) {
	print "Required tags are missing:" . implode(',', $tags_diff);
	exit;
}

$reporting_date = isset($tags[$reporting_run_date_tag]) ? $tags[$reporting_run_date_tag] : 'yesterday';
$datetime = new DateTime($reporting_date);
$campaigns = api_campaigns_list_all(false, null, false, array("search" => "MBFS-IVR-*" . $datetime->format('Fy') . "*"));

if (!$campaigns) {
	print "No Campaigns found";
	exit;
}

print "Generating cumulative report...";

$report = api_campaigns_report_cumulative_array($campaigns, 'phone');
if (!$report) {
	print "No data to report";
	exit;
}

$headers = array_shift($report);

$group_report = [];
foreach ($report as $row) {
	$campaign_name = $row['campaign'];
	if (!array_key_exists($campaign_name, $group_report)) {
		$group_report[$campaign_name] = [
			'targets' => 0,
			'human' => 0,
			'machine' => 0,
			'1_option' => 0,
			'2_option' => 0,
			'transfers' => 0,
			'callbacks' => 0,
		];
	}

	$group_report[$campaign_name]['targets']++;

	switch ($row['0_AMD']) {
		case 'HUMAN':
			$group_report[$campaign_name]['human']++;
			break;
		case 'MACHINE':
			$group_report[$campaign_name]['machine']++;
			break;
	}

	// this should always be human since it's a callback, but just in case
	if (isset($row['0_AMD_CALLBACK'])) {
		switch ($row['0_AMD_CALLBACK']) {
			case 'HUMAN':
				$group_report[$campaign_name]['human']++;
				break;
			case 'MACHINE':
				$group_report[$campaign_name]['machine']++;
				break;
		}
	}

	if (isset($row['1_OPTION'])) {
		switch ($row['1_OPTION']) {
			case '1_ISCUSTOMER':
			case '2_NOTCUSTOMER':
				$group_report[$campaign_name]['1_option']++;
				break;
		}
	}

	if (isset($row['1_OPTION_CALLBACK'])) {
		switch ($row['1_OPTION_CALLBACK']) {
			case '1_ISCUSTOMER':
			case '2_NOTCUSTOMER':
				$group_report[$campaign_name]['1_option']++;
				break;
		}
	}

	if (isset($row['2_DEBTOPTIONS'])) {
		switch ($row['2_DEBTOPTIONS']) {
			case '1_PTP48HOURS':
			case '2_PTPTODAY':
				$group_report[$campaign_name]['2_option']++;
				break;
		}
	}

	if (isset($row['2_DEBTOPTIONS_CALLBACK'])) {
		switch ($row['2_DEBTOPTIONS_CALLBACK']) {
			case '1_PTP48HOURS':
			case '2_PTPTODAY':
				$group_report[$campaign_name]['2_option']++;
				break;
		}
	}

	if (
		(isset($row['TRANSFER_TIMESTAMP']) && $row['TRANSFER_TIMESTAMP'])
		|| (isset($row['TRANSFER_TIMESTAMP_CALLBACK']) && $row['TRANSFER_TIMESTAMP_CALLBACK'])
	) {
		$group_report[$campaign_name]['transfers']++;
	}

	if (isset($row['CALLBACK'])) {
		switch ($row['CALLBACK']) {
			case 'CALLBACK':
				$group_report[$campaign_name]['callbacks']++;
				break;
		}
	}
}

// final report
$final_report = [];
foreach ($group_report as $campaign_name => $campaign_data) {
	$final_report[] = [
		'Campaign' => $campaign_name,
		'Total Targets' => $campaign_data['targets'],
		'% Human' => _format_as_percentage($campaign_data['human'] / $campaign_data['targets']),
		'% Voicemail' => _format_as_percentage($campaign_data['machine'] / $campaign_data['targets']),
		'% 1_OPTION (is or is not customer)' => _format_as_percentage($campaign_data['1_option'] / $campaign_data['targets']),
		'% 2_DEBTOPTIONS Self Serve (option 1 and 2 only)' => _format_as_percentage($campaign_data['2_option'] / $campaign_data['targets']),
		'% transfers to call centre' => _format_as_percentage($campaign_data['transfers'] / $campaign_data['targets']),
		'Total Callbacks' => $campaign_data['callbacks'],
		'% Callbacks' => _format_as_percentage($campaign_data['callbacks'] / $campaign_data['targets']),
	];
}

// sort by campaign name
usort($final_report, '_sort_mbfs_campaigns');

// Add header to report data
array_unshift($final_report, array_keys($final_report[0]));

$content = api_csv_string($final_report);
print "OK\n";

// email the thing
print "Sending report by email...";
$filename = "MBFS-PHONE-REACHTEL-MONTHLY-" . $datetime->format('MY') . ".csv";
$email["to"] = $tags[$reporting_destination_tag];
$email["bcc"] = "ReachTEL Support <support@reachtel.com.au>";
$email["subject"] = "[ReachTEL] - " . $filename;
$email["content"] = "Hello,\n\nPlease find attached the report.";
$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = [
	"content" => $content,
	"filename" => $filename
];

api_email_template($email);
print "OK\n";

print "Job Done";

// Remove reporting-run-date if successful
if (isset($tags[$reporting_run_date_tag])) {
	api_cron_tags_delete($cron_id, [$reporting_run_date_tag]);
}

/**
 * Format a float as a percentage string
 *
 * @param float $number
 *
 * @return string
 */
function _format_as_percentage($number) {
	return number_format($number * 100, 2) . '%';
}

/**
 * Custom sort to sort campaign names
 *
 * eg
 * MBFS-IVR-3May19-SFTP
 * MBFS-IVR-3May19-Contact2
 * MBFS-IVR-3May19-Contact3
 * MBFS-IVR-3May19-1-SFTP
 * MBFS-IVR-3May19-1-Contact2
 * MBFS-IVR-3May19-1-Contact3
 * MBFS-IVR-4May19-SFTP
 * MBFS-IVR-4May19-Contact2
 * MBFS-IVR-4May19-Contact3
 *
 * @param array $a
 * @param array $b
 *
 * @return integer
 */
function _sort_mbfs_campaigns(array $a, array $b) {
	// sort dates
	$a_date_section = explode('-', $a['Campaign'])[2];
	$b_date_section = explode('-', $b['Campaign'])[2];
	if ($a_date_section !== $b_date_section) {
		return strtotime($a_date_section) > strtotime($b_date_section) ? 1 : -1;
	}

	// if all but last letter is the same, eg Contact2 vs Contact3
	// compare last letter (2 vs 3)
	if (substr($a['Campaign'], 0, -1) === substr($b['Campaign'], 0, -1)) {
		return $a['Campaign'] > $b['Campaign'] ? 1 : -1;
	}

	// otherwise sort inversely, so SFTP comes before Contact
	return $a['Campaign'] > $b['Campaign'] ? -1 : 1;
}
