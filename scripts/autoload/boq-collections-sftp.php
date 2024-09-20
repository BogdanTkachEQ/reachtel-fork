<?php

require_once("Morpheus/api.php");

if(empty($argv[1])) {
	print "Invalid filename specified\n";
	exit;
}

$filename = $argv[1];

$path = "/tmp/";

print "Downloading file...";

$tags = api_cron_tags_get(55);

$options = array("hostname" => $tags["sftp-hostname"],
	 "username" => $tags["sftp-username"],
	 "password" => $tags["sftp-password"],
	 "localfile" => $path . $filename,
	 "remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){

	$email["to"]      = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"] = "[ReachTEL] Auto-load error - BOQ Collections - Early SMS - " . $filename;
	$email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
	$email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";

	api_email_template($email);

	print "Failed to fetch file '" . $filename . "'\n";
	exit;

} else print "OK\n";

if(preg_match("/RTOF/", $filename)) {

	// Process the VRS file type

	print "Creating campaign...";

	foreach(array("SMS", "Voice") as $type) {

		$campaignname = "BOQ-Collections-" . date("jFY") . "-Arrears-" . $type;
		print $campaignname;
		$exists = api_campaigns_checknameexists($campaignname);

		if(is_numeric($exists)) {

			unlink($path . $filename);

		    print "Failed. The campaign already exists.\n";
		    exit;
		}

		$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "BOQ-Collections*Arrears-" . $type));
		$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
	}

} else if (preg_match("/SMSCH/", $filename)) {
    
    print "Adding header row...";
    
    $contents = file_get_contents($path . $filename);
    
    if($contents === false) {
        
        unlink($path . $filename);
        
        print "Failed to open file\n";
        exit;
    }
    
    // Decrypt the PGP contents
    $contents = api_misc_pgp_decrypt($contents);
    
    if(empty($contents)) {
        
        unlink($path . $filename);
        
        print "File empty. Skipping.\n";
        continue;
    }
    
    unlink($path . $filename);
    
    $filename = substr($filename, 0, -4);
    
    $contents = "AccountID,destination,message\n" . $contents;
    
    if(file_put_contents($path . $filename, $contents)) print "OK\n";
    
    print "Creating campaign...";
    
    $campaignname = "BOQ-Collections-Custom-SMS-" . date("dmY");
    print $campaignname;
    $exists = api_campaigns_checknameexists($campaignname);
    
    if(is_numeric($exists)) {
        
        unlink($path . $filename);
        
        print "Failed. The campaign already exists.\n";
        exit;
    }
    
    $previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "BOQ-Collections-Custom-SMS-"));
    $campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
    
} else if(preg_match("/^(RPP\d+)_(.+)\.csv\.pgp$/", $filename, $matches)) {

	// Process the CardIssuance file type

	$cardissuance = true;

	print "Creating campaign...";

	$matches[1] = str_replace("_", "-", $matches[1]);

	$campaignname = "BOQ-CardIssuance-" . date("Ymd") . "-" . $matches[1];

	print $campaignname;
	$exists = api_campaigns_checknameexists($campaignname);

	if(is_numeric($exists)) {

		unlink($path . $filename);

	    print "Failed. The campaign already exists.\n";
	    exit;
	}

	$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "BOQ-CardIssuance-*-" . $matches[1]));
	$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

	// Delay campaign sending for 3 business days

	// -	Remove previous time periods
	foreach(api_restrictions_time_recurring_listall($campaignid) as $periodid => $period) api_restrictions_time_recurring_remove($campaignid, $periodid);
	foreach(api_restrictions_time_specific_listall($campaignid) as $periodid => $period) api_restrictions_time_specific_remove($campaignid, $periodid);

	$plus3days = api_misc_addbusinessdays(time(), 3);

	$start = strtotime(date("Y-m-d 10:00:00", $plus3days));
	$finish = strtotime(date("Y-m-d 17:00:00", $plus3days));

	api_restrictions_time_specific_add($campaignid, $start, $finish);

	// Set the correct billing month
	api_campaigns_setting_set($campaignid, "billingmonth", date("Y-m", $plus3days));

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

if($settings["type"] == "phone") {

	print "Deduplicating campaign...";

	api_targets_dedupe($campaignid);

	print "OK\n";
}

print "Setting the pacing...";

$targets = api_data_target_status($campaignid);

$secondsRemaining = api_restrictions_time_remaining($campaignid);

// If the campaign is a phone campaign, adjust the pacing to take into account the maximum number of reattempts (minus 60 seconds for some wiggle room)
if($settings["type"] == "phone") {

	if($settings["ringoutlimit"] >= $settings["retrylimit"]) $reattemptPeriod = ($settings["ringoutlimit"] * $settings["redialtimeout"] * 60) - 60;
	else $reattemptPeriod = ($settings["retrylimit"] * $settings["redialtimeout"] * 60) - 60;

	// We should only adjust the secondsRemaining for reattempts if we have enough time remaining
	if($reattemptPeriod < $secondsRemaining) $secondsRemaining = $secondsRemaining - $reattemptPeriod;

} else if(isset($cardissuance)) {
	$secondsRemaining = 7 * 3600;
}

if(!is_numeric($secondsRemaining) OR ($secondsRemaining <= 0)) $secondsRemaining = 60;

$sendrate = ceil(($targets["READY"]+$targets["REATTEMPT"]) / ($secondsRemaining / 3600)) + 1;

if(!is_numeric($sendrate)) $sendrate = 0;
else if($sendrate > 5000) $sendrate = 5000; // Cap out the send rate at 5k per hour to prevent stupidly large numbers

api_campaigns_setting_set($campaignid, "sendrate", $sendrate);

print $sendrate . " message per hour\n";

print "Activating campaign...";

if(!api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

	print "Failed!\n";
	exit;

}

print "OK\n";

print "Job done!\n";
