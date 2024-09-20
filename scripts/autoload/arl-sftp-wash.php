<?php

use Services\Container\ContainerAccessor;
use Services\Reports\CsvArrayToFileConverter;

require_once("Morpheus/api.php");

if(empty($argv[1])) {
	print "Invalid filename specified\n";
	exit;
}

$filename = $argv[1];

// If the filename ends in ".filepart" strip this from the file name
if(preg_match("/filepart$/i", $filename)) {
	$filename = substr($filename, 0, -9);
}

$path = "/tmp/";

// Sleep for 10 seconds to let the file settle down
sleep(10);

$tags = api_cron_tags_get(getenv(CRON_ID_ENV_KEY));

print "Downloading file...";

$options = array("hostname" => $tags["sftp-hostname"],
	 "username" => $tags["sftp-username"],
	 "password" => $tags["sftp-password"],
	 "localfile" => $path . $filename,
	 "remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - ARL - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

print "Creating campaign...";

$smscampaign = false;
if(preg_match("/^ARL\-VERFICTION\-\d+_(.+)\.csv$/i", $filename, $matches)) {

	$campaignname = "ARL-AutoWash-" . date("jFy") . "-" . $matches[1];
	$search = "ARL-AutoWash-*";

} else if(preg_match("/^ARL\-SMS\-\d+_(.+)\.csv/i", $filename, $matches)) {

	$campaignname = "ARL-SMS-" . date("jFy") . "-" . $matches[1];
	$search = "ARL-SMS-*";
	$smscampaign = true;

} else if(preg_match("/^ARL-CAMPAIGN-(\d{4})(\d{2})(\d{2})_\d{6}-.+-QldHealth01-(\d+)/i", $filename, $matches)) {

	$time = mktime(1, 0, 0, $matches[2], $matches[3], $matches[1]);

	$campaignname = "ARL-VRS-" . date("jFy", $time) . "-QldHealth01-" . $matches[4];
	$search = "ARL-VRS-*-QldHealth01";

} else {

	unlink($path . $filename);

    print "Failed. The file name doesn't match the expected format.\n";
    exit;
}

print $campaignname;
$exists = api_campaigns_checknameexists($campaignname);

if(is_numeric($exists)) {

	unlink($path . $filename);

    print "Failed. The campaign already exists.\n";
    exit;

}

$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => $search));
$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

if(!is_numeric($campaignid)){

	unlink($path . $filename);

	print "Failed to create campaign\n";
	exit;

}

print " OK\n";

print "Uploading data...";

if ($smscampaign === true) {
	$campaigns = array();
	if (($handle = fopen($path . $filename, "r")) === FALSE) {

		print "Failed to open the file\n";
		exit;
	}

	if (($header = fgetcsv($handle, 1024768, "|")) === FALSE) {
		print "Failed to read header row\n";
		exit;
	}

	while (($data = fgetcsv($handle, 1024768, "|")) !== FALSE) {
		// Skip blank rows
		if(empty($data[0])) continue;

		$row = array();

		foreach($header as $key => $value) $row[$value] = (!empty($data[$key])) ? trim($data[$key]) : null;

		if (empty($row['ResponsePH'])) {
			api_targets_add_single($campaignid, $row["Destination"], $row["Targetkey"], 1, $row);
			continue;
		}

		if (($did = api_data_numberformat($row['ResponsePH'])) === false) {
			print 'Invalid DID ('.$row['ResponsePH'].'), skipping row.'."\n";
			continue;
		}
		$did = $did['destination'];

		if (! isset($campaigns[$did])) {
			$campaigns[$did] = api_campaigns_checkorcreate('ARL-SMS'.$did.substr($campaignname, 7), key($previouscampaigns));
			$didid = api_sms_dids_nametoid($did);
			if ($didid === false) $didid = api_sms_dids_add($did);
			if ($didid !== false) {
				api_sms_dids_setting_set($didid, 'use', 'ARL-SMS-Campaign-AutoDID');
			} else {
				print 'Failed to create did'."\n";
				$campaigns[$did] = false;
				continue;
			}
			api_campaigns_setting_set($campaigns[$did], 'smsdid', $didid);			
		}

		if (! is_numeric($campaigns[$did])) {
			print 'Failed to create campaign for '.$did."\n";
			continue;
		}

		api_targets_add_single($campaigns[$did], $row["Destination"], $row["Targetkey"], 1, $row);
	}

	fclose($handle);
} else {
	$result = api_targets_fileupload($campaignid, $path . $filename, $filename, false, false, false, true);

	if(!is_array($result)){

		unlink($path . $filename);

		print "Failed to process file\n";
		exit;

	}

	if (
		isset($result['badrecords']) &&
		$result['badrecords'] &&
		isset($tags['badrecords-sftp-username']) &&
		isset($tags['badrecords-sftp-password']) &&
		isset($tags['badrecords-sftp-path'])
	) {
		$tempfname = tempnam(FILEPROCESS_TMP_LOCATION, "badrecordsarlwash");
		$badRecordsCsv = ContainerAccessor::getContainer()
			->get(CsvArrayToFileConverter::class)
			->convertArrayToFile($result['badrecords'], $tempfname);

		print "Sending bad records to sftp\n";
		$pathinfo = pathinfo($filename);
		$badRecordFilename = $pathinfo['filename'] . '_badrecords.csv';

		print "Connecting...\n";

		$options = [
			"hostname" => $tags["badrecords-sftp-hostname"],
			"username" => $tags["badrecords-sftp-username"],
			"password" => $tags["badrecords-sftp-password"],
			"localfile" => $tempfname,
			"remotefile" => $tags["badrecords-sftp-path"] . $badRecordFilename
		];

		$result = api_misc_sftp_put_safe($options);
		unlink($tempfname);

		if (!$result) {
			print "Uploading bad records failed!\n";
		} else {
			print "Uploading bad records successful\n";
		}
	}
}

print "OK\n";

unlink($path . $filename);

print "Activating campaign...";
if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed!\n";
	exit;

}
print "OK\n";

if (isset($campaigns)) {
	print 'Activating DID campaigns...'."\n";
	foreach ($campaigns as $campaignid) {
		if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

			print "Failed!\n";
			exit;

		}
	}
	print "OK\n";
}

print "Job done!\n";
