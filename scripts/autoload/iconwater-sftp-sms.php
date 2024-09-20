<?php
// Copied over from actewagl-sftp-sms.php as per the ticket MOR-1561 and refactored with feature/REACHTEL-280
// Should run at 9:20 am Canberra time Monday to Friday

require_once("Morpheus/api.php");

// cron 107
$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);

$runDate = 'now';
if (isset($tags['run-date'])) {
	$runDate = $tags['run-date'];
}

$boostModifier = 10; // Percentage
if(isset($tags['boost-modifier']) && is_numeric($tags['boost-modifier'])) {
	$boostModifier = $tags['boost-modifier'];
}

$timeZone = new DateTimeZone('Australia/Sydney');

// Date is current date for naming campaign / stopping on weekends
$date = new DateTime($runDate, $timeZone);
$date->setTime(12, 0, 0); // set to midday to avoid daylight savings issues when we use timestamp later

if($date->format('N') > 5){
	die("Stopping because it is a weekend\n");
}

if (false !== ($message = isHoliday($date))) {
	die($message);
}

$holidayCheckBackDaysTagKey = 'holiday-check-back-days';
$expectedTags = [
	$sftpHostNameTagKey = 'sftp-hostname',
	$sftpUsernameTagKey = 'sftp-username',
	$sftpPwdTagKey = 'sftp-password',
	$sftpPathTagKey = 'sftp-path',
	$sftpFailureEmailTagKey = 'sftp-failure-notification',
];

if (!$tags) {
	die("No tags set for cron.\n");
}

$missingTags = array_diff($expectedTags, array_keys($tags));

if ($missingTags) {
	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - Iconwater - " . $filename;
	$email["content"] = "Hello,\n\nThe following mandatory tag(s) are missing:\n\n" . implode(', ', $missingTags) . "\n\nThe auto-load process has failed.";
	api_email_template($email);
	die(sprintf("Mandatory tag(s) missing: %s.\n", implode(', ', $missingTags)));
}

$holidayCheckBack = 6;
if (isset($tags[$holidayCheckBackDaysTagKey])) {
	if ($tags[$holidayCheckBackDaysTagKey] > 0 && $tags[$holidayCheckBackDaysTagKey] <= 6) {
		$holidayCheckBack = $tags[$holidayCheckBackDaysTagKey];
	} else {
		print $holidayCheckBackDaysTagKey . " has invalid value and so defaulting it to " . $holidayCheckBack . ".\n";
		api_misc_audit('INVALID_CRON_TAG_VALUE', 'Tag ' . $holidayCheckBackDaysTagKey . ' has been set to invalid value.');
	}
}

print "Starting to fetch files\n";

// Find files for today and previous days if there were holidays previously, since all the files uploaded
// on previous holidays need to go to today's campaign
$data = [];
$errorDates = [];
$goodDates = [];

// Check date is modified to search for public holiday files
$checkDate = clone $date;
while ($holidayCheckBack >= 0) {
	// If it's Monday, run Saturday's file
	// Note: this will always skip Sunday in the checkback as Sunday will never have a file
	if ((int) $checkDate->format('N') === 1) {
		$checkDate->modify('-2 days');
	}

	$filename = "SMSREQUEST_ICONWATER_" . $checkDate->format('Ymd') . ".csv";
	$path = "/tmp/";
	print "Downloading file {$filename}...";

	$options = array(
		"hostname" => $tags[$sftpHostNameTagKey],
		"username" => $tags[$sftpUsernameTagKey],
		"password" => $tags[$sftpPwdTagKey],
		"localfile" => $path . $filename,
		"remotefile"=> $tags[$sftpPathTagKey] . $filename
	);

	if(!api_misc_sftp_get($options)){
		$errorDates[] = $checkDate->format('d-m-Y');
		print "Failed to fetch file '" . $filename . "'\n";
	} else {
		$goodDates[] = $checkDate->format('d-m-Y');
		print "OK\n";

		print "Reading data...";

		$csv = array_map('str_getcsv', file($path . $filename));

		unlink($path . $filename);

		if(!$csv || !is_array($csv)) {
			die("Failed to read data file\n");
		}
		print "OK\n";

		$data = array_merge($data, $csv);
	}

	if ($holidayCheckBack !== 0) {
		$checkDate = $checkDate->sub(new DateInterval('P1D'));
		if (!isHoliday($checkDate)) {
			print($checkDate->format('d-m-Y') . " is not a holiday. Stopping search for files\n");
			break;
		}
	}

	$holidayCheckBack--;
}

function isHoliday(DateTime $dateTime) {
	$timestamp = $dateTime->getTimestamp();
	if(api_misc_ispublicholiday('AU', $timestamp)){
		return "Stopping because it is a National public holiday";
	}
	if(api_misc_ispublicholiday("NSW", $timestamp)){
		return "Stopping because it is a NSW public holiday\n";
	}
	if(api_misc_ispublicholiday("ACT", $timestamp)){
		return "Stopping because it is an ACT public holiday\n";
	}

	return false;
}

if (count($goodDates) > 0) {
	print "Creating campaign...";

	// Campaign should be named for Saturday on Monday
	// This is safe since if today is Monday, we're ALWAYS running Saturday's file and possibly Friday
	if ((int) $date->format('N') === 1) {
		$date->modify('-2 days');
	}

	$campaignname = "Iconwater-SMS-" . $date->format("Ymd");
	print $campaignname;
	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		print "Failed. The campaign already exists.\n";
		exit;

	}

	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "Iconwater-SMS-20"));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

	if(!is_numeric($campaignid)){

		print "Failed to create campaign\n";
		exit;

	}

	print " OK\n";

	api_campaigns_tags_set($campaignid, array("PROC-DATE" => date("Ymd")));
	api_campaigns_tags_delete($campaignid, array("RETURNTIMESTAMP"));

	print "Uploading data...";

	// Upload data
	foreach($data as $line => $row) {
		$cycle = substr($row[3], 0, -8);

		if(!empty($tags["message-" . $cycle])) {

			$targetid = api_targets_add_single($campaignid, $row[1], $row[4], 1, ['Arrears' => $row[2], 'internal_id' => $row[0]]);

			// We can just gracefully continue if the target is invalid
			if(!$targetid) continue;

			$content = api_data_merge_process($tags["message-" . $cycle], $targetid, true);

			// Check if the content was generated ok
			if(empty($content)) {

				print "Failed to generate content for '" . $row[2] . "'\n";

			} else {
				api_targets_add_extradata_single($campaignid, $row[4], "MessageContent", $content);
			}

		} else {
			print "Could not find cycle '" . $cycle . "' for account '" . $row[4] . "\n";
		}
	}

	print "Setting the pacing...";

	$targets = api_data_target_status($campaignid);

	$secondsRemaining = api_restrictions_time_remaining($campaignid);

	if(!is_numeric($secondsRemaining) OR ($secondsRemaining <= 0)) $secondsRemaining = 60;

	$sendrateCalc = new \Services\Campaign\Limits\SendRate\PercentBoostTimeRemainingSendRateCalc($campaignid);
	$sendrate = $sendrateCalc->calculateRate($boostModifier);

	api_campaigns_setting_set($campaignid, "sendrate", $sendrate);

	print $sendrate . " message per hour\n";

	print "Activating campaign...";

	if((isset($tags["autoactivate"]) && $tags["autoactivate"] == "true") AND !api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

		print "Failed to activate the campaign!\n";
		exit;

	}

	print "OK\n";
} else {
	print "All files failed\n";
}

if ($errorDates) {
	$datesString = implode(', ', $errorDates);
	$goodDatesString = count($goodDates) > 0 ? implode(', ', $goodDates) : 'NONE';
	$content = <<<EOF
Hello,

Some expected files were not found for the following dates.

Successful dates: $goodDatesString

Unsuccessful dates: $datesString

Please advise ReachTEL Support if these files are expected at a later time.
EOF;

	print "Sending error email for files that could not be processed or retrieved.\n";
	$email["to"]      = $tags[$sftpFailureEmailTagKey];
	$email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - Iconwater - " . $filename;
	$email["content"] = $content;
	api_email_template($email);
}

print "Job done!\n";
