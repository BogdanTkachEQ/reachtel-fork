<?php

use Services\Container\ContainerAccessor;
use Services\Reports\CsvArrayToFileConverter;

require_once("Morpheus/api.php");

$cronid = getenv(CRON_ID_ENV_KEY); // cron id for 'Autoload - NCML - WASH'
$groupowner = 167; // NCML group id

if(empty($argv[1])) {
    print "Invalid filename specified\n";
    exit;
}
$filename = $argv[1];

$tags = api_cron_tags_get($cronid);

preg_match('/^CBA_PING_[^_]+_PING_([^\.]+)\.txt$/', $filename, $matches);
if(!isset($matches[1])) {
	print "File type not found in filename {$filename}\n";
	exit;
}
$type = $matches[1];

$error = function($message, $email_content = null) use(&$tags, $filename) {
    $email = [];
    if (!$email_content) {
        $email_content = $message;
    }
    if (!$tags["sftp-failure-notification"]) {
        $email["to"] = "ReachTEL Support <support@ReachTEL.com.au>";
    } else {
        $email["to"] = $tags["sftp-failure-notification"];
        $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
    }
    $email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Auto-load error - NCML WASH - " . $filename;
    $email["htmlcontent"] = $email["textcontent"] = "Hello,\n\n{$email_content}\n";
    api_email_template($email);

    print("{$message}\n");
    exit;
};

if(!$tags){
    print("Failed to fetch tags for cron id#{$cronid}\n");
    exit;
}

$mandatory = [
    'csv-types',
    'sftp-in-hostname',
    'sftp-in-username',
    'sftp-in-password',
    'sftp-in-path',
    'csv-delimiter',
    'csv-header-targetkey',
    'timezone',
    'post-completion-hook',
];
foreach($mandatory as $k) {
    if (!isset($tags[$k])) {
        // email support only
        $tags["sftp-failure-notification"] = false;
        $error(
            "Failed. Mandatory tag '{$k}' is missing."
        );
        exit;
    }
}

date_default_timezone_set($tags['timezone']);

$filetypes = array_filter(
	array_map('trim', array_map('strtoupper', explode(',', $tags['csv-types'])))
);
if(!in_array($type, $filetypes)){
	print("Invalid NCML file type '{$type}'\n");
	print("Please choose one of the following: " . implode(', ', $filetypes));
	print("\n");
	exit;
}

print("**** NCML WASH Autoload ****\n");
print("Date: " . date('d-m-Y') . "\n");
print("File: {$filename}\n\n");
print("OK\n");

$path = sys_get_temp_dir() . '/';

print("Downloading file...");

$options = [
    "hostname" => $tags["sftp-in-hostname"],
	"username" => $tags["sftp-in-username"],
	"password" => $tags["sftp-in-password"],
    "localfile" => $path . $filename,
	"remotefile"=> $tags["sftp-in-path"] . $filename,
];

sleep(10);
if(!api_misc_sftp_get($options)){
    $error(
        "Failed to fetch file '{$filename}'",
        "The following file could not be downloaded from the specified server:\n\n{$filename}\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time."
    );
}
print("OK\n");

print("Reading data...");

$csv = array_map(function ($item) use($tags) {
    return str_getcsv($item, $tags["csv-delimiter"]);
}, file($path . $filename));

unlink($path . $filename);

if(!$csv || !is_array($csv)) {
    $error(
        "Malformed or empty CSV file '{$filename}'",
        "The following CSV file is malformed or empty:\n\n{$filename}\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time."
    );
}
print(" OK\n");

print("Checking CSV headers... ");
$headers = array_shift($csv);
$headers[1] = 'Phone';
foreach(['targetkey'] as $k) {
    $tk = "csv-header-{$k}";

    if (!in_array($tags[$tk], $headers)) {
        // email support only
        $tags["sftp-failure-notification"] = false;
        $error(
            "Failed. CSV is missing {$k} header '{$tags[$tk]}'."
        );
        exit;
    }
}

print(" OK\n");

print("Creating campaign... ");
$campaign_prefix = "NCML-WASH-{$type}-";
$campaignname = $campaign_prefix . date("Ymd");
$exists = api_campaigns_checknameexists($campaignname);

if(is_numeric($exists)) {
    // email support only
    $tags["sftp-failure-notification"] = false;
    $error(
        "Failed. The campaign '{$campaignname}' already exists.\n"
    );
    exit;
}

$previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "{$campaign_prefix}*"));
if(!$previouscampaigns){
    // email support only
    $tags["sftp-failure-notification"] = false;
    $error(
        "Failed to find a campaign with prefix '{$campaign_prefix}' to duplicate from.\n"
    );
}

$campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));
if(!is_numeric($campaignid)){
    // email support only
    $tags["sftp-failure-notification"] = false;
    $error(
        "Failed to create campaign '{$campaignname}'.\n"
    );
}
api_campaigns_setting_set($campaignid, 'groupowner', $groupowner);
api_campaigns_setting_set($campaignid, 'timezone', $tags['timezone']);

if (! empty($tags['sftp-out-hostname']) && ! empty($tags['sftp-out-username']) && ! empty($tags['sftp-out-path'])) {
    api_campaigns_setting_set($campaignid, 'noreport', 'off');
    api_campaigns_setting_set($campaignid, 'sftpreport', 'on');
    api_campaigns_tags_set($campaignid, [
        'cron-id' => $cronid,
        'sftp-hostname' => $tags['sftp-out-hostname'],
        'sftp-username' => $tags['sftp-out-username'],
        'sftp-password' => $tags['sftp-out-password'],
        'sftp-path' => $tags['sftp-out-path'],
    ]);
}
print("OK\n");

$count = count($csv);
print("Uploading data ({$count} rows)...");

foreach($csv as $row) {
    $row = array_combine($headers, $row);
    if (!api_targets_add_single($campaignid, $row['Phone'], $row[$tags['csv-header-targetkey']], 1)) {
        $badRecords[] = array_values($row);
    }
}

if (
    isset($badRecords) &&
    $badRecords &&
    isset($tags['badrecords-sftp-username']) &&
    isset($tags['badrecords-sftp-password']) &&
    isset($tags['badrecords-sftp-path'])
) {
    $badRecords = array_merge([$headers], $badRecords);
    $tempfname = tempnam(FILEPROCESS_TMP_LOCATION, "badrecordsncmlwash");
    $badRecordsCsv = ContainerAccessor::getContainer()
        ->get(CsvArrayToFileConverter::class)
        ->convertArrayToFile($badRecords, $tempfname);

    print "Sending bad records to sftp\n";
    $pathinfo = pathinfo($filename);
    $badRecordFilename = $pathinfo['filename'] . '_badrecords.csv';

    print "Connecting...\n";

    $options = [
        "hostname" => $tags["badrecords-sftp-hostname"],
        "username" => $tags["badrecords-sftp-username"],
        "password" => $tags["badrecords-sftp-password"],
        "localfile" => $tempfname,
        "remotefile" => $tags["badrecords-sftp-path"] . $badRecordFilename
    ];

    $result = api_misc_sftp_put_safe($options);
    unlink($tempfname);

    if (!$result) {
        print "Uploading bad records failed!\n";
    } else {
        print "Uploading bad records successful\n";
    }
}

print("OK\n");

if(isset($tags["autoactivate"]) && ($tags["autoactivate"] == "true")){
    print("Activating campaign...");
    if (!api_campaigns_setting_set($campaignid, "status", "ACTIVE")) {
        // email support only
        $tags["sftp-failure-notification"] = false;
        $error(
            "Failed activating campaign '{$campaignname}' (id#{$campaignid}).\n"
        );
    }
    print("OK\n");

}

print("Job done!\n");
