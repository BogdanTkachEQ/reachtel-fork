<?php

// Should run at 9:51 am Sydney time Monday to Friday

require_once("Morpheus/api.php");

date_default_timezone_set('Australia/Sydney');

if(date("N") > 5){

	print "Stopping because it is a weekend\n";
	exit;

}

if(api_misc_ispublicholiday()){

	print "Stopping because it is a public holiday\n";
	exit;

}

$tags = api_cron_tags_get(14);

if(!empty($argv[1])) $filename = "SMS_Campaign_" . $argv[1] . ".xls";
else $filename = "SMS_Campaign_" . date("Ymd") . ".xls";

$path = "/tmp/";

print "Downloading file...";

$options = array("hostname" => $tags["sftp-hostname"],
	"username" => $tags["sftp-username"],
	"password" => $tags["sftp-password"],
	"localfile" => $path . $filename,
	"remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = $tags["sftp-failure-notification"];
	$email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - ToyotaFS - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

print "Creating campaign...";

$campaignname = "ToyotaFS-" . date("jFy") . "-1-1to8";

$exists = api_campaigns_checknameexists($campaignname);

if(is_numeric($exists)) {

	unlink($path . $filename);

	print "Failed. The campaign already exists.\n";
	exit;

} else {
	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "ToyotaFS*-1-1to8"));
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

	unlink($path . $filename);

	print "Failed to process file\n";
	exit;

} else print "OK\n";

print "Removing arrears > 8 days...";

$sql = "SELECT `targetkey` FROM `merge_data` WHERE `campaignid` = ? AND `element` = ? AND `value` > ?";
$rs = api_db_query_read($sql, array($campaignid, "iArrearsDays", 8));

$removed = 0;

print "Found " . $rs->RecordCount() . " rows to remove. ";

while(!$rs->EOF){

	$sql = "DELETE FROM `targets` WHERE `campaignid` = ? AND `targetkey` = ?";
	$rs2 = api_db_query_write($sql, array($campaignid, $rs->Fields("targetkey")));

	$removed++;

	$rs->MoveNext();
}

print "Removed " . $removed . " rows\n";

print "Deduplicating campaign...";

api_targets_dedupe($campaignid);

print "OK\n";

print "Delaying SMS to an appropriate time for each state...";

foreach(api_targets_listall($campaignid) as $targetid => $destination){

	$target = api_targets_getinfo($targetid);

	// Only update targets that are set to be sent.
	if($target["status"] != "READY") continue;

	// Get the value of the state
	$state = api_data_merge_get_single($campaignid, $target["targetkey"], "vchState");

	// If the value is empty or it doesn't equal WA, set the time to 9:30am Sydney time, else 1pm Sydney time
	if(empty($state) OR ($state != "WA")) api_targets_updatestatus($targetid, "REATTEMPT", strtotime("09:30 Australia/Sydney"));
	else api_targets_updatestatus($targetid, "REATTEMPT", strtotime("13:00 Australia/Sydney"));

}

print "OK\n";

print "Setting pacing...";
$targets = api_data_target_status($campaignid);

$sendrate = ceil($targets["TOTAL"] / 4) + 1;

api_campaigns_setting_set($campaignid, "sendrate", $sendrate);

print $sendrate . " per hour\n";

print "Activating campaign...";

if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed!\n";
	exit;
} else print "OK\n";

print "\n\n";

print "Creating campaign...";

$campaignname = "ToyotaFS-" . date("jFy") . "-1-SMSVoice";

$exists = api_campaigns_checknameexists($campaignname);

if(is_numeric($exists)) {

	unlink($path . $filename);

	print "Failed. The campaign already exists.\n";
	exit;

} else {
	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "ToyotaFS*-1-SMSVoice"));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
}

if(!is_numeric($campaignid)){

	unlink($path . $filename);

	print "Failed to create campaign\n";
	exit;

} else print "OK\n";

print "Uploading data...";

$result = api_targets_fileupload($campaignid, $path . $filename, $filename, true);

if(!is_array($result)){

	unlink($path . $filename);

	print "Failed to process file\n";
	exit;

} else print "OK\n";

print "Removing arrears < 9 days...";

$sql = "SELECT `targetkey` FROM `merge_data` WHERE `campaignid` = ? AND `element` = ? AND `value` < ?";
$rs = api_db_query_read($sql, array($campaignid, "iArrearsDays", 9));

$removed = 0;

print "Found " . $rs->RecordCount() . " rows to remove. ";

while(!$rs->EOF){

	$sql = "DELETE FROM `targets` WHERE `campaignid` = ? AND `targetkey` = ?";
	$rs2 = api_db_query_write($sql, array($campaignid, $rs->Fields("targetkey")));

	$removed++;

	$rs->MoveNext();
}

print "Removed " . $removed . " rows\n";

print "Deduplicating campaign...";

api_targets_dedupe($campaignid);

print "OK\n";

print "Delaying SMS to an appropriate time for each state...";

foreach(api_targets_listall($campaignid) as $targetid => $destination){

	$target = api_targets_getinfo($targetid);

	// Only update targets that are set to be sent.
	if($target["status"] != "READY") continue;

	// Get the value of the state
	$state = api_data_merge_get_single($campaignid, $target["targetkey"], "vchState");

	// If the value is empty or it doesn't equal WA, set the time to 9:30am Sydney time, else 3pm Sydney time
	if(empty($state) OR ($state != "WA")) api_targets_updatestatus($targetid, "REATTEMPT", strtotime("09:30 Australia/Sydney"));
	else api_targets_updatestatus($targetid, "REATTEMPT", strtotime("15:00 Australia/Sydney"));

}

print "OK\n";

print "Setting pacing...";
$targets = api_data_target_status($campaignid);

$sendrate = ceil($targets["TOTAL"] / 4) + 1;

api_campaigns_setting_set($campaignid, "sendrate", $sendrate);

print $sendrate . " per hour\n";

print "Activating campaign...";

if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed!\n";
	exit;

} else print "OK\n";

print "Job done!\n";