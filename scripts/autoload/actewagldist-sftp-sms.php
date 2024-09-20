<?php

// Should run at 09:50am Canberra time Monday to Friday

require_once("Morpheus/api.php");

if(date("N") > 5){

	print "Stopping because it is a weekend\n";
	exit;

}

if(api_misc_ispublicholiday()){

        print "Stopping because it is a National public holiday\n";
        exit;

}

if(api_misc_ispublicholiday("NSW")){

        print "Stopping because it is a NSW public holiday\n";
        exit;

}

if(api_misc_ispublicholiday("ACT")){

        print "Stopping because it is an ACT public holiday\n";
        exit;

}

$tags = api_cron_tags_get(18);

if(empty($tags["autoload-cycles"])) {

	print "No autoload cycles found\n";
	exit;

}

$autoloadcycles = explode(",", $tags["autoload-cycles"]);

foreach($autoloadcycles as $cycle){

	$time = time();

	$cycle = trim($cycle);

	$filename = "ActewAGL Dist_" . date("dmy", $time) . "_" . $cycle . ".csv";

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
		$email["subject"] = "[ReachTEL] Auto-load error - ActewAGL Dist - " . $filename;
		$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
		$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

		api_email_template($email);

		print "Failed to fetch file '" . $filename . "'\n";
		exit;

	} else print "OK\n";

	print "Creating campaign...";

	$campaignname = "ActewAGLDist-" . date("dmy", $time) . "-" . $cycle;
	print $campaignname;
	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		unlink($path . $filename);

	        print "Failed. The campaign already exists.\n";
	        exit;

	}

	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "ActewAGLDist-*-" . $cycle));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

	if(!is_numeric($campaignid)){

		unlink($path . $filename);

		print "Failed to create campaign\n";
		exit;

	}

	print " OK\n";

	api_campaigns_tags_set($campaignid, array("PROC-DATE" => date("Ymd")));
	api_campaigns_tags_delete($campaignid, array("RETURNTIMESTAMP"));

	print "Uploading data...";

	$result = api_targets_fileupload($campaignid, $path . $filename, $filename);

	if(!is_array($result)){

		unlink($path . $filename);

		print "Failed to process file\n";
		exit;

	}

	print "OK\n";

	unlink($path . $filename);

	print "Activating campaign...";

	if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

		print "Failed!\n";
		exit;

	}

	print "OK\n";

	print "Job done!\n";
}