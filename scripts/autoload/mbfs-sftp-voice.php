<?php

require_once __DIR__ . '/../../api.php';

date_default_timezone_set('Australia/Melbourne');

if (api_misc_ispublicholiday()) {
	print 'Stopping because it is a public holiday\n';
	exit();
}

$path = '/tmp/';

print 'Downloading file...';
$cronId = 127;
$tags = api_cron_tags_get($cronId);

$autoloadRunDateTag = 'autoload-run-date';

$date = new DateTime(
	isset($tags[$autoloadRunDateTag]) ? $tags[$autoloadRunDateTag] : 'now'
);

$options = [
	'hostname' => $tags['sftp-hostname'],
	'username' => $tags['sftp-username'],
	'password' => $tags['sftp-password'],
	'remotefile' => $tags['sftp-path']
];

$filenames = [];
if (!empty($argv[1])) {
	$filenames[] = $argv[1];
} else {
	// We want to find a file with the file name format of MBFS-PHONE-REACHTEL-DDDMMYYYY-HHMMSS.CSV
	$files = api_misc_sftp_list($options);
	if (!empty($files)) {
		$filenames = preg_grep(
			'/MBFS\-PHONE\-REACHTEL\-' . $date->format('dmY') . '/i',
			$files
		);
	}
}

if (!$filenames) {
	$email['to'] = $tags['sftp-failure-notification'];
	$email['cc'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['from'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['subject'] =
		'[ReachTEL] Auto-load error - MBFS - MBFS-PHONE-REACHTEL-' . $date->format('dmY') . '-*.CSV.PGP';
	$email['content'] =
		"Hello,\n\nThe following file could not be found on the specified server:\n\nMBFS-PHONE-REACHTEL-" .
		$date->format('dmY') .
		"-*.CSV.PGP\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print 'File not found MBFS-PHONE-REACHTEL-' . $date->format('dmY') . "-*.CSV.PGP\n";
	exit();
}

foreach($filenames as $filename) {
	print "Fetching file '" . $filename . "'... ";
	$options['remotefile'] = $tags['sftp-path'] . $filename;
	$options['localfile'] = $path . $filename;

	if (!api_misc_sftp_get($options)) {
		$email['to'] = $tags['sftp-failure-notification'];
		$email['cc'] = 'ReachTEL Support <support@ReachTEL.com.au>';
		$email['from'] = 'ReachTEL Support <support@ReachTEL.com.au>';
		$email['subject'] = '[ReachTEL] Auto-load error - MBFS - MBFS-PHONE-REACHTEL-' . $date->format('dmY') . '-*.CSV.PGP';
		$email['content'] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n$filename\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

		api_email_template($email);

		print "Failed to fetch file '" . $filename . "'\n";
		exit();
	}
	print "OK\n";
}

print 'Creating campaigns...';

$campaign_base = 'MBFS-IVR-' . $date->format('jFy');
$campaign_name = $campaign_base . '-SFTP';

$exists = is_numeric(api_campaigns_checknameexists($campaign_name));
$fallback_exists = false;

if ($exists) {
	// try our fallback
	$campaign_base .= '-1';
	$campaign_name = $campaign_base . '-SFTP';
	$fallback_exists = is_numeric(api_campaigns_checknameexists($campaign_name));
}

// if the fallback exists
if ($fallback_exists) {
	$email['to'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['from'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['subject'] = '[ReachTEL] Auto-load error - MBFS - MBFS-PHONE-REACHTEL-' . $date->format('dmY') . '-*.CSV.PGP';
	$email['content'] = "Hello,\n\nThe fallback campaign $campaign_name already exists for the following file:\n\n$filename\n\nThe auto-load process has failed.";

	api_email_template($email);

	foreach($filenames as $filename) {
		unlink($path . $filename);
	}

	print "Failed. The campaign and fallback already exists.\n";
	exit();
} else {
	$campaign = ['name' => $campaign_name];
	$campaign2 = $campaign_base . '-Contact2';
	$campaign3 = $campaign_base . '-Contact3';

	$previouscampaign_1 = api_campaigns_list_all(
		true,
		null,
		1,
		['search' => 'MBFS-IVR-*-SFTP']
	);
	$previouscampaign_2 = api_campaigns_list_all(
		true,
		null,
		1,
		['search' => 'MBFS-IVR-*-Contact2']
	);
	$previouscampaign_3 = api_campaigns_list_all(
		true,
		null,
		1,
		['search' => 'MBFS-IVR-*-Contact3']
	);
	$campaign['campaignid'] = api_campaigns_checkorcreate(
		$campaign['name'],
		key($previouscampaign_1)
	);
	$campaign2_id = api_campaigns_checkorcreate(
		$campaign2,
		key($previouscampaign_2)
	);
	$campaign3_id = api_campaigns_checkorcreate(
		$campaign3,
		key($previouscampaign_3)
	);
}

if (!is_numeric($campaign['campaignid']) || !is_numeric($campaign2_id) || !is_numeric($campaign3_id)) {
	foreach($filenames as $filename) {
		unlink($path . $filename);
	}

	$email['to'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['from'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['subject'] = '[ReachTEL] Auto-load error - MBFS - MBFS-PHONE-REACHTEL-' . $date->format('dmY') . '-*.CSV.PGP';
	$email['content'] = "Hello,\n\nOne or more campaigns couldn't be created for the following file:\n\n$filename\n\nThe auto-load process has failed.";

	api_email_template($email);

	print "Failed to create campaigns\n";
	exit();
} else {
	print "OK\n";
}
$targets_added = 0;
foreach($filenames as $filename) {
	print "Uploading file {$filename}:\n";
	if (is_readable($path . $filename) && preg_match('/pgp$/i', $filename)) {
		print " * PGP decrypt ...";
		// Decrypt the PGP file

		$contents = file_get_contents($path . $filename);
		$decrypted = api_misc_pgp_decrypt($contents);

		if (!$decrypted) {
			print "Failed to decrypt the file\n";
			exit();
		}

		file_put_contents($path . str_replace('.pgp', '', $filename), $decrypted);
		unlink($path . $filename);
		$filename = str_replace('.pgp', '', $filename);
		print "OK\n";
	}

	print ' * Uploading data...';

	if (($handle = fopen($path . $filename, 'r')) === false) {
		print "Failed to open the file\n";
		exit();
	}

	$i = 0;

	while (($data = fgetcsv($handle, 1024768, ',')) !== false) {
		$i++;

		if ($i == 1) {
			$header = $data;

			// check all contact names / DOBs exist
			if ((in_array('Contact_Name', $header)
				&& in_array('Contact_DOB_Day', $header)
				&& in_array('Contact_DOB_Month', $header)
				&& in_array('Contact_DOB_Year', $header)
				&& in_array('Contact_Name_2', $header)
				&& in_array('Contact_DOB_Day_2', $header)
				&& in_array('Contact_DOB_Month_2', $header)
				&& in_array('Contact_DOB_Year_2', $header)
				&& in_array('Contact_Name_3', $header)
				&& in_array('Contact_DOB_Day_3', $header)
				&& in_array('Contact_DOB_Month_3', $header)
				&& in_array('Contact_DOB_Year_3', $header)
			) === false) {
				fclose($handle);
				unlink($path . $filename);

				$email['to'] = 'ReachTEL Support <support@ReachTEL.com.au>';
				$email['from'] = 'ReachTEL Support <support@ReachTEL.com.au>';
				$email['subject'] = '[ReachTEL] Auto-load error - MBFS - MBFS-PHONE-REACHTEL-' . $date->format('dmY') . '-*.CSV.PGP';
				$email['content'] = "Hello,\n\nOne or more of the required fields (names/DOBs) couldn't be found for the following file:\n\n$filename\n\nThe auto-load process has failed.";

				api_email_template($email);

				print "Failed to find required fields (names/DOBs)\n";
				exit();
			}
		} else {
			// Skip blank rows
			if (empty($data[0])) {
				continue;
			}

			$row = [];

			foreach ($header as $key => $value) {
				$row[$value] = !empty($data[$key]) ? trim($data[$key]) : null;
			}

	        // add campaign 1 generic merge data
			$row['CURRENT_NAME'] = $row['Contact_Name'];
			$row['DOB_DAY'] = $row['Contact_DOB_Day'];
			$row['DOB_MONTH'] = $row['Contact_DOB_Month'];
			$row['DOB_YEAR'] = $row['Contact_DOB_Year'];
			$row['Call_Timestamp'] = date("Y-m-d H:i:s");

			$target_key = $row['Contract_Number'] . '-1';

			$result = api_targets_add_single(
				$campaign['campaignid'],
				$row['Primary_Contact_Number'],
				$target_key,
				1,
				$row
			);

			if ($result !== false) {
				$targets_added++;
			}
		}
	}

	fclose($handle);
	unlink($path . $filename);
	print "OK\n";
}

print 'Activating campaign...';

$send_rate_base_hours = isset($tags['send-rate-base-hours']) ? $tags['send-rate-base-hours'] : 2;
api_campaigns_setting_set($campaign['campaignid'], 'sendrate', (int) ceil($targets_added / $send_rate_base_hours));

if (!api_campaigns_setting_set($campaign['campaignid'], 'status', 'ACTIVE')) {
	print "Failed!\n";
	exit();
} else {
	print "OK\n";
}

if (isset($tags[$autoloadRunDateTag])) {
	api_cron_tags_delete($cronId, [$autoloadRunDateTag]);
	print 'Removed tag ' . $autoloadRunDateTag;
}

api_error_printiferror();
print "Job done!\n";
