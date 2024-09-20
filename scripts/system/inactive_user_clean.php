<?php

require_once('Morpheus/api.php');

use Services\User\UserTypeEnum;

// Default intervals
const DEFAULT_FILTER_LOGGED_IN_INTERVAL = 'P3M';
const DEFAULT_FILTER_CREATED_INTERVAL = 'P1M';

// report date format
const DATE_FORMAT = 'Y-m-d H:i:s';

// Check for tags to override intervals
$tags = api_cron_tags_get(108);
if ($tags) {
	if (check_valid_interval_tag($tags, 'logged-in-interval')) {
		define('FILTER_LOGGED_IN_INTERVAL', $tags['logged-in-interval']);
	}

	if (check_valid_interval_tag($tags, 'created-interval')) {
		define('FILTER_CREATED_INTERVAL', $tags['created-interval']);
	}
}

if (!defined('FILTER_LOGGED_IN_INTERVAL')) {
	define('FILTER_LOGGED_IN_INTERVAL', DEFAULT_FILTER_LOGGED_IN_INTERVAL); // Logged in in last 3 months
}

if (!defined('FILTER_CREATED_INTERVAL')) {
	define('FILTER_CREATED_INTERVAL', DEFAULT_FILTER_CREATED_INTERVAL); // Created in last month
}

$field_function_map = [
	'username' => function($value) { return transformNull($value); },
	'created' => function($value) { return transformDate($value); },
	'lastauth' => function($value) { return transformDate($value); },
	'status' => function($value) { return transformNull($value); },
    'usertype' => function($value) { return transformNull($value); },
];

$sql = 'SELECT id, item, value FROM `key_store` WHERE type=? AND item IN (' .
	implode(',', array_fill(0, count($field_function_map), '?')) .
	') ORDER BY id ASC';

$rs = api_db_query_read($sql, array_merge(['USERS'], array_keys($field_function_map)));

if (!$rs || $rs->RecordCount() == 0) {
	print 'Something went wrong';
	exit;
}

$users = [];

while (!$rs->EOF) {
	$id = $rs->Fields('id');
	$item = $rs->Fields('item');
	$value = $rs->Fields('value');

	if (!isset($users[$id])) {
		$users[$id] = [
			'id' => $id,
		];
	}

	$field_function = $field_function_map[$item];

	$users[$id][$item] = $field_function($value);

	$rs->MoveNext();
}

// filter out anyone who has logged in recently, or is less than a month old
$now = new \DateTimeImmutable();
$created_interval_date = $now->sub(new \DateInterval(FILTER_CREATED_INTERVAL));
$logged_in_interval_date = $now->sub(new \DateInterval(FILTER_LOGGED_IN_INTERVAL));

$inactive_users = array_filter(
	$users,
	function($user) use ($created_interval_date, $logged_in_interval_date) {
	    // only look at client users

        if (isset($user['usertype']) && UserTypeEnum::hasValue($user['usertype'])) {
            $userType = UserTypeEnum::get($user['usertype']);
            if ($userType &&
                !$userType->is(UserTypeEnum::CLIENT()) &&
                !$userType->is(UserTypeEnum::API())
            ) {
                return false;
            }
        }

		// only cleanse active users
		if ($user['status'] !== USER_STATUS_ACTIVE) {
			return false;
		}

		// only cleanse if user doesn't have a created date or they're older than a month
		if (isset($user['created']) && $user['created'] > $created_interval_date) {
			return false;
		}

		// user has not authenticated in last 3 months, or they haven't authenticated at all
		return !isset($user['lastauth'])
			|| $user['lastauth'] < $logged_in_interval_date;
	}
);

// Set inactive users to disabled by inactivity (-3)
$deactivation_list = [];
$success_count = 0;
foreach ($inactive_users as $user) {
	$success = api_users_setting_set($user['id'], 'status', USER_STATUS_INACTIVE);

	// make a list for reporting
	$deactivation_list[] = [
		'id' => $user['id'],
		'username' => $user['username'],
		'created' => isset($user['created']) ? $user['created']->format(DATE_FORMAT) : '',
		'lastauth' => isset($user['lastauth']) ? $user['lastauth']->format(DATE_FORMAT) : '',
		'success' => $success ? 'Y' : 'N',
	];

	if ($success) {
		$success_count++;
	}
}

$deactivation_count = count($deactivation_list);
$deactivation_count_text = ($deactivation_count === $success_count) ? $deactivation_count : "$success_count of $deactivation_count";
print "Deactivated: $deactivation_count_text\n";

// Send a report if there are deactivations
if ($deactivation_count > 0) {
	// add header row
	$header = array_keys($deactivation_list[0]);
	array_unshift($deactivation_list, $header);

	// make csv content
	$csv_content = api_csv_string($deactivation_list);

	$logged_in_config_text = $logged_in_interval_date->format(DATE_FORMAT) . ' (' . FILTER_LOGGED_IN_INTERVAL . ')';
	$created_config_text = $created_interval_date->format(DATE_FORMAT) . ' (' . FILTER_CREATED_INTERVAL . ')';


	$text_content = <<<EOF
Hello,

The inactive user cleanup cron deactivated $deactivation_count_text user(s) with the following configuration.

Created before: $created_config_text
Logged in since: $logged_in_config_text

See attached report for details.
EOF;

	$email = [
		'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
		'to' => 'ReachTEL Support <support@ReachTEL.com.au>',
		'subject' => '[ReachTEL] Inactive User Clean Deactivations',
		'content' => $text_content,
		'attachments' => [
			[
				'content' => $csv_content,
				'filename' => 'ReachTEL-Inactive-User-Deactivations-' . date('Ymd') . '.csv',
			],
		],
	];

	api_email_template($email);
}

/**
 * @param string $value
 *
 * @return \DateTime
 */
function transformDate($value) {
	if (is_null($value)) {
		return '';
	}

	$date = new \DateTime();
	$date->setTimestamp($value);
	return $date;
}

/**
 * @param string $value
 * @return string
 */
function transformNull($value) {
	return $value === null ? '' : $value;
}

/**
 * Check if the tag exists and is a valid DateInterval format
 *
 * @param array $tags
 * @param string $tag_name
 *
 * @return boolean
 */
function check_valid_interval_tag(array $tags, $tag_name) {
	if (isset($tags[$tag_name])) {
		try {
			$interval = new \DateInterval($tags[$tag_name]);
		} catch (\Exception $e) {
			echo "ERROR: Tag {$tag_name}:'{$tags[$tag_name]}' could not be parsed. It should be something like P3M.\n";
			exit;
		}
		return true;
	}
	return false;
}
