#!/usr/bin/php
<?php

require_once(__DIR__ . "/../../api.php");

$timezone = "Australia/Melbourne";
date_default_timezone_set($timezone);

// cron 396
$cron_id = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cron_id);

$sftp_username_tag_name = 'sftp-username';
$sftp_hostname_tag_name = 'sftp-hostname';
$sftp_pw_tag_name = 'sftp-password';
$sftp_path_tag_name = 'sftp-path';
$reporting_run_date_tag = 'reporting-run-date';

$tags_diff = array_diff([$sftp_hostname_tag_name, $sftp_username_tag_name, $sftp_pw_tag_name, $sftp_path_tag_name], (array) array_keys($tags));
if ($tags_diff) {
	print "Required tags are missing:" . implode(',', $tags_diff) . "\n";
	exit;
}

$reporting_date = isset($tags[$reporting_run_date_tag]) ? $tags[$reporting_run_date_tag] : 'now';
$datetime = new DateTime($reporting_date);
$campaigns = api_campaigns_list_all(false, null, 15, array("search" => "DTFS-IVR-" . $datetime->format('jFy') . "*"));

if (!$campaigns) {
	print "No Campaigns found";
	exit;
}

print "Generating cumulative report...\n";

$report = api_campaigns_report_cumulative_array($campaigns, 'phone');
$headers = array_shift($report);

// output CSV header order
$header_map = [
	'targetkey' => 'UNIQUEID',
	'destination' => 'DESTINATION',
	'status' => 'STATUS',
	'0_AMD' => '0_AMD',
	'1_OPTION' => '1_OPTION',
	'2_OPTION' => '2_OPTION',
	'2_TRANSCALLTIME' => '2_TRANSCALLTIME',
	'2_TRANSDEST' => '2_TRANSDEST',
	'2_TRANSDUR' => '2_TRANSDUR',
	'2_DEBTOPTIONS' => '2_DEBTOPTIONS',
	'COMMENTS' => 'COMMENTS',
	'COST' => 'COST',
	'disconnected' => 'DISCONNECTED',
	'DURATIONS ->' => 'DURATIONS ->',
	'IDENTIFY' => 'IDENTIFY',
	'REMOVED' => 'REMOVED',
	'TRANSFER_TIMESTAMP' => 'TRANSFER_TIMESTAMP',
	'TRANSFER_OUTCOME' => 'TRANSFER_OUTCOME',
	'VM' => 'VM',
	'Contract_Number' => 'Contract_Number',
	'Address' => 'Address',
	'Age' => 'Age',
	'Asset_Type_Description' => 'Asset_Type_Description',
	'Automatic_DD' => 'Automatic_DD',
	'Banking_Details' => 'Banking_Details',
	'BPay' => 'BPay',
	'Contact_DOB_Day' => 'Contact_DOB_Day',
	'Contact_DOB_Day_2' => 'Contact_DOB_Day_2',
	'Contact_DOB_Day_3' => 'Contact_DOB_Day_3',
	'Contact_DOB_Month' => 'Contact_DOB_Month',
	'Contact_DOB_Month_2' => 'Contact_DOB_Month_2',
	'Contact_DOB_Month_3' => 'Contact_DOB_Month_3',
	'Contact_DOB_Year' => 'Contact_DOB_Year',
	'Contact_DOB_Year_2' => 'Contact_DOB_Year_2',
	'Contact_DOB_Year_3' => 'Contact_DOB_Year_3',
	'Contact_Email' => 'Contact_Email',
	'Contact_Name' => 'Contact_Name',
	'Contact_Name_2' => 'Contact_Name_2',
	'Contact_Name_3' => 'Contact_Name_3',
	'Contact_Number_2' => 'Contact_Number_2',
	'Contact_Number_3' => 'Contact_Number_3',
	'Contract_Start_Date' => 'Contract_Start_Date',
	'Date_of_Birth' => 'Date_of_Birth',
	'Direct_Debit_Dishonour_Date' => 'Direct_Debit_Dishonour_Date',
	'Gender' => 'Gender',
	'Name' => 'Name',
	'Overdue_Amount' => 'Overdue_Amount',
	'Overdue_Days' => 'Overdue_Days',
	'Payment_Method' => 'Payment_Method',
	'Payment_Reference_Number' => 'Payment_Reference_Number',
	'Primary_Contact_Number' => 'Primary_Contact_Number',
	'Risk' => 'Risk',
	'Salutation' => 'Salutation',
	'Secondary_Contact_Number' => 'Secondary_Contact_Number',
	'Suppression_End_Date' => 'Suppression_End_Date',
	'Third_Party_Authority' => 'Third_Party_Authority',
];

$header = array_values($header_map);

$phantom_check_array = [];

foreach ($report as &$data) {
	// rename the columns in the header map
	$row = array_combine($headers, $data);
	$new = [];
	foreach ($header_map as $column => $alias) {
		$new[$alias] = array_key_exists($column, $row) ? $row[$column] : null;
	}

	$comments_fields = [
		'STATUS',
		'REASON', //magic column
		'0_AMD',
		'VM',
		'1_OPTION',
		'2_OPTION',
		'IDENTIFY',
		'2_DEBTOPTIONS'
	];

	// this logic is shared with DTFS-callback-daily-report.php and they should be updated together
	$comments = [];
	foreach ($comments_fields as $field) {
		$comments[$field] = isset($new[$field]) ? $new[$field] : '';
		switch ($field) {
			case 'STATUS':
				$is_machine = isset($new['0_AMD']) && $new['0_AMD'] === 'MACHINE';
				if ($comments[$field] === 'ABANDONED' && $is_machine) {
					$comments[$field] = 'COMPLETE';
					$new['STATUS'] = 'COMPLETE';
				}
				break;
			case 'REASON':
				if ($new['STATUS'] === 'ABANDONED') {
					$is_disconnected = isset($new['DISCONNECTED']) && $new['DISCONNECTED'] !== '';
					$is_removed = isset($new['REMOVED']) && $new['REMOVED'] !== '';
					if ($is_disconnected) {
						$reason = $new['DISCONNECTED'] === 'YES' ? 'DISCONNECTED' : $new['DISCONNECTED'];
					} elseif ($is_removed) {
						$reason = $new['REMOVED'];
					} else {
						$reason = 'RINGOUT';
					}
					$comments[$field] = $reason;
				}
				break;
			default:
				break;
		}
	}

	$new['COMMENTS'] = implode('-', $comments);


	// record seen targets to identify phantom rows
	$contract_number = $new['Contract_Number'];
	$base_campaign = explode('-', $data['campaign']);
	array_pop($base_campaign);
	$base_campaign = implode('-', $base_campaign);

	if (!isset($phantom_check_array[$base_campaign])) {
		$phantom_check_array[$base_campaign] = [];
	}

	if (!isset($phantom_check_array[$base_campaign][$contract_number])) {
		$phantom_check_array[$base_campaign][$contract_number] = [
			'row' => $new,
			'targets' => [],
		];
	}

	$phantom_check_array[$base_campaign][$contract_number]['targets'][] = $new['UNIQUEID'];

	$data = $new;
}

// Add phantom rows - ie calls that weren't needed in the cascade
$phantom_array = [];
foreach ($phantom_check_array as $base_campaign_name => $campaign) {
	foreach ($campaign as $contract_number => $contract_details) {
		$contract_targets = $contract_details['targets'];
		if (count($contract_targets) !== 3) {
			sort($contract_targets);
			$phantom_array[] = [
				'campaign' => $base_campaign_name,
				'contract_number' => $contract_number,
				'targets' => $contract_targets,
				'row' => $contract_details['row']
			];
		}
	}
}

$phantom_empty_fields = [
	'UNIQUEID',
	'DESTINATION',
	'STATUS',
	'0_AMD',
	'1_OPTION',
	'2_OPTION',
	'2_TRANSCALLTIME',
	'2_TRANSDEST',
	'2_TRANSDUR',
	'2_DEBTOPTIONS',
	'COMMENTS',
	'COST',
	'DISCONNECTED',
	'DURATIONS ->',
	'IDENTIFY',
	'REMOVED',
	'TRANSFER_TIMESTAMP',
	'TRANSFER_OUTCOME',
	'VM',
];

foreach ($phantom_array as $contract) {
	$template_row = $contract['row'];
	// empty non required fields
	foreach ($template_row as $field => &$value) {
		if (in_array($field, $phantom_empty_fields)) {
			switch ($field) {
				case 'UNIQUEID':
					$value = $template_row['Contract_Number'];
					break;
				case 'COST':
					$value = '';
					break;
				case 'STATUS':
					$value = 'NOTREQUIRED';
					break;
				case 'COMMENTS':
					$value = 'NOTREQUIRED-------';
					break;
				default:
					$value = '';
					break;
			}
		}
	}

	$next_target_number = count($contract['targets']) + 1;

	while ($next_target_number <= 3) {
		$row = $template_row;
		$row['UNIQUEID'] .= '-' . $next_target_number;
		$destination = $row['Contact_Number_' . $next_target_number];
		$formatted_destination = api_data_numberformat($destination);
		$row['DESTINATION'] = $formatted_destination ? $formatted_destination['fnn'] : $destination;

		// add to report
		$report[] = $row;
		$next_target_number++;
	}
}

// sort by targetkey
usort(
	$report,
	function($a, $b) {
		return $a['UNIQUEID'] > $b['UNIQUEID'] ? 1 : -1;
	}
);

// Add header to report data
array_unshift($report, $header);

$content = api_csv_string($report);

$filename = "DTFS-PHONE-REACHTEL-" . $datetime->format('dmY-His') . ".csv";

if (!empty($tags["pgpkeys"])) {
	print "PGP encrypting report\n";
	$content = api_misc_pgp_encrypt($content, $tags["pgpkeys"]);

	if (empty($report)) {
		print "Failed to PGP encrypt report\n";
		exit;
	}
	$filename .= $filename . ".pgp";
}

$tempfname = tempnam("/tmp", "dtfs-ivr");

if (!file_put_contents($tempfname, $content)) {
	print "Failed to write to temp file.";
	unlink($tempfname);
	exit;
}

print "Sending report via sftp\n";
$options = array("hostname"  => $tags[$sftp_hostname_tag_name],
	"username"  => $tags[$sftp_username_tag_name],
	"password"  => $tags[$sftp_pw_tag_name],
	"localfile" => $tempfname,
	"remotefile" => $tags[$sftp_path_tag_name] . $filename);

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if (!$result) {
	print "Failed to upload to SFTP\n";
	exit;
}

// Remove reporting-run-date if successful
if (isset($tags[$reporting_run_date_tag])) {
	api_cron_tags_delete($cron_id, [$reporting_run_date_tag]);
}

print "Job Done";
