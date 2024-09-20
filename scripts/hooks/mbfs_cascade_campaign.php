<?php
/**
 * MBFS cascading post complete hook
 *
 * @author      christopher.colborne@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

/**
 * Post complete hook to cascade and add targets for guarantor campaigns
 *
 * @param integer $campaign_id
 *
 * @return void
 */
function api_campaigns_hooks_mbfs_cascade_campaign($campaign_id) {
	$log_array = [
		'request_id' => uniqid(),
		'campaign_id' => $campaign_id,
	];
	$start_time = $checkpoint_time = _mbfs_cascade_campaign_timer_start();
	$checkpoint_time = _mbfs_cascade_campaign_log('Start post hook', $log_array, $start_time, $checkpoint_time);

	$current_campaign_name = api_campaigns_setting_getsingle($campaign_id, 'name');
	$current_campaign_number = _mbfs_cascade_campaign_get_campaign_number($current_campaign_name);

	if ($current_campaign_number === 3) {
		return;
	}

	$next_campaign_name = _mbfs_cascade_campaign_get_next_campaign_name($current_campaign_name);
	$next_campaign_number = _mbfs_cascade_campaign_get_campaign_number($next_campaign_name);

	// next campaign will be false on error or if final
	if ($next_campaign_name !== false) {
		$next_campaign_id = api_campaigns_checknameexists($next_campaign_name);

		// Add targets to next campaign
		$targets = api_targets_listall($campaign_id);
		if (!is_array($targets)) {
			return api_error_raise(sprintf('No targets found for %d', $campaign_id));
		}

		$tags = api_campaigns_tags_get($campaign_id);
		$next_call_delay_hours = isset($tags['next-call-delay-hours']) ? $tags['next-call-delay-hours'] : 2;
		$send_rate_base_hours = isset($tags['send-rate-base-hours']) ? $tags['send-rate-base-hours'] : 2;

		$next_campaign_target_count = 0;

		$checkpoint_time = _mbfs_cascade_campaign_log('Adding targets', $log_array, $start_time, $checkpoint_time);
		foreach ($targets as $target_id => $destination) {
			// Get unsuccessful calls
			$target = api_targets_getinfo($target_id);
			if (empty($target) || ($target['status'] !== 'ABANDONED')) {
				continue;
			}

			// Add to next campaign
			$merge_data = api_data_merge_get_all($campaign_id, $target['targetkey']);
			$next_destination = false;

			// Get the next destination, and replace next_destination with the following
			if (!is_array($merge_data)) {
				return api_error_raise(sprintf('No merge data found for %s - %s', $campaign_id, $target['targetkey']));
			}
			$next_destination = _mbfs_cascade_campaign_get_destination($next_campaign_number, $merge_data);
			$next_target_key = _mbfs_cascade_campaign_get_next_target_key($current_campaign_number, $target['targetkey']);

			$call_results = api_data_callresult_get_all_bytargetid($target_id);

			$generated_time = isset($call_results['GENERATED']) ? $call_results['GENERATED'] : 'now';

			// Add next call attempt time merge data
			// NB: This is for debugging in reports only, the 6th param below sets nextattempt which actually does the work
			$merge_data['next-call-attempt-aest'] = date('Y-m-d H:i:00', strtotime("$generated_time + $next_call_delay_hours hours"));

			// Add well known fields for name and DOB
			$well_known_fields = _mbfs_cascade_campaign_get_well_known_fields($next_campaign_number, $merge_data);
			if (!$well_known_fields) {
				return api_error_raise(sprintf("Couldn't parse well known fields from merge data for %s - %s", $campaign_id, $target['targetkey']));
			}
			$merge_data = array_merge($merge_data, $well_known_fields);


			$result = api_targets_add_single($next_campaign_id, $next_destination, $next_target_key, $target['priority'], $merge_data, $merge_data['next-call-attempt-aest']);
			if ($result !== false) {
				$next_campaign_target_count++;
			}
		}

		$log_array['targets_added'] = $next_campaign_target_count;
		$checkpoint_time = _mbfs_cascade_campaign_log('Added targets', $log_array, $start_time, $checkpoint_time);

		// Update send rate based on tag and number of campaigns
		api_campaigns_setting_set($next_campaign_id, 'sendrate', (int) ceil($next_campaign_target_count / $send_rate_base_hours));

		// Set campaign active
		api_campaigns_setting_set($next_campaign_id, 'status', 'ACTIVE');
		_mbfs_cascade_campaign_log('End post hook', $log_array, $start_time, $checkpoint_time);

	}
}

/**
 * Get microtime as timer
 *
 * @return float
 */
function _mbfs_cascade_campaign_timer_start() {
	return microtime(true);
}

/**
 * Get time since give timer in ms
 *
 * @param float $start_time
 *
 * @return string
 */
function _mbfs_cascade_campaign_timer_elapsed($start_time) {
	$end_time = microtime(true);
	$total_time = $end_time - $start_time;
	return sprintf('%sms', round($total_time, 4) * 1000);
}

/**
 * Log with metrics and return new checkpoint
 *
 * @param string $message
 * @param array  $log_array
 * @param float  $start_time
 * @param float  $checkpoint_time
 *
 * @return float
 */
function _mbfs_cascade_campaign_log($message, array $log_array, $start_time, $checkpoint_time) {
	$log_array['time_elapsed'] = _mbfs_cascade_campaign_timer_elapsed($checkpoint_time);
	$log_array['total_time_elapsed'] = _mbfs_cascade_campaign_timer_elapsed($start_time);
	api_misc_audit('MBFS_CASCADE_CAMPAIGN', sprintf("%s:\t%s", $message, json_encode($log_array)));
	return _mbfs_cascade_campaign_timer_start();
}

/**
 * Get the next campaign name, based on current campaign
 *
 * @param string $campaign_name
 *
 * @return string|boolean
 */
function _mbfs_cascade_campaign_get_next_campaign_name($campaign_name) {
	$suffix_1 = 'SFTP';
	$suffix_1_length = strlen($suffix_1);
	$suffix_2 = 'Contact2';
	$suffix_2_length = strlen($suffix_2);
	$suffix_3 = 'Contact3';

	// check format of campaign
	if (preg_match('/^MBFS-IVR-.*-\w{4,8}$/', $campaign_name) !== 1) {
		return api_error_raise($campaign_name . " doesn't match expected format for mbfs cascade");
	}

	// return false for final campaign
	$next_campaign_name = false;

	// Get next campaign
	if (substr($campaign_name, -1 * $suffix_1_length) === $suffix_1) {
		$next_campaign_name = substr($campaign_name, 0, -1 * $suffix_1_length) . $suffix_2;
	}
	if (substr($campaign_name, -1 * $suffix_2_length) === $suffix_2) {
		$next_campaign_name = substr($campaign_name, 0, -1 * $suffix_2_length) . $suffix_3;
	}

	return $next_campaign_name;
}

/**
 * Get the next target key, based on current target key and campaign
 *
 * @param integer $campaign_number
 * @param string  $target_key
 *
 * @return string|boolean
 */
function _mbfs_cascade_campaign_get_next_target_key($campaign_number, $target_key) {
	$next_target_key = false;
	$base_target_key = substr($target_key, 0, -2);
	switch ($campaign_number) {
		case 1:
		case 2:
			$next_target_key = $base_target_key . '-' . ($campaign_number + 1);
			break;
		default:
			$next_target_key = false;
	}
	return $next_target_key;
}

/**
 * Determine the campaign number (1, 2 or 3) from the name
 *
 * @param string $campaign_name
 * @return integer
 */
function _mbfs_cascade_campaign_get_campaign_number($campaign_name) {
	$suffix_1 = 'SFTP';
	$suffix_1_length = strlen($suffix_1);
	$suffix_2 = 'Contact2';
	$suffix_2_length = strlen($suffix_2);
	$suffix_3 = 'Contact3';
	$suffix_3_length = strlen($suffix_3);

	if (substr($campaign_name, -1 * $suffix_1_length) === $suffix_1) {
		return 1;
	}

	if (substr($campaign_name, -1 * $suffix_2_length) === $suffix_2) {
		return 2;
	}

	if (substr($campaign_name, -1 * $suffix_3_length) === $suffix_3) {
		return 3;
	}
}

/**
 * Determine the destination for the given campaign
 * @param integer $campaign_number
 * @param array   $merge_data
 * @return string|boolean
 */
function _mbfs_cascade_campaign_get_destination($campaign_number, array $merge_data) {
	$destination_field = '';
	switch ($campaign_number) {
		case 1:
			$destination_field = 'Primary_Contact_Number';
			break;
		case 2:
		case 3:
			$destination_field = 'Contact_Number_' . $campaign_number;
			break;
		default:
			return false;
	}

	return (isset($merge_data[$destination_field])) ? $merge_data[$destination_field] : false;
}

/**
 * Determine the well known fields for the given campaign
 * @param integer $campaign_number
 * @param array   $merge_data
 * @return array|boolean
 */
function _mbfs_cascade_campaign_get_well_known_fields($campaign_number, array $merge_data) {
	$data = [];
	switch ($campaign_number) {
		case 1:
			if (!(
				isset($merge_data['Contact_Name'])
				&& isset($merge_data['Contact_DOB_Day'])
				&& isset($merge_data['Contact_DOB_Month'])
				&& isset($merge_data['Contact_DOB_Year'])
			)) {
				return false;
			}
			$data = [
				'CURRENT_NAME' => $merge_data['Contact_Name'],
				'DOB_DAY' => $merge_data['Contact_DOB_Day'],
				'DOB_MONTH' => $merge_data['Contact_DOB_Month'],
				'DOB_YEAR' => $merge_data['Contact_DOB_Year'],
			];
			break;
		case 2:
		case 3:
			if (!(
				isset($merge_data['Contact_Name_' . $campaign_number])
				&& isset($merge_data['Contact_DOB_Day_' . $campaign_number])
				&& isset($merge_data['Contact_DOB_Month_' . $campaign_number])
				&& isset($merge_data['Contact_DOB_Year_' . $campaign_number])
			)) {
				return false;
			}
			$data = [
				'CURRENT_NAME' => $merge_data['Contact_Name_' . $campaign_number],
				'DOB_DAY' => $merge_data['Contact_DOB_Day_' . $campaign_number],
				'DOB_MONTH' => $merge_data['Contact_DOB_Month_' . $campaign_number],
				'DOB_YEAR' => $merge_data['Contact_DOB_Year_' . $campaign_number],
			];
			break;
		default:
			return false;
	}

	return $data;
}
