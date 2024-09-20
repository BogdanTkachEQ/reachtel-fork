<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(53);

if(api_misc_ispublicholiday()){

	print "Stopping because it is a public holiday\n";
	exit;

}

if(!empty($argv[1])) $filename = $argv[1];
else $filename = "ReachTel_InputFile_" . date("dmY") . "_0530.txt";

$path = "/tmp/";

print "Downloading file...";

$options = array("hostname" => "sftp.reachtel.com.au",
	"username" => "reachtelautomation",
	"localfile" => $path . $filename,
	"remotefile"=> "/mnt/sftpusers/dnb_01/upload/Input_ReachTel/" . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      	= $tags["sftp-failure-notification"];
	$email["cc"]    	= "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    	= "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] 	= "[ReachTEL] Auto-load error - DnB VRS - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

print "Creating campaigns...\n";

$nameprefix = "DnB-VRS";

// Map the "DnB team transfer number" merge data value to specific campaigns
$mapping = array("0399145301" => "UtilitiesNZ",
	"0399145302" => "TelcoandEnt",
	"0399145303" => "LifestyleandEducation",
	"0399145304" => "RoadsandFinance",
	"0399145305" => "UtilitiesAU",
	"0399145336" => "Centrelink",
	"0399145307" => "AMT",
	"0399145308" => "CentrelinkAMT",
	"0399145309" => "UtilitiesAusAMT");

foreach($mapping as $teamnumber => $campaigntype) {

	$campaignname = $nameprefix . "-" . date("jFy") . "-" . $campaigntype;

	print "Processing '" . $campaignname . "'...";

	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		unlink($path . $filename);

		print "Failed. The campaign already exists.\n";
		exit;

	} else {
		$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => $nameprefix . "-*-" . $campaigntype));
		$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
	}

	if(!is_numeric($campaignid)){

		unlink($path . $filename);

		print "Failed to create campaign '" . $campaignname . "'\n";
		exit;

	} else print "OK\n";

	print "Uploading data...";

	$result = api_targets_fileupload($campaignid, $path . $filename, $filename, false);

	if(!is_array($result)){

		unlink($path . $filename);

		print "Failed to process file\n";
		exit;

	} else print "OK\n";

	// Remove stuff where the team number doesn't match
	foreach(api_targets_listall($campaignid) as $targetid => $destination) {

		$target = api_targets_getinfo($targetid);

		$targetTeamNumber = api_data_merge_get_single($campaignid, $target["targetkey"], "DnB team transfer number");

		if(empty($targetTeamNumber) OR !array_key_exists($targetTeamNumber, $mapping)) {

			$email["to"]      	= $tags["sftp-failure-notification"];
			$email["cc"]    	= "ReachTEL Support <support@ReachTEL.com.au>";
			$email["from"]    	= "ReachTEL Support <support@ReachTEL.com.au>";
			$email["subject"] 	= "[ReachTEL] Auto-load error - DnB VRS - Invalid DnB team transfer number detected";
			$email["textcontent"] = "Hello,\n\nInvalid DnB team transfer number detected - '" . $targetTeamNumber . "'\n\nThe auto-load process has failed. Please advise ReachTEL Support if the corrected files are expected at a later time.";
			$email["htmlcontent"] = "Hello,\n\nInvalid DnB team transfer number detected - '" . $targetTeamNumber . "'\n\nThe auto-load process has failed. Please advise ReachTEL Support if the corrected files are expected at a later time.";

			api_email_template($email);

			print "Failed to fetch file '" . $filename . "'\n";
			exit;

		} else if ($targetTeamNumber != $teamnumber) api_targets_delete_single_bytargetid($targetid);

	}

	print "Deduplicating campaign...";

	api_targets_dedupe($campaignid);

	print "OK\n";

	print "Activating campaign...";

	if(!empty($tags["autoactivate"]) AND ($tags["autoactivate"] === "true") AND !api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

		print "Failed!\n";
		exit;

	} else print "OK\n";

	print "Removing old RETURNTIMESTAMP...";
	api_campaigns_tags_delete($campaignid, array("RETURNTIMESTAMP"));
	print "OK\n\n";

}

unlink($path . $filename);

print "Job done!\n";