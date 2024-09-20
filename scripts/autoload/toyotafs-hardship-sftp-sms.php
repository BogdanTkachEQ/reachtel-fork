<?php

// Should run at 9:51 am Sydney time Monday to Friday

require_once("Morpheus/api.php");

if(date("N") > 5){

	print "Stopping because it is a weekend\n";
	exit;

}

if(api_misc_ispublicholiday()){

	print "Stopping because it is a public holiday\n";
	exit;

}

if(!empty($argv[1])) $filename = "SMS_Hardship" . $argv[1] . ".xls";
else $filename = "SMS_Hardship_" . date("Ymd") . ".xls";

$path = "/tmp/";

print "Downloading file...";

$tags = api_cron_tags_get(52);

$options = array("hostname" => $tags["sftp-hostname"],
	"username" => $tags["sftp-username"],
	"password" => $tags["sftp-password"],
	"localfile" => $path . $filename,
	"remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = $tags["sftp-failure-notification"];
	$email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - ToyotaFS - Hardship - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

print "Creating campaign...";

$campaignname = "ToyotaFS-Hardship-" . date("Ymd");

$exists = api_campaigns_checknameexists($campaignname);

if(is_numeric($exists)) {

	unlink($path . $filename);

	print "Failed. The campaign already exists.\n";
	exit;

} else {
	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "ToyotaFS-Hardship-*"));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
}

if(!is_numeric($campaignid)){

	unlink($path . $filename);

	print "Failed to create campaign\n";
	exit;

} else print "OK\n";

print "Uploading data...";

$result = api_targets_fileupload($campaignid, $path . $filename, $filename);

if(!is_array($result)){

	print "Failed to process file\n";

} else {
    print "OK\n";
}

unlink($path . $filename);

print "Checking if we have an empty campaign...";

$targets = api_data_target_status($campaignid);

if($targets["READY"] == 0) { // Check if we have no targets

	print "It's empty - delete it.";

	api_campaigns_delete($campaignid);

	exit;

} else print "Nope...we have " . $targets["READY"] . " targets.\n";

print "Deduplicating campaign...";

api_targets_dedupe($campaignid);

print "OK\n";

print "Activating campaign...";

if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed!\n";
	exit;
} else print "OK\n";

print "Job done!\n";
