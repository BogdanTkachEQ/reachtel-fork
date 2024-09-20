<?php

require_once(__DIR__ . '/../../api.php');

$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);

if (!isset($tags['phone-number'])) {
	die("Tag 'phone-number' not found.\n");
}

$phone = api_data_numberformat(
	$tags['phone-number'],
	$region = strtoupper(isset($tags['region']) ? $tags['region'] : DEFAULT_REGION)
);

if (!$phone) {
	die("Invalid phone number {$tags['phone-number']} (region {$region})\n");
}

echo "Phone formatted to {$phone['destination']} ({$phone['numbertype']} {$phone['countryname']})\n\n";
$phone = $phone['destination'];

$csv = [['source', 'timestamp', 'contents', 'status', 'status date']];

echo "Fetching sms_sent data ...";
$sql = <<<EOF
SELECT s.timestamp, s.contents, st.status, st.supplierdate
FROM sms_sent s
LEFT JOIN sms_status st on (s.eventid = st.eventid)
WHERE s.to = ?;
EOF;
$rs = api_db_query_read($sql, [$phone]);
if (!$rs) {
	die(sprintf(
		"ERROR: Query failed:\n%s\n",
		api_error_printiferror(['return' => true])
	));
}

while (!$rs->EOF) {
	$row = $rs->fields;
	$csv[] = [
		'Morpheus',
		$row['timestamp'], // timestamp
		$row['contents'], // contents
		$row['status'], // status
		$row['supplierdate'], // status date
	];
	$rs->MoveNext();
}
echo "ok\n";

echo "Fetching sms_out data ...";
$sql = <<<EOF
SELECT s.timestamp, s.message, st.status, st.timestamp AS status_timestamp
FROM sms_out s
LEFT JOIN sms_out_status st on (s.id = st.id)
WHERE s.from = ?;
EOF;
$rs = api_db_query_read($sql, [$phone]);
if (!$rs) {
	die(sprintf(
		"ERROR: Query failed:\n%s\n",
		api_error_printiferror(['return' => true])
	));
}

while (!$rs->EOF) {
	$row = $rs->fields;
	$csv[] = [
		'REST API',
		$row['timestamp'], // timestamp
		$row['message'], // contents
		$row['status'], // status
		$row['status_timestamp'], // status date
	];
	$rs->MoveNext();
}
echo "ok\n\n";

$content = [
	"content" => api_csv_string($csv),
	"filename" => "phone-report-{$phone}-" . date('YmdHis'). ".csv"
];

echo "Generating report:\n";

if (!empty($tags["pgpkeys"])) {
	echo " * PGP encrypting report... ";
	$content = api_misc_pgp_encrypt(
		$content,
		$tags["pgpkeys"]);

	if (empty($content)) {
		echo "ERROR: Failed to PGP encrypt report\n";
		exit;
	}

	echo "ok\n";
}
echo " * File path: {$content['filename']}\n\n";

$destination = isset($tags["reporting-destination"]) ? $tags["reporting-destination"] : 'support@ReachTEL.com.au';
echo "Sending email to {$destination}...";
$email = [];
$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
$email["to"] = $destination;
$email["subject"] = "[ReachTEL] Phone number report for {$phone} - " . date('Y-m-d');
$email["content"] = "Hey!\nPlease find attached to this email the phone number report for {$phone}.";
$email["attachments"][] = $content;
if (api_email_template($email)) {
	echo "sent!\n";
} else {
	echo "FAILED !!!\n";
}
