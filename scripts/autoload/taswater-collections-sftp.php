<?php

require_once("Morpheus/api.php");

if(empty($argv[1])) {
	print "Invalid filename specified\n";
	exit;
}

$filename = $argv[1];

$path = "/tmp/";

print "Downloading file...";

$tags = api_cron_tags_get(78);

$options = array("hostname" => $tags["sftp-hostname"],
	 "username" => $tags["sftp-username"],
	 "password" => $tags["sftp-password"],
	 "localfile" => $path . $filename,
	 "remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - TasWater Collections - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";


if(preg_match("/^(BLW|SW|CMW)_DEBT_BROADCAST/", $filename, $filematches)) {

	// Process the Debt Broadcast file type for voice campaign

	print "Creating campaign...";

	$campaignname = "TasWater-VOICE-" . date("jFy") . "-Broadcast-" . $filematches[1];
	print $campaignname;
	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		unlink($path . $filename);

		print "Failed. The campaign already exists.\n";
		exit;
	}

	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "TasWater-VOICE*Broadcast-".$filematches[1]));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

} else if (preg_match("/^(BLW|SW|CMW)_DEBT_SMS_FRIENDLY/", $filename, $filematches)) {

	// Process the SMS file type

	print "Adding header row...";

	$contents = file_get_contents($path . $filename);

	if($contents === false) {

		unlink($path . $filename);

		print "Failed to open file\n";
		exit;
	}

	print "Creating campaign...";

	$campaignname = "TasWater-SMS-".date("jFy")."-Friendly-" . $filematches[1];

	print $campaignname;
	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		unlink($path . $filename);

	    print "Failed. The campaign already exists.\n";
	    exit;
	}

	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "TasWater-SMS*Friendly-".$filematches[1]));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

} else if (preg_match("/^(BLW|SW|CMW)_DEBT_SMS_FIRM/", $filename, $filematches)) {

		// Process the SMS file type

		print "Adding header row...";

		$contents = file_get_contents($path . $filename);

		if($contents === false) {

			unlink($path . $filename);

			print "Failed to open file\n";
			exit;
		}

		print "Creating campaign...";

		$campaignname = "TasWater-SMS-".date("jFy")."-Firm-" . $filematches[1];
		print $campaignname;
		$exists = api_campaigns_checknameexists($campaignname);

		if(is_numeric($exists)) {

			unlink($path . $filename);

			print "Failed. The campaign already exists.\n";
			exit;
		}

		$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "TasWater-SMS*Firm-".$filematches[1]));
		$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

} else {

	unlink($path . $filename);

    print "Failed. We don't understand the file type '" . $filename . "'.\n";
    exit;
}

if(!is_numeric($campaignid)){

	unlink($path . $filename);

	print "Failed to create campaign\n";
	exit;

}

print " OK\n";

$settings = api_campaigns_setting_getall($campaignid);

print "Uploading data...";

$result = api_targets_fileupload($campaignid, $path . $filename, $filename.".csv");

if(!is_array($result)){

	unlink($path . $filename);

	print "Failed to process file\n";
	exit;

}

print "OK\n";

unlink($path . $filename);

if($settings["type"] == "phone") {

	print "Deduplicating campaign...";

	api_targets_dedupe($campaignid);

	print "OK\n";
}

print "Activating campaign...\n";

if(isset($tags["autoactivate"]) && ($tags["autoactivate"] == "true") && api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

		print "Activated\n";

} else {
		print "Auto-activate disabled\n";
}

print "OK\n";

print "Job done!\n";