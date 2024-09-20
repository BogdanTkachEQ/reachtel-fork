<?php

require_once("Morpheus/api.php");

if(empty($argv[1])) {
    print "Invalid filename specified\n";
    exit;
}

$filename = $argv[1];
$logs = "**** NCML SMS Autoload ****\n";
$logs .= "Date: " . date('d-m-Y') . "\n";
$logs .= "File: {$filename}\n\n";

// If the filename ends in ".filepart" strip this from the file name
if(preg_match("/filepart$/i", $filename)) {
    $filename = substr($filename, 0, -9);
}

$tags = api_cron_tags_get(71);

date_default_timezone_set('Australia/Adelaide');

$path = "/tmp/";

printlogs("Downloading file...");

sleep(10);
$options = array("hostname" => $tags["sftp-hostname"],
	"username" => $tags["sftp-username"],
	"password" => $tags["sftp-password"],
	"localfile" => $path . $filename,
	"remotefile"=> $tags["sftp-path"] . $filename);

if(!api_misc_sftp_get($options)){
    $email["to"]      = $tags["sftp-failure-notification"];
    $email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Auto-load error - NCML SMS - " . $filename;
    $email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
    $email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
    api_email_template($email);

    print "Failed to fetch file '" . $filename . "'\n";
    exit;
} else print "OK\n";

printlogs("Reading data...");

$csv = array_map('str_getcsv', file($path . $filename));

unlink($path . $filename);

if(!$csv || !is_array($csv)) {

	print "Failed to read data file\n";
	exit;
}
printlogs("Reading data...");
printlogs(" OK\n");

// array of unique campaigns
$campaigns = [];
$keys = array_shift($csv);
foreach($csv as $campaign) {
    if (count($keys) != count($campaign)) {
        print " > Failed: Wrong CSV structure for campaign row:\n";
        print implode(',', $campaign) . "\n";
        exit;
    }
    $campaign = array_combine($keys, $campaign);

    if (!isset($campaign['Campaign']) || !$campaign['Campaign']) {
        print " > Failed: 'Campaign' value is missing or empty for row:\n";
        print implode(',', $campaign) . "\n";
        exit;
    }

    $campaigns[trim(str_replace(['_', 'NCML-SMS-'], ['-', ''], $campaign['Campaign']))][] = $campaign;
}

$nbCampaigns = count($campaigns);
printlogs("Creating {$nbCampaigns} campaigns:\n");
$time = time();

foreach($campaigns as $name => $data) {
    $campaignname = "NCML-SMS-" . date("jFy", $time) . "-$name";
    printlogs("  * {$campaignname}\n");
    $exists = api_campaigns_checknameexists($campaignname);

    if(is_numeric($exists)) {
        print "     > Failed. The campaign '{$campaignname}' already exists.\n";
        exit;
    }

    $previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "NCML-SMS-*-" . $name));

    if(empty($previouscampaigns)) {
        $email["to"]      = $tags["sftp-failure-notification"];
        $email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["subject"] = "[ReachTEL] Auto-load error - NCML SMS - " . $filename;
        $email["textcontent"] = "Hello,\n\nThe data files contains a new campaign that we don't have business rules for:\n\n" . $name . "\n\nThe auto-load process has failed. Please advise ReachTEL Support of the relevant business rules.";
        $email["htmlcontent"] = "Hello,\n\nThe data files contains a new campaign that we don't have business rules for:\n\n" . $name . "\n\nThe auto-load process has failed. Please advise ReachTEL Support of the relevant business rules.";
        api_email_template($email);

        print "     > Failed to find a campaign to duplicate for '{$campaignname}'\n";
        exit;
    }

    $campaignid = api_campaigns_add($campaignname, 'sms', key($previouscampaigns));
    if(!is_numeric($campaignid)){
        printlogs("     > Failed to create campaign '{$campaignname}'\n");
        exit;
    }

    printlogs("     > campaign created successfully\n");

    $ndData = count($data);
    printlogs("     > Uploading {$ndData} targets\n");

    // Upload data
    foreach($data as $i => $row) {
        $i++;
        $destination = $row['M.PHONE'];
        $targetkey = $row['Key'];
        unset($row['Campaign'], $row['M.PHONE'], $row['Key']);
        $targetid = api_targets_add_single(
            $campaignid,
            $destination,
            $targetkey,
            1,
            $row
        );
        if (!$targetid) {
            printlogs("     > !!! ERROR !!! Failed to create target '{$destination}'\n", true);
            continue;
        }
    }

    printlogs("     > Deduplicating campaign");

    api_targets_dedupe($campaignid);

    printlogs("     > Activating campaign");

    if(isset($tags["autoactivate"]) && ($tags["autoactivate"] == "true") && api_campaigns_setting_set($campaignid, "status", "ACTIVE")){

        printlogs("     > Activated!\n");

    } else {
        printlogs("\n     > Auto-activate disabled\n");
    }

    printlogs("\n");
}

printlogs("OK\n");
printlogs("File auto-loaded successfully\n");
printlogs("Job done!\n\n");

print "Sending confirmation successful email to {$tags["sftp-failure-notification"]}\n";
$email["to"]      = $tags["sftp-failure-notification"];
$email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
$email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
$email["subject"] = "[ReachTEL] Auto-load successful - " . date('d-m-Y') . " - NCML SMS - " . $filename;
$email["textcontent"] = strip_tags($logs);
$email["htmlcontent"] = nl2br($logs);
api_email_template($email);
printlogs("Email sent!\n\n");


function printlogs($message, $error = false) {
    global $logs;
    if ($error) {
        $message = "<span style=\"color: red;\"><b>{$message}</b></span>";
    }
    print strip_tags($message);
    $logs .= $message;
}