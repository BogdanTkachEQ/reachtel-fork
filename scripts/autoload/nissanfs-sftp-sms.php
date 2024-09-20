<?php

// Should run at 12:10 Sydney time Monday to Friday

require_once("Morpheus/api.php");

date_default_timezone_set('Australia/Melbourne');

$groupid = 325;

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

$tags = api_cron_tags_get(19);

if(empty($tags["autoload-cycles"])) {

	print "No autoload cycles found\n";
	exit;

}

$autoloadcycles = explode(",", $tags["autoload-cycles"]);

foreach($autoloadcycles as $input){

	$input = trim($input);

	$time = time();

	$filename = "SMS " . $input . " " . date("dmY", $time) . ".xlsx.pgp";

	$path = "/tmp/";

	print "Downloading file " . $filename . "...";

	$options = array("hostname" => "sftp.reachtel.com.au",
		"username" => "reachtelautomation",
		"localfile" => $path . $filename,
		"remotefile"=> $tags["sftp-path"] . $filename);

	if(!api_misc_sftp_get($options)){

		$email["to"]      = $tags["sftp-failure-notification"];
		$email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["subject"] = "[ReachTEL] Auto-load error - Nissan Financial Services - " . $filename;
		$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
		$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
		api_email_template($email);

		print "Failed to fetch file '" . $filename . "'\n";
		continue;

	} else print "OK\n";

	print "Creating campaign...";

	$campaignname = "NissanFinancialServices-" . date("jFY", $time) . "-SMS-" . $input . "-1";

	print $campaignname;

	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		unlink($path . $filename);

	        print "Failed. The campaign already exists.\n";
	        exit;

	}

	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "NissanFinancialServices-*-SMS-" . $input));
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

	print "Setting the pacing...";

	$targets = api_data_target_status($campaignid);

	$timeperiods = api_restrictions_time_recurring_listall($campaignid);

	$starttime = time();
	$endtime = strtotime("00:00:00");

	foreach($timeperiods as $periodid => $period){

	        if(strtotime($period["starttime"]) > $starttime) $starttime = strtotime($period["starttime"]);
	        if(strtotime($period["endtime"]) > $endtime) $endtime = strtotime($period["endtime"]);

	}

	$sendrate = ceil($targets["READY"] / (($endtime - $starttime)/3600)) + 1;

	api_campaigns_setting_set($campaignid, "sendrate", $sendrate);

	print $sendrate . " message per hour\n";

	print "Activating campaign...";

	if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

		print "Failed!\n";
		exit;

	}

	print "OK\n";

	print "Job done!\n";

}