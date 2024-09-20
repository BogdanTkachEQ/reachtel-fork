#!/usr/bin/php
<?php

require_once(__DIR__ . "/../../api.php");

// cron 143
$cronid = getenv('CRON_ID');
$tags = api_cron_tags_get($cronid);

// optionally use secondary db
if (isset($tags['database']) && 'slave' === $tags['database']) {
	print "Using '{$tags['database']}' database.\n";
	api_db_switch_connection(null, null, null, DB_MYSQL_READ_HOST_FORCED);
}

// optionally run on archive tables
$archive_suffix = null;
if (isset($tags['archive']) && $tags['archive']) {
	// archive table suffix
	$archive_suffix = '_archive';
}

if (!isset($tags['user-group-id']) || !$tags['user-group-id']) {
	die("Tag 'user-group-id' is not set\n");
}

$user_group_id = $tags['user-group-id'];
$group_name = api_groups_setting_getsingle($user_group_id, 'name');
if (!$group_name) {
	die("ERROR: Group id {$user_group_id} not found\n");
}
print "Group {$group_name} (id#{$user_group_id}).\n";

$from_date = strtotime(isset($tags['from-date']) ? $tags['from-date'] : '1 day ago');
$to_date = strtotime(isset($tags['to-date']) ? $tags['to-date'] : 'now');

print "Generating report from {$from_date} to {$to_date} ...";

// Get campaigns between dates
$sql = <<<EOF
SELECT
	id
FROM
	key_store
WHERE
	type = 'CAMPAIGNS'
	AND item = 'created'
	AND value BETWEEN ?
	AND ?
	AND id IN (
		SELECT
			id
		FROM
			key_store
		WHERE
			type = 'CAMPAIGNS'
			AND item = 'type'
			AND value = 'phone'
			AND id in (
				SELECT
					id
				FROM
					key_store
				WHERE
					type = 'CAMPAIGNS'
					AND item = 'groupowner'
					AND value = '?'
			)
	);
EOF;

$rs = api_db_query_read(
	$sql,
	[
		$from_date,
		$to_date,
		$user_group_id,
	]
);

$rows = $rs->getArray();

$ids = array_map(
	function($row) {
		return $row['id'];
	},
	$rows
);

$minutes_array = [];
foreach ($ids as $id) {
	$id_template = implode(',', array_fill(0, count($ids), '?'));
	$id_template = implode(',', array_fill(0, 1, '?'));

	// Get minutes
	$sql = <<<EOF
SELECT
	answer.campaignid,
	IF(
		SUBSTR(answer.destination, 1, 2) = '04',
		'mobile',
		'landline'
	) as call_type,
	SUM(
		TIMESTAMPDIFF(SECOND, answer.answer, hangup.hangup)
	) / 60 as minutes
FROM
	(
		SELECT
			cr.campaignid,
			eventid,
			cr.targetid,
			destination,
			timestamp as answer
		FROM
			call_results{$archive_suffix} cr
			INNER JOIN targets{$archive_suffix} t ON cr.targetid = t.targetid
		WHERE
			cr.campaignid in ({$id_template})
			AND value = 'ANSWER'
	) answer
	JOIN (
		SELECT
			cr.campaignid,
			eventid,
			cr.targetid,
			destination,
			timestamp as hangup
		FROM
			call_results{$archive_suffix} cr
			INNER JOIN targets{$archive_suffix} t ON cr.targetid = t.targetid
		WHERE
			cr.campaignid in ({$id_template})
			AND value = 'HANGUP'
	) hangup ON answer.eventid = hangup.eventid
GROUP BY
	campaignid,
	IF(
		SUBSTR(answer.destination, 1, 2) = '04',
		'mobile',
		'landline'
	);
EOF;

	$params = [];
	// Add two lots of ids for both queries
	$params = array_merge($params, [$id]);
	$params = array_merge($params, [$id]);
	$rs = api_db_query_read($sql, $params);


	$call_statistics = $rs->getArray();

	foreach ($call_statistics as $campaign_statistics) {
		$campaign_id = $campaign_statistics['campaignid'];
		$call_type = $campaign_statistics['call_type'];
		$minutes = $campaign_statistics['minutes'];
		if (!isset($minutes_array[$campaign_id])) {
			$minutes_array[$campaign_id] = [];
		}
		$minutes_array[$campaign_id][$call_type] = $minutes;
	}

}

$header = [
	'id',
	'Campaign Name',
	'billingmonth',
	'donotcontactdestination',
	'voicedid',
	'Total',
	'Abandoned',
	'Complete',
	'Calls',
	'Answered',
	'Busy',
	'Ringouts',
	'Disconnected',
	'Call issue',
	'Mobile Minutes',
	'Landline Minutes',
];

$report = [];
foreach ($ids as $campaign_id) {
	$fields = api_campaigns_setting_get_multi_byitem($campaign_id, ['name', 'billingmonth', 'donotcontactdestination', 'voicedid']);

	$row = [];
	$row['id'] = $campaign_id;
	$row['Campaign Name'] = $fields['name'];
	$row['billingmonth'] = isset($fields['billingmonth']) ? $fields['billingmonth'] : '?';
	$row['donotcontactdestination'] = isset($fields['donotcontactdestination']) ? $fields['donotcontactdestination'] : '?';
	$row['voicedid'] = isset($fields['voicedid']) ? $fields['voicedid'] : '?';

	$results = api_data_target_status_phone_json($campaign_id, false, (bool) $archive_suffix);
	$results = $results['status'];

	$row['Total'] = $results['targets'];
	$row['Abandoned'] = $results['abandoned'];
	$row['Complete'] = $results['complete'];
	$row['Calls'] = $results['calls'];
	$row['Answered'] = $results['answered'];
	$row['Busy'] = $results['busy'];
	$row['Ringouts'] = $results['ringout'];
	$row['Disconnected'] = $results['disconnected'];
	$row['Call issue'] = $results['chanunavail'];

	// minutes
	$row['Mobile Minutes'] = isset($minutes_array[$campaign_id]['mobile']) ? $minutes_array[$campaign_id]['mobile'] : 0;
	$row['Landline Minutes'] = isset($minutes_array[$campaign_id]['landline']) ? $minutes_array[$campaign_id]['landline'] : 0;

	$report[] = array_values($row);
}

print "done!\n";

array_unshift($report, $header);

api_error_printiferror();

$email["to"]	        = isset($tags['reporting-destination']) ? $tags['reporting-destination'] : 'support@ReachTEL.com.au';
$email["subject"]       = "[ReachTEL] Call Results Report - " . date("H:i:s d/m/Y", strtotime($from_date)) . " to " . date("H:i:s d/m/Y", strtotime($to_date));
$email["content"]       = "Hello,\n\nPlease find attached the ReachTEL call results report for {$group_name}.\n\n";
$email["from"]          = "ReachTEL Support <support@ReachTEL.com.au>";
$email["attachments"][] = ["content" => api_csv_string($report), "filename" => "Call-Results-Report-group-{$user_group_id}.csv"];

if(api_email_template($email)){
	print "Report sent\n";
}
