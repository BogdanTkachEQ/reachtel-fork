#!/usr/bin/php
<?php

/**
 *
 * Tags:
 * groupid
 * run-date
 * end-run-date
 * reporting-destination
 * sftp-out-hostname
 * sftp-out-username
 * sftp-out-password
 * sftp-out-path
 * sftp-failure-notification
 * failure-notification-email
 *
 *
 * $tags['groupid'] = 2;
 * $tags['reporting-destination'] = 'phillip.berry@equifax.com';
 * $tags['run-date'] = "today";
 * $tags['end-run-date'] = "tomorrow";
 *
 */

require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

$groupid = isset($tags['groupid']) ? $tags['groupid'] : null;

if (!$groupid) {
    print __FILE__." requires a group id";
    exit(1);
}

$group_name = api_groups_setting_getsingle($groupid, "name");
if (!$group_name) {
    print __FILE__." {$groupid} as a group does not exist";
    exit(1);
}

if (!isset($tags["reporting-destination"]) && !isset($tags["sftp-hostname"])) {
    print __FILE__." requires tags: 'reporting-destination' or 'sftp-hostname'";
    exit(1);
}

if (isset($tags["sftp-hostname"])) {
    foreach(['sftp-hostname', 'sftp-username', 'sftp-password', 'sftp-path', 'failure-notification-email'] as $tagname) {
        if(!isset($tags[$tagname])) {
            print "Please define a '{$tagname}' tag\n";
            exit(1);
        }
    }
}

if (isset($tags['run-date'])) {
	try {
		$start = new DateTime($tags['run-date']);
	} catch (Exception $e) {
		print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit(1);
	}
} else {
	$start = new DateTime('yesterday 00:00:00');
}

if (isset($tags['end-run-date'])) {
	try {
		$end = new DateTime($tags['end-run-date']);
	} catch (Exception $e) {
		print "Invalid end run date given:  '" . $tags['end-run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
        exit(1);
	}
} else {
	$end = new DateTime('yesterday 23:59:59');
}

$sql = "SELECT * FROM `sms_api_mapping` WHERE `timestamp` > ? AND `timestamp` < ? AND `userid` IN (";
$variables = array($start->format("Y-m-d H:i:s"), $end->format("Y-m-d H:i:s"));

$userdata = [];
foreach (api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid) {
	if (api_security_check(125, null, true, $userid)) {
		$sql .= "?, ";
		$variables[] = $userid;
		$userdata[$userid] = api_users_setting_getall($userid);
	}
}

if (empty($userdata)) {
    print "No user ids available for the report";
    exit(1);
}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

$contents = "timestamp,userid,from,destination,message,status,username,email\n";

while ($rs && !$rs->EOF) {
	$sql = "SELECT * FROM `sms_sent` WHERE `eventid` = ?";
	$rs3 = api_db_query_read($sql, array($rs->Fields("rid")));

	$sql = "SELECT `status` FROM `sms_status` WHERE `eventid` = ? ORDER BY `timestamp` DESC";
	$rs2 = api_db_query_read($sql, array($rs->Fields("rid")));

	if ($rs2->RecordCount() > 0) {
		$status = $rs2->Fields("status");
	} else {
		$status = "SENT";
	}

	$from = api_data_numberformat($rs3->Fields("to"));
    $did_name = api_sms_dids_setting_getsingle($rs3->Fields("sms_account"), "name") ?: "UNKNOWN";

	$contents .= $rs->Fields("timestamp") . ","
        . $rs->Fields("userid").","
		. $did_name . ","
        . $from["fnn"] . ","
		. "\"" . $rs3->Fields("contents"). "\","
		. $status . ","
        . "\"" . $userdata[$rs->Fields("userid")]["username"] . "\","
        . "\"" . $userdata[$rs->Fields("userid")]["emailaddress"] . "\""
        . "\n";

	$rs->MoveNext();
}

$escaped_group_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $group_name);
$filename = "ReachTEL-Email2SMS-{$escaped_group_name}-" . date("Ymd") . ".csv";

if (isset($tags["reporting-destination"])) {
    $email["to"] = $tags["reporting-destination"];
    $email["subject"] = "[ReachTEL] Email-2-SMS - report - " . $start->format('d/m/Y') . " to " . $end->format('d/m/Y');
    $email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL Email-2-SMS report for the period between " . $start->format('d/m/Y') . " and " . $end->format('d/m/Y') . ".\n\n";
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

    $email["attachments"][] = array("content" => $contents, "filename" => $filename);

    api_email_template($email);
}

if (isset($tags["sftp-hostname"])) {
    $tmpfname = tempnam("/tmp", "email2sms");

    file_put_contents($tmpfname, $contents);

    print "Connecting...\n";

    $options = [
        "hostname" => $tags["sftp-hostname"],
        "username" => $tags["sftp-username"],
        "password" => $tags["sftp-password"],
        "localfile" => $tmpfname,
        "remotefile" => $tags["sftp-path"] . $filename
    ];

    $result = api_misc_sftp_put_safe($options);
    unlink($tmpfname);
    if (!$result) {
        failure_notification($tags['failure-notification-email'], "SFTP Failure", "Failed to upload Email2SMS report to sFTP server: {$filename}");
        exit(1);
    } else {
        print "Upload to sFTP seems to have worked:\n";
    }
}

function failure_notification($to, $subject, $error) {
    $email["to"] = $to;
    $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Email-2-SMS - {$subject}";
    $email["textcontent"] = "Hello,\n\nThere was an error with this report: $error\n";
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    api_email_template($email);
    print "$error";
}

