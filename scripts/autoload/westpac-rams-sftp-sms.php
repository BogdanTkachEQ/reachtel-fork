<?php
require_once ("Morpheus/api.php");

use Services\Utils\CampaignUtils;

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

function extract_file_data(array $items, $format)
{
    return array_map(function ($item) use ($format) {
        return str_getcsv($item, $format);
    }, $items);
}

$regional_holidays = array();

function is_public_holiday($region) {
    global $regional_holidays;

    if (isset($regional_holidays[$region])) {
        return $regional_holidays[$region];
    }

	$holidays = explode(",", api_system_tags_get($region . "-public-holidays"));

	if (empty($holidays)) return ($regional_holidays[$region] = false);

	$timestamp = time();

	$dayofmonth = date("j", $timestamp);
	$monthofyear = date("n", $timestamp);

	foreach ($holidays as $holiday){

		$holiday = trim($holiday);

		if (empty($holiday)) continue;

		$time = strtotime($holiday);

		if ($time == false) continue;

		if ((date("j", $time) == $dayofmonth) && (date("n", $time) == $monthofyear)) {
			return ($regional_holidays[$region] = true);
		}

	}

	return ($regional_holidays[$region] = false);
}

if (empty($argv[1])) {
    print "Invalid filename specified\n";
    exit();
}

$filename = $argv[1];
print "**** WESTPAC RAMS SMS Autoload ****\n";
print "Date: " . date('d-m-Y') . "\n";
print "File: {$filename}\n\n";

$path = "/tmp/";

// Download File (file name format YYYYMMDDHHMMSS_RAMS_SMSText.txt)
$tags = api_cron_tags_get(82);
print "Downloading file...";

$options = array(
    "hostname" => $tags["sftp-hostname"],
    "username" => $tags["sftp-username"],
    "password" => $tags["sftp-password"],
    "localfile" => $path . $filename,
    "remotefile" => $tags["sftp-path"] . $filename
);

if (! api_misc_sftp_get($options)) {
    $email["to"] = $tags["sftp-failure-notification"];
    $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Auto-load error - WESTPAC RAMS SMS - " . $filename;
    $email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
    $email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
    api_email_template($email);

    print "Failed to fetch file '" . $filename . "'\n";
    exit();
} else
    print "OK\n";

// Extract file data
print "Reading data...";
$csv = extract_file_data(file($path . $filename), '|');

unlink($path . $filename);

if (! $csv || ! is_array($csv)) {

    print "Failed to read data file\n";
    exit();
}
// Remove first row of file
$filedate = array_shift($csv);
print " OK\n";

// Match campaign name based on SMS reference in data file & set header for input data
$header = [
    'Account Ref',
    'Date',
    'SMS Reference',
    'Product',
    'Phone Number',
    'Customer Full Name',
    'State of Residence',
    'Postcode',
    'First Name of Customer',
    'last 4',
    'Persuable Amount',
    'Collections Phone Number'
];

foreach ($csv as $row) {
    if (! strlen($row[0]))
        continue;

    $smstype = str_replace(' ', '', $row[2]);

    $i = 0;
    $rowtmp = [];
    foreach ($header as $h) {
        $rowtmp[$h] = $row[$i ++];
    }

    $campaigntypes[$smstype][] = $rowtmp;
    unset($rowtmp);
}

// Creating campaigns
$nbCampaigns = count($campaigntypes);
print "Creating {$nbCampaigns} campaigns:\n";
$time = time();

foreach ($campaigntypes as $campaigntype => $campaigndata) {
    if (! strlen($campaigntype))
        continue;
    $campaignname = "RAMS-SMS-" . date("jFy", $time) . "-" . str_replace(' ', '', $campaigntype);

    print "  * {$campaignname}\n";
    $exists = api_campaigns_checknameexists($campaignname);

    if (is_numeric($exists)) {
        print "     > Failed. The campaign '{$campaignname}' already exists.\n";
        continue;
    }

    // find previous campaigns
    $previouscampaigns = api_campaigns_list_all(true, null, null, array(
        "search" => "RAMS-SMS-*-" . $campaigntype
    ));

    if (empty($previouscampaigns)) {
        print "     > Failed to find a campaign to duplicate for '{$campaignname}'\n";
        continue;
    }

    // Create duplicate campaign from previous campaign
    $campaignid = api_campaigns_add($campaignname, 'sms', key($previouscampaigns));
    if (! is_numeric($campaignid)) {
        print "     > Failed to create campaign '{$campaignname}'\n";
        exit();
    }

    print "     > campaign created successfully\n";

    // Load data to campaign
    $notargets = count($campaigndata);
    print "     > Uploading {$notargets} targets \n";

    $index = [];
    $count = 0;
    $excluded_postcodes = isset($tags['excluded-postcodes']) ? explode(',', $tags['excluded-postcodes']) : [];
    foreach ($campaigndata as $row) {
        $destination = str_replace(' ','',$row['Phone Number']);

        if (in_array($row['Postcode'], $excluded_postcodes)) {
            print '     > !!! WARNING !!! Skipping target '.$destination.' because it is in '.$row['Postcode']."\n";
            continue;
        }

        if (is_public_holiday($row['State of Residence'])) {
            print '     > !!! WARNING !!! Skipping target '.$destination.' because it is a public holiday in '.$row['State of Residence']."\n";
            continue;
        }

        $targetkey = $row['Account Ref'];

        // Set correct index if '$targetkey' is duplicated in same campaign
        // This will avoid overwrite of target record
        if(array_key_exists ($targetkey, $index))
        {
            $index[$targetkey] = $index[$targetkey] + 1;
        }
        else {
            $index[$targetkey] = 1;
        }

        $targetkey .= '-'.$index[$targetkey];

        $targetid = api_targets_add_single($campaignid, $destination, $targetkey, null, $row);
        if ($targetid) {
            $count++;
        } else {
            print "     > !!! ERROR !!! Failed to create target '{$destination}'\n";
            continue;
        }
    }

    unset($index);

    // Find the first time period that applies to today and use number of hours to set send rate
    $timeperiods = api_restrictions_time_recurring_listall($campaignid);
    $today = 1 << strftime("%w");
    foreach ($timeperiods as $period) {
        $dayOfWeekBitwise = isset($period["daysofweek"]) && is_numeric($period["daysofweek"]) ? $period["daysofweek"] : CampaignUtils::TIMING_RECURRING_WEEKDAYS_BITWISE;
        if ($dayOfWeekBitwise & $today == $today) {
            $sendrate = ceil($count / ((strtotime($period["endtime"]) - strtotime($period["starttime"])) / 3600)) + 1;
            api_campaigns_setting_set($campaignid, "sendrate", $sendrate);
            print $sendrate . " message(s) per hour\n";
            break;
        }
    }

    /*
     * //TODO: Confirm if we need to dedupe data.
     * print " > Deduplicating campaign";
     *
     * api_targets_dedupe($campaignid);
     */

    print "     > Activating campaign";

    if (isset($tags["autoactivate"]) && ($tags["autoactivate"] == "true") && api_campaigns_setting_set($campaignid, "status", "ACTIVE")) {

        print "     > Activated!\n";
    } else {
        print "\n     > Auto-activate disabled\n";
    }
}
print "Job Done \n";
