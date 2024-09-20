#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$cron_id = getenv('CRON_ID');

$tags = api_cron_tags_get($cron_id);

if ((!isset($tags["reporting-destination"]) && !isset($tags["sftp-hostname"]))) {
    print __FILE__." requires either reporting-destination or sftp-hostname tag";
    exit;
}

if (isset($tags["sftp-hostname"])) {
    foreach(['sftp-hostname', 'sftp-username', 'sftp-password', 'sftp-path', 'failure-notification-email'] as $tagname) {
        if(!isset($tags[$tagname])) {
            print "Please define a '{$tagname}' tag\n";
            exit;
        }
    }
}

if (array_diff(['groupid', 'subject'], array_keys($tags))) {
    print 'Mandatory tags missing';
    api_error_raise('Mandatory tags missing in cron id ' . $cron_id);
    exit;
}

$start = new DateTime(isset($tags['start']) ? $tags['start'] : 'Yesterday 00:00:00');
$end = new DateTime(isset($tags['end']) ? $tags['end'] : 'Yesterday 23:59:59');

$users = api_users_list_all_by_groupowner($tags['groupid']);

if(isset($tags['status-run-date']) && isset($tags['status-end-date'])) {
    $data = api_reports_rest_api_sms_report($users, $start, $end, true, new DateTime($tags['status-run-date']), new DateTime($tags['status-end-date']));
}
else {
    $data = api_reports_rest_api_sms_report($users, $start, $end);
}

if (!$data) {
    print "No data found for the dates passed";
    exit;
}
$headers = array_keys($data[0]);
$csv_contents = api_csv_string(array_merge([$headers], $data));

if (isset($tags["sftp-hostname"])) {
    $filenamePrefix = isset($tags['filename-prefix']) ? $tags['filename-prefix'] : 'ReachTel_rest_sms_report_';
    $filenameDate = isset($tags['filename-date-format']) ? date($tags['filename-date-format']) : date("Ymd");
    $filename = $filenamePrefix . $filenameDate . ".csv";
    $tempfname = tempnam(FILEPROCESS_TMP_LOCATION, "restsmsreport");

    file_put_contents($tempfname, $csv_contents);

    print "Connecting...\n";

    $options = [
        "hostname" => $tags["sftp-hostname"],
        "username" => $tags["sftp-username"],
        "password" => $tags["sftp-password"],
        "localfile" => $tempfname,
        "remotefile" => $tags["sftp-path"] . $filename
    ];

    $result = api_misc_sftp_put_safe($options);
    unlink($tempfname);

    if (!$result) {
        print "Upload to sftp failed!\n";
        failure_notification($tags['failure-notification-email'], "SFTP Failure", "Failed to upload cumulative campaign report to sFTP server: {$filename}");
    } else {
        print "Upload to sFTP seems to have worked:\n";
    }
}

if (isset($tags['reporting-destination'])) {
    print "Sending report via email\n";

    $email["to"]	      = $tags["reporting-destination"];
    $email["subject"]     = "[ReachTEL] " . $tags['subject'] . " SMS traffic report - " . $start->format('d/m/Y') . " to " . $end->format('d/m/Y');
    $email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL SMS traffic report for the period between " . $start->format('d-m-Y H:i:s') . " and " . $end->format('d-m-Y H:i:s') . ".\n\n";
    $email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL SMS traffic report for period between " . $start->format('d-m-Y H:i:s') . " and " . $end->format('d-m-Y H:i:s') . ".<br /><br />";
    $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

    $email["attachments"][] = array("content" => $csv_contents, "filename" => "ReachTEL-SMS-Traffic-" . date("Ymd") . ".csv");

    api_email_template($email);

    print "Report sent via email";
}

