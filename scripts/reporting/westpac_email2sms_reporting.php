#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$groupid = 273;
$tags = api_cron_tags_get(42);

if (isset($tags['run-date'])) {
	try {
		$start = new DateTime($tags['run-date']);
	} catch (Exception $e) {
		print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
} else {
	$start = new DateTime('yesterday 00:00:00');
}

if (isset($tags['end-run-date'])) {
	try {
		$end = new DateTime($tags['end-run-date']);
	} catch (Exception $e) {
		print "Invalid end run date given:  '" . $tags['end-run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
		exit;
	}
} else {
	$end = new DateTime('yesterday 23:59:59');
}

$sql = "SELECT * FROM `sms_api_mapping` WHERE `timestamp` > ? AND `timestamp` < ? AND `userid` IN (";
$variables = array($start->format("Y-m-d H:i:s"), $end->format("Y-m-d H:i:s"));

foreach (api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid) {
	if (api_security_check(125, null, true, $userid)) {
		$sql .= "?, ";
		$variables[] = $userid;

		$userdata[$userid] = api_users_setting_getall($userid);
	}
}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";

$rs = api_db_query_read($sql, $variables);

$contents = "timestamp,userid,from,destination,message,status,username,email\n";

while (!$rs->EOF) {
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

	$contents .= $rs->Fields("timestamp") . ","
        . $rs->Fields("userid")
		. $rs3->Fields("sms_account")
        . $from["fnn"] . ","
		. $rs3->Fields("contents")
		. $status . ","
        . "\"" . $userdata[$rs->Fields("userid")]["username"] . "\","
        . "\"" . $userdata[$rs->Fields("userid")]["emailaddress"] . "\","
        . "\n";

	$rs->MoveNext();
}

$filename = "ReachTEL-Westpac-Email2SMS-" . date("Ymd") . ".csv";

$email["to"]          = $tags["reporting-destination"];
$email["subject"]     = "[ReachTEL] Westpac Email-2-SMS - Daily report - " . $start->format('d/m/Y') . " to " . $end->format('d/m/Y');
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL Email-2-SMS report for the period between " . $start->format('d/m/Y') . " and " . $end->format('d/m/Y') . ".\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL Email-2-SMS report for period between " . $start->format('d/m/Y') . " and " . $end->format('d/m/Y') . ".<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => $filename);

api_email_template($email);

$tmpfname = tempnam("/tmp", "westpacsftp");

file_put_contents($tmpfname, $contents);

print "Connecting...\n";

$options = [
	"hostname"  => $tags["sftp-hostname"],
	"username"  => $tags["sftp-username"],
	"password"  => $tags["sftp-password"],
	"localfile" => $tmpfname,
	"remotefile" => $tags["sftp-path"] . $filename
];

$result = api_misc_sftp_put_safe($options);

if (!$result) {
	print "Failed to upload Westpac Email2SMS report to sFTP server";
	exit;
} else {
	print "Upload to Westpac sFTP seems to have worked:\n";
}

unlink($tmpfname);
