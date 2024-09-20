#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$cronId = 92;

$tags = api_cron_tags_get($cronId);
foreach(['month', 'group-id', 'email-report'] as $key) {
    if (!isset($tags[$key])) {
        echo "[ERROR] Required cron tag '{$key}' is missing\n";
        exit;
    }
}

// start date report
$start = new DateTime("first day of {$tags['month']}");
$start->setTime(0, 0, 0);
// end date report
$end = new DateTime("last day of {$tags['month']}");
$end->setTime(23, 59, 59);
echo "Creating report for month of " . $start->format('F Y') . "\n";

echo "Fetching users with API REST SMS GET & POST permissions...";
$userIds = [];
foreach(api_keystore_getidswithvalue("USERS", "groupowner", $tags['group-id']) as $userId) {
    if(api_security_check(119 /* API - REST - SMS - Get */, null, true, $userId)
       || api_security_check(120 /* API - REST - SMS - Post */ , null, true, $userId)) {
           $userIds[] = (int) $userId;
    }
}

if (!$userIds) {
    echo "[ERROR] No users found.\n";
    exit;
}
echo "[OK]\n";

$data = [];

echo "Fetching data in `sms_out` table ...";
$sql = "SELECT `sms_out`.*, GROUP_CONCAT(`sms_out_status`.status ORDER BY `sms_out_status`.timestamp DESC) AS statuses
        FROM `sms_out`
        LEFT JOIN `sms_out_status` ON `sms_out`.id = `sms_out_status`.id
        WHERE `sms_out`.`timestamp` BETWEEN FROM_UNIXTIME(?) AND FROM_UNIXTIME(?)
          AND `sms_out`.userid IN (" . implode(',', $userIds) . ")
        GROUP BY `sms_out`.id
        ORDER BY `sms_out`.timestamp ASC;";
$rs = api_db_query_read($sql, [
    $start->getTimestamp(),
    $end->getTimestamp()
]);

foreach($rs->GetArray() as $sms) {
    $status = empty($sms['statuses']) ? null : explode(',', $sms['statuses'])[0];
    $messageunits = 1;
    $len = strlen($sms['message']);
    if($len > 160) {
        $messageunits = (fmod($len/153, 1) != 0) ? ceil($len/153) : ($len/153);
    }

    $data[] = [
        $sms['timestamp'],
        $sms['destination'],
        api_misc_crypt_safe($sms['id']),
        $status,
        $messageunits,
        $sms['message'],
    ];
}
echo "[OK]\n";

echo "Fetching data in `sms_sent` table ...";
$sql = "SELECT `sms_sent`.*,
               GROUP_CONCAT(`sms_status`.status ORDER BY `sms_status`.timestamp DESC) AS statuses,
               GROUP_CONCAT(`sms_api_mapping`.messageunits ORDER BY `sms_status`.timestamp DESC) AS messageunits
        FROM `sms_sent`
        LEFT JOIN `sms_status` ON `sms_sent`.eventid = `sms_status`.eventid
        LEFT JOIN `sms_api_mapping` ON `sms_sent`.eventid = `sms_api_mapping`.rid
        WHERE `sms_sent`.`timestamp` BETWEEN FROM_UNIXTIME(?) AND FROM_UNIXTIME(?)
          AND `sms_sent`.sms_account IN (" . implode(',', $userIds) . ")
        GROUP BY `sms_sent`.eventid
        ORDER BY `sms_sent`.timestamp ASC;";
$rs = api_db_query_read($sql, [
    $start->getTimestamp(),
    $end->getTimestamp()
]);

foreach($rs->GetArray() as $sms) {
    $status = empty($sms['statuses']) ? null : explode(',', $sms['statuses'])[0];
    $messageunits = empty($sms['messageunits']) ? null : explode(',', $sms['messageunits'])[0];

    $data[] = [
        $sms['timestamp'],
        $sms['to'],
        api_misc_crypt_safe($sms['eventid']),
        $status,
        $messageunits,
        $sms['contents'],
    ];
}
echo "[OK]\n";

echo "Sorting data by date ...";
usort($data, function($a, $b) { return $a[0] > $b[0]; });
echo "[OK]\n";

echo "Generating CSV... ";
$temp = tempnam('/tmp', 'cfmeu-');
$fp = fopen($temp, 'w');
fputcsv($fp, ['timestamp', 'destination', 'rid', 'status', 'billable units', 'message']);
foreach ($data as $row) {
    fputcsv($fp, $row);
}
fclose($fp);
$data = file_get_contents($temp);
@unlink($temp);
echo "[OK]\n";

echo "Sending email... ";
$email["to"]          = $tags['email-report'];
$email["subject"]     = "[ReachTEL] CFMEU report - " . $start->format('F Y');
$email["textcontent"] = "Hello,\n\nPlease find attached the latest CFMEU report.\n\n";
$email["htmlcontent"] = nl2br($email["textcontent"]);
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
$email["attachments"][] = [
    "content" => $data,
    "filename" => "ReachTEL-CFMEU-" . $start->format('F-Y') . "-Report.csv"
];

if (!api_email_template($email)) {
    echo "[FAILED]\n";
    exit;
}

echo "[OK]\n";
