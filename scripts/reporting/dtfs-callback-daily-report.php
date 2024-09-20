#!/usr/bin/php
<?php

require_once(__DIR__ . "/../../api.php");

$timezone = "Australia/Melbourne";
date_default_timezone_set($timezone);

// cron 395
$cron_id = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cron_id);

$sftp_username_tag_name = 'sftp-username';
$sftp_hostname_tag_name = 'sftp-hostname';
$sftp_pw_tag_name = 'sftp-password';
$sftp_path_tag_name = 'sftp-path';
$reporting_run_date_tag = 'reporting-run-date';

$tags_diff = array_diff([$sftp_hostname_tag_name, $sftp_username_tag_name, $sftp_pw_tag_name, $sftp_path_tag_name], array_keys($tags));
if ($tags_diff) {
	print "Required tags are missing:" . implode(',', $tags_diff);
	exit;
}

// find campaign ids from the last 24 hours that have callbacks
$reporting_date = isset($tags[$reporting_run_date_tag]) ? $tags[$reporting_run_date_tag] : 'now';
$start_date = new DateTime($reporting_date);
$end_date = clone($start_date);
$start_date->modify('-1 day');
$start_date->setTime(22, 0, 0);
$end_date->setTime(21, 59, 59);

printf("Looking for callbacks between %s and %s\n", $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'));

$sql = <<<EOF
SELECT targetkey, campaignid, k.value as campaignname
FROM response_data r FORCE INDEX (timestamp)
INNER JOIN key_store k ON (
	k.type = 'CAMPAIGNS'
	AND k.id = r.campaignid
	AND k.item = 'name'
	AND k.value LIKE 'DTFS-IVR-%'
)
WHERE
	action = 'CALLBACK'
	AND timestamp BETWEEN
	? AND ?
EOF;

$rs = api_db_query_read(
	$sql,
	[
		$start_date->format('Y-m-d H:i:s'),
		$end_date->format('Y-m-d H:i:s'),
	]
);
$results = $rs->GetArray();

// find out which of those are DTFS
$callback_targets = [];
$campaign_ids = [];
foreach ($results as $row) {
	if (!array_key_exists($row['campaignname'], $callback_targets)) {
		$callback_targets[$row['campaignname']] = [];
		$campaign_ids[$row['campaignid']] = $row['campaignid'];
	}
	$callback_targets[$row['campaignname']][] = $row['targetkey'];
}

// get the report
print "Generating cumulative report...\n";

if (!$campaign_ids) {
	print "No campaigns found to return.";
	api_error_printiferror();
	exit;
}

$report = api_campaigns_report_cumulative_array($campaign_ids, 'phone');

if (!$report) {
	print "No records to return.";
	api_error_printiferror();
	exit;
}

$headers = array_shift($report);

// filter to only callbacks in the last 24 hours
$report = array_filter(
	$report,
	function($row) use ($callback_targets) {
		// targets identified earlier with callback flag
		return in_array($row['targetkey'], $callback_targets[$row['campaign']]) && $row['CALLBACK'] === 'CALLBACK';
	},
	ARRAY_FILTER_USE_BOTH
);

// remap _CALLBACK responses to their canonical names
// output CSV header order
$header_map = [
	'targetkey' => 'UNIQUEID',
	'destination' => 'DESTINATION',
	'status' => 'STATUS',
	'0_AMD_CALLBACK' => '0_AMD', /* Callback Renamed */
	'1_OPTION_CALLBACK' => '1_OPTION', /* Callback Renamed */
	'2_OPTION_CALLBACK' => '2_OPTION', /* Callback Renamed */
	'2_TRANSCALLTIME_CALLBACK' => '2_TRANSCALLTIME', /* Callback Renamed */
	'2_TRANSDEST_CALLBACK' => '2_TRANSDEST', /* Callback Renamed */
	'2_TRANSDUR_CALLBACK' => '2_TRANSDUR', /* Callback Renamed */
	'2_DEBTOPTIONS_CALLBACK' => '2_DEBTOPTIONS', /* Callback Renamed */
	'COMMENTS' => 'COMMENTS',
	'COST' => 'COST',
	'disconnected' => 'DISCONNECTED',
	'DURATIONS ->' => 'DURATIONS ->',
	'IDENTIFY_CALLBACK' => 'IDENTIFY', /* Callback Renamed */
	'REMOVED' => 'REMOVED',
	'TRANSFER_TIMESTAMP_CALLBACK' => 'TRANSFER_TIMESTAMP', /* Callback Renamed */
	'TRANSFER_OUTCOME_CALLBACK' => 'TRANSFER_OUTCOME', /* Callback Renamed */
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

foreach ($report as &$data) {
	// rename the columns in the header map
	$row = array_combine($headers, $data);
	$new = [];
	foreach ($header_map as $column => $alias) {
		$new[$alias] = array_key_exists($column, $row) ? $row[$column] : null;
	}

	$comments_fields = [
		'STATUS',
		'REASON', // magic column
		'0_AMD',
		'VM',
		'1_OPTION',
		'2_OPTION',
		'IDENTIFY',
		'2_DEBTOPTIONS'
	];

	// this logic is shared with DTFS-IVR-daily-report.php and they should be updated together
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

	$data = $new;
}

// Add header to report data
array_unshift($report, $header);

$content = api_csv_string($report);

$filename = "DTFS-PHONE-CALLBACK-REACHTEL-" . $end_date->format("dmY-His") . ".csv";

if (!empty($tags["pgpkeys"])) {
	print "PGP encrypting report\n";
	$content = api_misc_pgp_encrypt($content, $tags["pgpkeys"]);

	if (empty($report)) {
		print "Failed to PGP encrypt report\n";
		exit;
	}
	$filename .= $filename . ".pgp";
}

$tempfname = tempnam("/tmp", "dtfs-callback");

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
