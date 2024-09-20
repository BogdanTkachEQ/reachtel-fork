<?php

//Copied from baycorpnz-ondemand.php

require_once("Morpheus/api.php");

if(api_misc_ispublicholiday()){
    print "Stopping because it is a public holiday\n";
    exit;
}

if(empty($argv[1])) {
	print "Invalid filename specified\n";
	exit;
}

$filename = $argv[1];

$cronId = 123;

// If the filename ends in ".filepart" strip this from the file name
if(preg_match("/filepart$/i", $filename)) {
	$filename = substr($filename, 0, -9);
}

$path = "/tmp/";

// Sleep for 10 seconds to let the file settle down
sleep(10);

print "Downloading file...";

$tags = api_cron_tags_get($cronId);

$options = array("hostname" => $tags["sftp-hostname"],
	"username" => $tags["sftp-username"],
	"password" => $tags["sftp-password"],
	"localfile" => $path . $filename,
	"remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - BaycorpAU - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

print "Creating campaign...";

if(preg_match("/^SMS_Request_(\d{2})(\d{2})(\d{4})_(\d{2})(\d{2})(\d{2})\.csv$/i", $filename, $matches)) {

	$time = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[1], $matches[3]);

	$campaignname = "BaycorpAU-" . date("jFy", $time) . "-OnDemand-" . $matches[4] . $matches[5] . $matches[6];
	$search = "BaycorpAU-*-OnDemand";

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

$result = api_targets_fileupload($campaignid, $path . $filename, $filename);

if(!is_array($result)){

	unlink($path . $filename);

	print "Failed to process file\n";
	exit;

}

print "OK\n";

unlink($path . $filename);

print "De-duping...";

if (!api_targets_dedupe($campaignid)) {
    print "\nFailed when de-duping campaign";
    exit;
}

print "OK\n";

print "Activating campaign...";

if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed!\n";
	exit;

}

print "OK\n";

print "Job done!\n";
