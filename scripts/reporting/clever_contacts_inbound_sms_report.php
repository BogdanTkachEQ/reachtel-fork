<?php

require_once("Morpheus/api.php");

$cronId = 119;
$tags = api_cron_tags_get($cronId);

if (!isset($tags['dids'])) {
    print "Failed to run as DIDs not set in the tags";
    exit;
}

$dids = explode(',', $tags['dids']);

$smsDids = api_sms_dids_setting_get_multi_byid($dids, 'name');

if (!$smsDids) {
    print 'DIDS not found';
    exit;
}

$intervalDays = isset($tags['interval_days']) ? $tags['interval_days'] : 7;

print "Generating report\n";

$smsReceived = api_sms_get_received_sms_history(array_keys($smsDids), [], $intervalDays);

if (!$smsReceived) {
    print "No data received";
    exit;
}

$data = [['Sender', 'Keyword', 'DID', 'Timestamp']];

foreach ($smsReceived as $received) {
    $data[] = [$received['from'], $received['contents'], $smsDids[$received['sms_account']], $received['timestamp']];
}

$contents = api_csv_string($data);

$tempfname = tempnam("/tmp", "ReachTEL-CleverContacts-Fortnightly");

if(!file_put_contents($tempfname, $contents)) {
    print "Failed to write to tmp file";
    exit;
}

print "Uploading to sftp\n";

$filename = "ReachTEL-CleverContacts-Fortnightly-" . date("YmdHis") . ".csv";

$options = [
    "hostname"  => $tags["sftp-hostname"],
    "username"  => $tags["sftp-username"],
    "password"  => $tags["sftp-password"],
    "localfile" => $tempfname,
    "remotefile" => $tags["sftp-path"] . $filename
];

$result = api_misc_sftp_put_safe($options);

unlink($tempfname);

if(!$result) {
    print "Failed to upload to SFTP\n";
    exit;
}

print "Job done";
