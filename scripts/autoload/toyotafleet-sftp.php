<?php

// Should run at 12:05pm Sydney time Monday to Friday

require_once("Morpheus/api.php");

$groupid = 390;

if(date("N") > 5){

	print "Stopping because it is a weekend\n";
	exit;

}

$tags = api_cron_tags_get(15);

if(empty($tags["autoload-cycles"])) {

	print "No autoload cycles found\n";
	exit;

}

$autoloadcycles = explode(",", $tags["autoload-cycles"]);

foreach($autoloadcycles as $input){

	$cycle = trim($input);

	$filename = "TFS-FLT-DNS-" . $cycle . "-" . date("dmY") . ".csv";

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
		$email["subject"] = "[ReachTEL] Auto-load error - Toyota Fleet - " . $filename;
		$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
		$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

		api_email_template($email);

		print "Failed to fetch file '" . $filename . "'\n";
		continue;

	} else print "OK\n";

	$contents = file_get_contents($path . $filename);

	if($contents === false) {

		unlink($path . $filename);

		print "Failed to open file\n";
		exit;
	}

	if(empty($contents)) {

		unlink($path . $filename);

		print "File empty. Skipping.\n";
		continue;
	}

	$types = array("SMS", "Email");

	foreach($types as $type) {

		print "Creating campaign...";

		$campaignname = "ToyotaFleet-" . $type . "-DNS-" . $cycle . "-" . date("dmY");
		print $campaignname;
		$exists = api_campaigns_checknameexists($campaignname);

		if(is_numeric($exists)) {

			unlink($path . $filename);

			print "Failed. The campaign already exists.\n";
			exit;

		}

		$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "ToyotaFleet-" . $type . "-DNS-" . $cycle . "-"));
		$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

		if(!is_numeric($campaignid)){

			print "Failed to create campaign\n";

		}

		print " OK\n";

		api_campaigns_tags_set($campaignid, array("PROC-DATE" => date("Ymd")));
		api_campaigns_tags_delete($campaignid, array("RETURNTIMESTAMP"));

		print "Uploading data...";

		$result = api_targets_fileupload($campaignid, $path . $filename, $filename);

		if(!is_array($result)){

			print "Failed to process file\n";

		}

		print "OK\n";

		print "Checking if we have an empty campaign...";

		$targets = api_data_target_status($campaignid);

		if($targets["READY"] == 0) { // Check if we have no targets

			print "It's empty - delete it.";

			api_campaigns_delete($campaignid);

			continue;

		} print "Nope...we have " . $targets["READY"] . " targets.\n";

		print "Activating campaign...";

		if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

			print "Failed!\n";
			exit;

		}

		print "OK\n";

	}

	unlink($path . $filename);

	print "Job done!\n";
}