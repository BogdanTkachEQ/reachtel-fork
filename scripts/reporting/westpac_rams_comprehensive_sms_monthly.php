#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);

$start = date('Y-m-d 00:00:00', strtotime('first day of previous month'));
$end = date("Y-m-d 23:59:59", strtotime("last day of previous month"));
$westpac_groupid = 138;
$westpac_rams_groupid = 784;
$westpac_userids = api_keystore_getidswithvalue("USERS", "groupowner", $westpac_groupid);
$westpac_rams_userids = api_keystore_getidswithvalue("USERS", "groupowner", $westpac_rams_groupid);
$messages = [];
$userids = array_merge(
    $westpac_userids ?: [],
    $westpac_rams_userids ?: []
);
foreach($userids as $userid) {
    $userdata[$userid] = api_users_setting_getall($userid);
}

// Get the received messages
$westpac_rams_sms_account = 701;
$westpac_rams_sms_account_name = api_data_format(api_sms_dids_setting_getsingle($westpac_rams_sms_account, "name"), "sms");

print "Fetching data from sms_received\n";

$sql = "SELECT UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `sms_account`, `from`, `contents` FROM `sms_received` 
WHERE `timestamp` >= ? AND `timestamp` <= ? AND `sms_account` = ?
ORDER BY `timestamp` ASC";
$rs = api_db_query_read($sql, array($start, $end, $westpac_rams_sms_account));

while(!$rs->EOF){

    $messages[$rs->Fields("timestamp")][] = [
        "direction" => "received",
        "username" => null,
        "from" => api_data_format($rs->Fields("from"), "sms"),
        "destination" => $westpac_rams_sms_account_name,
        "message" => $rs->Fields("contents"),
        "status" => "received"
    ];

    $rs->MoveNext();
}

print "Completed\n";

print "Fetching data from sms_out\n";
// Get the API messages
$imploded_user_id = implode(',', array_fill(0, count($userdata), '?'));
$sql = "SELECT `id`, UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `userid`, `from`, `destination`, `message` 
FROM `sms_out` WHERE `timestamp` >= ? AND `timestamp` <= ? 
AND `userid` IN (" . $imploded_user_id . ") 
ORDER BY `timestamp` ASC";

$userid_sql_variables = array_merge([$start, $end], array_keys($userdata));

$rs = api_db_query_read($sql, $userid_sql_variables);

$sms_out_data = [];
while (!$rs->EOF) {
    $sms_out_data[$rs->Fields("id")] = [
        'timestamp' => $rs->Fields("timestamp"),
        'userid' => $rs->Fields("userid"),
        'from' => $rs->Fields("from"),
        'destination' => $rs->Fields("destination"),
        'message' => $rs->Fields("message")
    ];

    $rs->MoveNext();
}

if ($sms_out_data) {
    $sms_out_statuses = get_sms_out_statuses(array_keys($sms_out_data));

    foreach ($sms_out_data as $id => $data) {
        $messages[$data['timestamp']][] = [
            'username' => $userdata[$data['userid']]['username'],
            'from' => $data['from'],
            'destination' => $data['destination'],
            'message' => $data['message'],
            'status' => isset($sms_out_statuses[$id]) ? $sms_out_statuses[$id] : 'SENT'
        ];
    }
}
print "Completed\n";

print "Fetching data from sms_sent\n";

// TODO: Indexing timestamp in sms_sent would improve the performance of this query. Might need to look at this
// if there is a performance overhead.
$sql = "SELECT s.`eventid` as eventid, UNIX_TIMESTAMP(s.`timestamp`) as `timestamp`, s.`to` as `to`,
s.`contents` as `contents`, sa.`userid` as userid
FROM sms_sent s LEFT JOIN sms_api_mapping sa ON (sa.`rid` = s.`eventid`) 
WHERE s.`timestamp` >= ? AND s.`timestamp` <= ? AND s.`sms_account`=?";
$rs = api_db_query_read($sql, [$start, $end, $westpac_rams_sms_account]);

$sms_data = [];

while(!$rs->EOF) {
    $sms_data[$rs->Fields("eventid")] = [
        'timestamp' => $rs->Fields("timestamp"),
        'userid' => $rs->Fields("userid"),
        'to' => $rs->Fields("to"),
        'contents' => $rs->Fields("contents")
    ];

    $rs->MoveNext();
}

if ($sms_data) {
    $sms_statuses = get_sms_statuses(array_keys($sms_data));

    foreach ($sms_data as $id => $data) {
        $messages[$data['timestamp']][] = [
            "direction" => "sent",
            "username" => ($data['userid'] && isset($userdata[$data['userid']])) ?
                $userdata[$data['userid']]["username"] : '',
            "from" => $westpac_rams_sms_account_name,
            "destination" => api_data_format($data["to"], "sms"),
            "message" => $data['contents'],
            "status" => isset($sms_statuses[$id]) ? $sms_statuses[$id] : 'sent'
        ];
    }
}
print "Completed\n";

print "Transforming data\n";
ksort($messages);

$headers = ['timestamp', 'username', 'from', 'destination', 'status','message'];

$contents = [];

foreach($messages as $timestamp => $messagesAtTimestamp) {
    foreach($messagesAtTimestamp as $message) {
        $contents[] = [
            date("Y-m-d H:i:s", $timestamp),
            $message["username"],
            $message["from"],
            $message["destination"],
            $message["status"],
            $message["message"]
        ];
    }
}

array_unshift($contents, $headers);
$content = api_csv_string($contents);

$email["to"]          = api_cron_tags_get(109, "reporting-destination");
$email["subject"]     = "[ReachTEL] SMS traffic report - " . $start . " to " . $end;
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL SMS traffic report for the period between " . $start . " and " . $end . ".\n\n";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL SMS traffic report for period between " . $start . " and " . $end . ".<br /><br />";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $content, "filename" => "ReachTEL-SMS-Traffic-" . date("Ymd-His") . ".csv");

if (!api_email_template($email)) {
    print "Failed emailing report....";
    print exit;
}
print "Report emailed";

function get_sms_out_statuses(array $sms_out_ids) {
    $imploded_sms_out_id = implode(',', array_fill(0, count($sms_out_ids), '?'));

    $sql = "SELECT s1.id as id, s1.status as status FROM sms_out_status s1 JOIN 
    (SELECT id, max(`timestamp`) as `timestamp` FROM sms_out_status WHERE id IN (" . $imploded_sms_out_id . ") 
    GROUP BY id) s2 
    ON (s1.id=s2.id AND s1.timestamp = s2.timestamp) WHERE s1.id IN (" . $imploded_sms_out_id . ") 
    ORDER BY s1.timestamp DESC";

    $rs = api_db_query_read($sql, array_merge(array_keys($sms_out_ids), array_keys($sms_out_ids)));

    return ($rs->RecordCount() > 0) ? $rs->GetAssoc() : [];
}

function get_sms_statuses(array $sms_ids) {
    $imploded_sms_id = implode(',', array_fill(0, count($sms_ids), '?'));

    $sql = "SELECT s1.eventid as id, LOWER(s1.status) as status FROM sms_status s1 JOIN 
    (SELECT eventid, max(`timestamp`) as `timestamp` FROM sms_status WHERE eventid IN (" . $imploded_sms_id . ") 
    GROUP BY eventid) s2 
    ON (s1.eventid=s2.eventid AND s1.timestamp = s2.timestamp) WHERE s1.eventid IN (" . $imploded_sms_id . ") 
    ORDER BY s1.timestamp DESC";

    $rs = api_db_query_read($sql, array_merge(array_keys($sms_ids), array_keys($sms_ids)));

    return ($rs->RecordCount() > 0) ? $rs->GetAssoc() : [];
}
