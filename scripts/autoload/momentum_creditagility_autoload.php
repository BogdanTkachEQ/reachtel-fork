<?php

require_once("Morpheus/api.php");

if(empty($argv[1])) {
	print "Invalid filename specified\n";
	exit;
}

$tags = api_cron_tags_get(76);

$filename = $argv[1];
$environment = $argv[2];

$path = "/tmp/";

// Sleep for 10 seconds to let the file settle down
sleep(10);

print "Downloading file...";

$options = array("hostname" => "sftp.reachtel.com.au",
	"username" => "reachtelautomation",
	"localfile" => $path . $filename,
	"remotefile"=> "/mnt/sftpusers/veda_01/upload/MomentumEnergy/{$environment}/FromMomentum/" . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - Momentum Energy - {$environment} - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

print "Creating campaign...";

$campaignname = "MomentumEnergy-" . date("jFy") . "-VRS-CreditAgility-{$environment}-Overdue-1";
$search = "MomentumEnergy-*-VRS-CreditAgility-{$environment}-Overdue";

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

$result = api_targets_fileupload($campaignid, $path . $filename, $filename);

if(!is_array($result)){

	unlink($path . $filename);

	print "Failed to process file\n";
	exit;

}

print "OK\n";

unlink($path . $filename);

print "Setting pacing...";
$targets = api_data_target_status($campaignid);

$sendrate = ceil(($targets["READY"] / 2) * 1.2) + 1;

api_campaigns_setting_set($campaignid, "sendrate", $sendrate);

print $sendrate . " per hour\n";

print "Activating campaign...";

if(($tags["autoactivate"] == "true") AND !api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed to activate the campaign!\n";
	exit;

}

print "OK\n";

print "Job done!\n";