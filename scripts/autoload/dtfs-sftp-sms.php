<?php

require_once __DIR__ . '/../../api.php';

date_default_timezone_set('Australia/Melbourne');

if (api_misc_ispublicholiday()) {
	print 'Stopping because it is a public holiday\n';
	exit();
}

$path = '/tmp/';

print "Downloading files\n";
$cronId = 393;
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
	// We want to find a file with the file name format of DTFS-SMS-REACHTEL-DDDMMYYYY-HHMMSS.CSV
	// @TODO: We also match the old file name (without '-SMS'). Remove it once DTFS changed it on their side.
	$files = api_misc_sftp_list($options);
	if (!empty($files)) {
		$filenames = preg_grep(
			'/DTFS\-(SMS\-)?REACHTEL\-' . $date->format('dmY') . '/i',
			$files
		);
	}
}

if (!$filenames) {
	$email['to'] = $tags['sftp-failure-notification'];
	$email['cc'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['from'] = 'ReachTEL Support <support@ReachTEL.com.au>';
	$email['subject'] =
		'[ReachTEL] Auto-load error - DTFS - DTFS-SMS-REACHTEL-' . $date->format('dmY') . "-*.CSV.PGP";
	$email['content'] =
		"Hello,\n\nThe following file could not be found on the specified server:\n\nDTFS-SMS-REACHTEL-" .
		$date->format('dmY') .
		"-*.CSV.PGP\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print 'File not found DTFS-SMS-REACHTEL-' . $date->format('dmY') . "-*.CSV.PGP\n";
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
		$email['subject'] = '[ReachTEL] Auto-load error - DTFS - DTFS-SMS-REACHTEL-' . $date->format('dmY') . '-*.CSV.PGP';
		$email['content'] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n$filename\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

		api_email_template($email);

		print "Failed to fetch file '" . $filename . "'\n";
		exit();
	}
	print "OK\n";
}

print 'Creating campaigns...';

$campaign = array("name" => "DTFS-SMS-" . $date->format("jFy") . "-SFTP");

$exists = api_campaigns_checknameexists($campaign["name"]);

if(is_numeric($exists)) {
	foreach($filenames as $filename) {
		unlink($path . $filename);
	}

	print "Failed. The campaign already exists.\n";
	exit;
} else {
	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "DTFS-SMS-*-SFTP"));
	$campaign["campaignid"] = api_campaigns_checkorcreate($campaign["name"], key($previouscampaigns));
}

if(!is_numeric($campaign["campaignid"])){
	foreach($filenames as $filename) {
		unlink($path . $filename);
	}

	print "Failed to create campaign\n";
	exit;
} else print "OK\n";

print "Setting SMS content...";
if(!api_campaigns_setting_set($campaign["campaignid"], "content", "[%SMS_Message%]")){
	print "Failed!\n";
	exit;
} else print "OK\n";


foreach($filenames as $filename) {
	print "Uploading file {$filename}:\n";
	if(is_readable($path . $filename) && preg_match("/pgp$/i", $filename)) {
		print " * PGP decrypt ...";
		// Decrypt the PGP file

		$contents = file_get_contents($path . $filename);
		$decrypted = api_misc_pgp_decrypt($contents);

		if(!$decrypted) {
			print "Failed to decrypt the file\n";
			exit;
		}

		file_put_contents($path . str_replace(".pgp", "", $filename), $decrypted);
		unlink($path . $filename);
		$filename = str_replace(".pgp", "", $filename);
		print "OK\n";
	}

	print " * Uploading data...";

	if (($handle = fopen($path . $filename, "r")) === FALSE) {
		print "Failed to open the file\n";
		exit;
	}

	$i = 0;

	while (($data = fgetcsv($handle, 1024768, ",")) !== FALSE) {
		$i++;

		if($i == 1) {
			$header = $data;
		} else {
			// Skip blank rows
			if(empty($data[0])) continue;

			$row = array();

			foreach($header as $key => $value) $row[$value] = (!empty($data[$key])) ? trim($data[$key]) : null;

			api_targets_add_single($campaign["campaignid"], $row["Contact_Numbers_Primary"], $row["Contract_Number"], 1, $row);
		}
	}

	fclose($handle);
	unlink($path . $filename);
	print "OK\n";
}



print "Deduplicating campaign...";
api_targets_dedupe($campaign["campaignid"]);

print "OK\n";

print 'Activating campaign...';

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
