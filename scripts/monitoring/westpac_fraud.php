<?php

require_once("Morpheus/api.php");

$tags = api_cron_tags_get(98);

$default_recipients = isset($tags['email']) ? $tags['email'] : 'support@reachtel.com.au';

if (empty($tags['alert-interval']) || !preg_match('/^[0-9]+$/', $tags['alert-interval'])) {
	$tags['alert-interval'] = 15;
}

print "Checking for messages sent/received in last {$tags['alert-interval']} minutes...\n";

$message = '';

$sql = "SELECT COUNT(*) AS received FROM sms_received WHERE sms_account = 318 AND timestamp > DATE_SUB(NOW(), INTERVAL {$tags["alert-interval"]} MINUTE)";
$rs = api_db_query_read($sql);
if (!$rs || empty($rs->Fields("received"))) {
	// Unable to confirm inbound messages
	$message .= "Unable to confirm inbound messages in last {$tags["alert-interval"]} minutes.\n";
	print "Unable to confirm inbound messages!\n";
} else {
	print "Found {$rs->Fields("received")} inbound messages.\n";
}

$sql = "SELECT COUNT(*) AS sent FROM sms_sent WHERE sms_account = 318 AND timestamp > DATE_SUB(NOW(), INTERVAL {$tags["alert-interval"]} MINUTE) AND (contents LIKE '%Alert:%' OR contents LIKE '%Notification:%')";
$rs = api_db_query_read($sql);
if (!$rs || empty($rs->Fields("sent"))) {
	// Unable to confirm outbound messages
	$message .= "Unable to confirm outbound messages in last {$tags["alert-interval"]} minutes.\n";
	print "Unable to confirm outbound messages!\n";
} else {
	print "Found {$rs->Fields("sent")} outbound messages.\n";
}

if ($message) {
	$email = [];
	$email["to"]          = @$tags["alert-destination"];
	if (empty($email["to"])) {
		$email["to"]  = $default_recipients;
	} else {
		$email["cc"]  = $default_recipients;
	}
	$email["subject"]     = "[ReachTEL] Westpac Fraud SMS alert";
	$email["textcontent"] = <<<EOT
Hello,

{$message}

Please investigate immediately!

EOT;
	$email["htmlcontent"] = nl2br($email["textcontent"]);
	$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

	api_email_template($email);
	print "Sent notification email.\n";
}
