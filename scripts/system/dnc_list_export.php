<?php

require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if (!isset($tags['sftp-hostname'])) {
    print "sftp-hostname must be set, exiting.\n";
    exit;
}

if (!isset($tags['sftp-username'])) {
    print "sftp-username must be set, exiting\n";
    exit;
}

if (!isset($tags['sftp-password'])) {
    print "sftp-password must be set, exiting\n";
    exit;
}

if (!isset($tags['sftp-path'])) {
    print "sftp-path must be set, exiting\n";
    exit;
}

if (!isset($tags['list-id'])) {
    print "list id must be set, exiting\n";
    exit;
}

print "Fetching data...\n";
$dnclist = api_restrictions_donotcontact_exportlist($tags['list-id']);

if (!$dnclist) {
    print "No items found for the list id supplied, exiting\n";
    exit;
}

print "Fetching data completed.\nUploading file...\n";

$csvData = 'timestamp,type,destination' . "\n" . api_csv_string($dnclist);

$filename = "DNCList-" . $tags['list-id'] . "-" . date("dMy") . ".csv";
$tempfname = tempnam("/tmp", "dnclist");

file_put_contents($tempfname, $csvData);
$options = ["hostname" => $tags["sftp-hostname"], "username" => $tags["sftp-username"],
    "password" => $tags["sftp-password"], "localfile" => $tempfname,
    "remotefile" => $tags["sftp-path"] . $filename];

if (!api_misc_sftp_put_safe($options)) {
    print "Failed to upload to sftp\n";
}

print "Uploaded file " . $filename . " to sftp.\n";
unlink($tempfname);

print "Job done";
