#!/usr/bin/php
<?php

require_once(__DIR__ . "/../../../Morpheus/api.php");

$cronid = getenv('CRON_ID');
if (!$cronid) {
	die("ERROR: Invalid env var CRON_ID\n");
}

$tags = api_cron_tags_get($cronid);

$days = isset($tags['retention-days']) ? $tags['retention-days'] : '365';
echo "Retention period set to {$days} days'\n";
$datetime = @strtotime("{$days} days ago");

if (!$datetime) {
	die("ERROR: Invalid retention period\n");
}

if ($days < BAD_DATA_BACKCHECK_DAYS_PHONE) {
	$const = BAD_DATA_BACKCHECK_DAYS_PHONE;
	die("ERROR: 'retention-days' is lower than BAD_DATA_BACKCHECK_DAYS_PHONE = {$const} days\n");
}
if ($days < BAD_DATA_BACKCHECK_DAYS_EMAIL) {
	$const = BAD_DATA_BACKCHECK_DAYS_EMAIL;
	die("ERROR: 'retention-days' is lower than BAD_DATA_BACKCHECK_DAYS_EMAIL = {$const} days\n");
}

$date = date('Y-m-d', $datetime);
echo "Deleting all bad data where timestamp < {$date} ... ";

$sql = "DELETE FROM `bad_data` WHERE `timestamp` < ?;";
if (!api_db_query_write($sql, [$date])) {
	die("ERROR: Query failed\n");
}

echo "done!\n";
