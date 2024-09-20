<?php
require_once(__DIR__ . '/../../api.php');

$cron_id = 128;
$tags = api_cron_tags_get($cron_id);

if (!$tags) {
	$tags = [];
}

// Do the thing!
$year = date('Y');


$public_holidays = _get_holidays_for_year($year, $tags);
$public_holiday_tags = _get_holiday_tags($public_holidays, $tags);

$success = api_system_tags_set($public_holiday_tags);

_send_email($success, null, $tags);

/**
 * Get stream context with proxy
 *
 * @return resource
 */
function _get_stream_context()
{
	$context_array = [];
	if (defined('PROXY_EXTERNAL')) {
		$context_array = [
			'http' => [
				'proxy' => str_replace('http', 'tcp', PROXY_EXTERNAL),
			],
			'ssl' => [
				'verify_peer' => false
			]
		];
	}

	return stream_context_create($context_array);
}

/**
 * Get Open Data URL for a given year
 *
 * @param integer $year
 *
 * @return string
 */
function _get_url_for_year($year, $tags = [])
{
	$url = isset($tags['file-list-url']) ? $tags['file-list-url'] : 'https://data.gov.au/api/3/action/package_show?id=b1bc6077-dadd-4f61-9f8c-002ab2cdff10';

	$year = (string)$year;
	$json = @file_get_contents($url, false, _get_stream_context());
	if ($json === false) {
		_send_email(false, sprintf("Failed retrieving public holidays data list.\n\nError parsing %s", $url), $tags);
		exit();
	}
	$json = json_decode($json, true);

	// since we're grepping by name, there might be multiple (ie 2016 - 2017, 2017 - 2018)
	$possible_resources = [];
	foreach ($json['result']['resources'] as $resource) {
		if (strpos($resource['name'], $year) !== false) {
			$possible_resources[] = $resource;
		}
	}

	// sort by last modified to get most recent option
	usort(
		$possible_resources,
		function($a, $b) {
			return strtotime($a['last_modified']) > strtotime($b['last_modified']) ? -1 : 1;
		}
	);

	return $possible_resources[0]['url'];
}

/**
 * Parse open data for public holidays for a given year
 *
 * @param integer $year
 * @return array
 */
function _get_holidays_for_year($year, $tags = [])
{
	$url = _get_url_for_year($year, $tags);
	$h = fopen($url, 'r', false, _get_stream_context());

	// Real public holiday list has only observed, doesn't include actual day if it's a weekend
	// let's respect the day anyway
	$safety_white_list = _get_safety_white_list($year, $tags);
	$easter_list = [];
	$headers = fgetcsv($h);

	$index_state = -1;
	$index_date = -1;
	$index_name = -1;
	foreach ($headers as $index => $header) {
		switch ($header) {
			case 'Applicable To':
			case 'Jurisdiction':
				$index_state = $index;
				break;
			case 'Date':
				$index_date = $index;
				break;
			case 'Holiday Name':
				$index_name = $index;
				break;
		}
	}

	$public_holidays = [];
	while ($line = fgetcsv($h)) {
		$date = false;
		$state = false;
		if ($index_date !== -1 && $index_state !== -1) {
			$date = strtotime($line[$index_date]);
			if (date('Y', $date) !== (string)$year) {
				continue;
			}
			$state = trim(strtoupper($line[$index_state]));

			// record Easter dates from NSW for national fallback
			if ($state === 'NSW' && $index_name !== -1) {
				if ($line[$index_name] === 'Good Friday' || strpos(strtolower($line[$index_name]), 'easter') !== false) {
					$easter_list[_get_date_key_format($year, $date)] = date('jS F', $date);
				}
			}
		}

		if (!$date || !$state) {
			$content = <<<EOF
Hi,

The public holiday system tag updated failed because the state and/or date columns couldn't be found in the source CSV.

Check here: $url
EOF;
			_send_email(false, $content, $tags);
			exit();
		}

		// for national holidays, add to all separate months, and the AU national tag
		if ($state === 'NAT') {
			$state = 'ACT|NSW|NT|QLD|TAS|VIC|WA|SA|AU';
		}

		$state_array = explode('|', $state);

		foreach ($state_array as $state) {
			$public_holidays[$state][_get_date_key_format($year, $date)] = date('jS F', $date);
		}
	}

	fclose($h);

	// if national doesn't exist for some reason (2019 seems to be missing - I've emailed data.gov.au)
	// use the whitelist and NSW Easter which basically makes the national holidays
	if (!array_key_exists('AU', $public_holidays)) {
		$public_holidays['AU'] = $safety_white_list + $easter_list;
	}

	foreach ($public_holidays as $state => $holidays) {
		$public_holidays[$state] = array_merge($holidays, $safety_white_list);
		ksort($public_holidays[$state]);
	}

	return $public_holidays;
}

/**
 * Format public holiday array as expected tag array format
 *
 * @param array $public_holidays
 * @return array
 */
function _get_holiday_tags(array $public_holidays)
{
	$public_holiday_tags = [];
	foreach ($public_holidays as $state => $holidays) {
		$public_holiday_tags[$state . '-public-holidays'] = implode(', ', $holidays);
	}

	return $public_holiday_tags;
}

/**
 * Send a success or failure email
 *
 * @param boolean $success
 * @param string  $message
 *
 * @return boolean
 */
function _send_email($success = true, $message = null, array $tags = [])
{
	$email = [];
	if ($success) {
		$email['content'] = 'Public holiday system tags updated successfully!';
		$subject_suffix = 'SUCCESS';
	} else {
		$email['content'] = 'Public holiday system tags update FAILED!';
		$subject_suffix = 'FAILED';
	}

	if ($message) {
		$email['content'] .= "\n\n$message";
	}

	$date = date('Y-m-d H:i:s');
	$to = isset($tags['email-destination']) ? $tags['email-destination'] : 'AUReachTELITSupport@equifax.com';
	$email['to'] = $to;
	$email['subject'] = "[ReachTEL] Public Holiday System Tag Update $subject_suffix - $date";

	echo $email['subject'] . "\n";
	echo $email['content'] . "\n";

	api_email_template($email);

	return true;
}

/**
 * Get the safety white list array in the required format, either from tags or hard coded
 *
 * @param integer $year
 * @param array   $tags
 *
 * @return array
 */
function _get_safety_white_list($year, array $tags = []) {
	$safety_white_list = [];
	if (isset($tags['safety-white-list'])) {
		$white_list_array = array_map('trim', explode(',', $tags['safety-white-list']));
	} else {
		$white_list_array = [
			'1st January',
			'26th January',
			'25th April',
			'25th December',
			'26th December',
		];
	}

	foreach ($white_list_array as $white_listed_date) {
		$white_listed_date = strtotime($white_listed_date);
		if ($white_listed_date !== false) {
			$safety_white_list[_get_date_key_format($year, $white_listed_date)] = date('jS F', $white_listed_date);
		}
	}

	return $safety_white_list;
}


/**
 * Return a date in the required array key format
 *
 * @param integer $year
 * @param integer $date Date as unix timestamp.
 *
 * @return string
 */
function _get_date_key_format($year, $date) {
	return sprintf('d-%d%s', $year, date('md', $date));
}
