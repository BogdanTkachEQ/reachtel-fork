#!/usr/bin/php
<?php

require_once(__DIR__ . "/../../api.php");

// cron id 138
$cron_id = getenv('CRON_ID');
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

$reporting_date = isset($tags[$reporting_run_date_tag]) ? $tags[$reporting_run_date_tag] : 'yesterday';
$datetime = new DateTime($reporting_date);
$campaigns = api_campaigns_list_all(
	false,
	null,
	2, // limit
	["regex" => 'SimplyEnergy-RMD(ELEC|GAS)01X-' . $datetime->format('Ymd')]
);

if (!$campaigns) {
	print "No Campaigns found\n";
	api_error_printiferror();
	exit;
}

print "Generating cumulative report...\n";

$report = api_campaigns_report_cumulative_array($campaigns, 'phone');
$headers = array_shift($report);

// output CSV header order
$header_map = [
	'targetkey' => 'UNIQUEID',
];

$header = array_values($header_map);

$reports = [];
foreach ($report as $data) {
	// rename the columns in the header map
	$row = array_combine($headers, $data);
	$new = [];
	foreach ($header_map as $column => $alias) {
		$new[$alias] = array_key_exists($column, $row) ? $row[$column] : null;
	}

	$comments_fields = [
		'status',
		'REASON', // magic column
		'0_AMD',
		'VM',
		'1_OPTION',
		'2_DEBTOPTIONS'
	];

	// this logic is shared with MBFS-callback-daily-report.php and they should be updated together
	$comments = [];
	foreach ($comments_fields as $field) {
		$comments[$field] = isset($row[$field]) ? $row[$field] : '';
		switch ($field) {
			case 'status':
				$is_machine = isset($row['0_AMD']) && $row['0_AMD'] === 'MACHINE';
				if ($comments[$field] === 'ABANDONED' && $is_machine) {
					$comments[$field] = 'COMPLETE';
					$row[$field] = 'COMPLETE';
				}
				break;
			case 'REASON':
				if (isset($row['status']) && $row['status'] === 'ABANDONED') {
					$is_disconnected = isset($row['disconnected']) && $row['disconnected'] !== '';
					$is_removed = isset($row['REMOVED']) && $row['REMOVED'] !== '';
					if ($is_disconnected) {
						$reason = $row['disconnected'] === 'YES' ? 'DISCONNECTED' : $row['disconnected'];
					} elseif ($is_removed) {
						$reason = $row['REMOVED'];
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

	$reports[$data['campaign']][] = $new;
}

if (!$reports) {
	print "No report could be generated:\n";
	api_error_printiferror();
	exit;
}

foreach ($reports as $campaignName => $report) {
	print "Uploading report for campaign {$campaignName}\n";
	$content = api_csv_string($report);

	$filename = sprintf(
		'AddBulkNotes_%s_%s.csv',
		$datetime->format('Ymd'),
		str_replace('-', '_', $campaignName)
	);
	print "  * filename: {$filename}\n";

	if (!empty($tags["pgpkeys"])) {
		print "  * PGP encrypting report\n";
		$content = api_misc_pgp_encrypt($content, $tags["pgpkeys"]);

		if (empty($report)) {
			print "ERROR: Failed to PGP encrypt report\n";
			exit;
		}
		$filename .= $filename . ".pgp";
	}

	$tempfname = tempnam("/tmp", "simplyenergy-ivr");

	if (!file_put_contents($tempfname, $content)) {
		print "ERROR: Failed to write to temp file.";
		unlink($tempfname);
		exit;
	}

	print "  * Sending report via sftp ...";
	$options = array("hostname"  => $tags[$sftp_hostname_tag_name],
		"username"  => $tags[$sftp_username_tag_name],
		"password"  => $tags[$sftp_pw_tag_name],
		"localfile" => $tempfname,
		"remotefile" => $tags[$sftp_path_tag_name] . $filename);

	$result = api_misc_sftp_put_safe($options);

	unlink($tempfname);

	if (!$result) {
		print "ERROR: Failed to upload to SFTP\n";
		exit;
	}

	print " done!\n\n";
}

print "Job Done\n";

// Remove reporting-run-date if successful
if (isset($tags[$reporting_run_date_tag])) {
	api_cron_tags_delete($cron_id, [$reporting_run_date_tag]);
}
