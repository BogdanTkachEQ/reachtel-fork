<?php
/**
 * Cron Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

/**
 * Add a cron
 *
 * @param string $name
 * @return integer
 */
function api_cron_add($name) {

	if ((strlen($name) < 4) || (strlen($name) > 50)) {
		return api_error_raise("Sorry, the name must be more than 4 characters and less than 50");
	}

	if (api_cron_checknameexists($name)) {
		return api_error_raise("Sorry, a cron with that name already exists");
	}

	$id = api_keystore_increment("CRON", 0, "nextid");

	api_cron_setting_set($id, "name", $name);
	api_cron_setting_set($id, "scriptname", "");
	api_cron_setting_set($id, "description", "");
	api_cron_setting_set($id, "lastrun", "");
	api_cron_setting_set($id, "status", "DISABLED");
	api_cron_setting_set($id, "timezone", DEFAULT_TIMEZONE);
	api_cron_setting_set($id, "minute", "*");
	api_cron_setting_set($id, "hour", "*");
	api_cron_setting_set($id, "dayofmonth", "*");
	api_cron_setting_set($id, "month", "*");
	api_cron_setting_set($id, "dayofweek", "*");

	return $id;
}

/**
 * Delete a cron
 *
 * @param integer $id
 * @return boolean
 */
function api_cron_delete($id) {

	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a cron event");
	}

	api_keystore_purge("CRON", $id);

	return true;
}

/**
 * List all crons
 *
 * @param boolean $short
 * @param boolean $activeonly
 * @return array
 */
function api_cron_listall($short = false, $activeonly = false) {

	$crons = api_keystore_getentirenamespace("CRON");

	$allcrons = array();

	if ($crons) {
		foreach ($crons as $id => $keys) {
			if (!$activeonly || ($activeonly && ($keys["status"] == "ACTIVE"))) {
				if ($short) {
					$allcrons[$id] = $keys["name"];
				} else {
					$allcrons[$id] = $keys;
				}
			}
		}
	}

	return $allcrons;
}

/**
 * Run a cron
 *
 * @return void
 */
function api_cron_run() {

	foreach (api_cron_listall(true, true) as $id => $name) {
		if (api_cron_isdue($id)) {
			api_misc_audit("CRON", "Enqueued=" . $id . "; Name=" . $name);
			api_queue_add("cron", array("cronid" => $id));
		}
	}
}

/**
 * Takes a cron id and checks if it should be run now
 *
 * @param integer $id
 * @param string  $time
 * @return boolean
 */
function api_cron_isdue($id, $time = "now") {

	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid cron event");
	}

	// Check if the cron event is active
	$status = api_cron_setting_getsingle($id, "status");

	if (empty($status) || ($status != "ACTIVE")) {
		return false;
	}

	// Get the structure of valid periods
	$cron = api_cron_parse($id);

	if (!$cron || !is_array($cron)) {
		return api_error_raise("Sorry, we can't process that cron structure");
	}

	// Set the time zone we are checking
	$now = new DateTime($time);

	try {
		$timezone = api_cron_setting_getsingle($id, "timezone");
		$timezone = new DateTimeZone($timezone);
	} catch (Exception $e) {
		api_misc_audit("INVALID_TIMEZONE", "timezone=" . $timezone . "; cronid=" . $id);

		$timezone = false;
	}

	// If the timezone was rejected, bail out
	if ($timezone === false) {
		return api_error_raise("Sorry, that is not a valid timezone");
	}

	$now->setTimezone($timezone);

	foreach ($cron as $segment => $tokens) {
		if (empty($tokens)) {
			continue; // No tokens defaults to match all
		}
		switch ($segment) {
			case "minute":
				if (in_array(intval($now->format("i")), $tokens)) {
					continue(2);
				}
				break;
			case "hour":
				if (in_array($now->format("G"), $tokens)) {
					continue(2);
				}
				break;
			case "dayofmonth":
				if (in_array($now->format("j"), $tokens)) {
					continue(2);
				}
				break;
			case "month":
				if (in_array($now->format("n"), $tokens)) {
					continue(2);
				}
				break;
			case "dayofweek":
				if (in_array($now->format("N"), $tokens)) {
					continue(2);
				}
				break;
		}

		return false; // No matches found for this period
	}

	return true;
}

/**
 * Parse a cron
 * @param integer $id
 * @return false|array
 */
function api_cron_parse($id) {

	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid cron event");
	}

	$segments = api_cron_setting_get_multi_byitem($id, array("minute", "hour", "dayofmonth", "month", "dayofweek"));

	$cron = array("minute" => array(), "hour" => array(), "dayofmonth" => array(), "month" => array(), "dayofweek" => array());

	$dayofweekpattern = array("/Mon/i", "/Tue/i", "/Wed/i", "/Thu/i", "/Fri/i", "/Sat/i", "/Sun/i");
	$dayofweekreplace = array(1, 2, 3, 4, 5, 6, 7);

	foreach ($cron as $segment => $value) {
		$tokens = array();

		if (!isset($segments[$segment]) || ($segments[$segment] == "*")) {
			continue; // Empty defaults to match everything
		} elseif (preg_match("/,/", $segments[$segment])) {
			$tokens = explode(",", $segments[$segment]); // Handle lists ("8,14,18")
		} else {
			$tokens[] = $segments[$segment];
		}

		// Handle ranges ("8-12")
		foreach ($tokens as $token) {
			// Substitute textual day of week ("Mon") for numeric ("1")
			if ($segment == "dayofweek") {
				$token = preg_replace($dayofweekpattern, $dayofweekreplace, $token);
			}

			if (preg_match("/(\d+)\-(\d+)/", $token, $matches) && ($matches[1] < $matches[2])) {
				for ($i = $matches[1]; $i <= $matches[2];
				$i++) {
					$cron[$segment][] = (int)$i;
				}
			} elseif (is_numeric($token)) {
				$cron[$segment][] = (int)$token;
			} else {
				return api_error_raise("Sorry, that is not a well formed cron");
			}
		}
	}

	return $cron;
}

/**
 * Checks a cron exists by id
 *
 * @param integer $id
 * @return false|integer
 */
function api_cron_checkidexists($id) {

	if (!is_numeric($id)) {
		return false;
	}

	return api_cron_setting_getsingle($id, "name") !== false;
}

/**
 * Checks if name already exists
 *
 * @param string $name
 * @return mixed|boolean
 */
function api_cron_checknameexists($name) {
	return api_keystore_checkkeyexists("CRON", "name", $name);
}

/**
 * Set a cron keystore value
 *
 * @param integer $id
 * @param string  $setting
 * @param mixed   $value
 * @return boolean
 */
function api_cron_setting_set($id, $setting, $value) {
	return api_keystore_set("CRON", $id, $setting, $value);
}

/**
 * Increment a cron keystore value
 *
 * @param integer $id
 * @param string  $setting
 * @return integer|false
 */
function api_cron_setting_increment($id, $setting) {
	return api_keystore_increment("CRON", $id, $setting);
}

/**
 * Delete a cron keystore value
 *
 * @param integer $id
 * @param string  $setting
 * @return boolean
 */
function api_cron_setting_delete_single($id, $setting) {
	return api_keystore_delete("CRON", $id, $setting);
}

/**
 * Purge cron keystore values
 *
 * @param integer $id
 * @return boolean
 */
function api_cron_setting_delete_all($id) {
	return api_keystore_purge("CRON", $id);
}

/**
 * Get a cron keystore value
 *
 * @param integer $id
 * @param string  $setting
 * @return string|false
 */
function api_cron_setting_getsingle($id, $setting) {
	return api_keystore_get("CRON", $id, $setting);
}

/**
 * Get namespace cron keystore values
 *
 * @param integer $id
 * @return array|false
 */
function api_cron_setting_getall($id) {
	return api_keystore_getnamespace("CRON", $id);
}

/**
 * Get cron keystore value by items
 *
 * @param string $id
 * @param array  $items
 * @return array
 */
function api_cron_setting_get_multi_byitem($id, array $items) {
	return api_keystore_get_multi_byitem("CRON", $id, $items);
}

/**
 * Get a cron tag values
 *
 * @param integer $id
 * @param mixed   $tags
 * @return array|false
 */
function api_cron_tags_get($id, $tags = null) {

	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid cron id");
	}

	return api_tags_get('CRON', $id, $tags);
}

/**
 * Set cron tag values
 *
 * @param integer $id
 * @param array   $tags
 * @param array   $encrypt_tags
 * @return boolean
 */
function api_cron_tags_set($id, array $tags = [], array $encrypt_tags = []) {

	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid cron id");
	}

	return api_tags_set('CRON', $id, $tags, $encrypt_tags);
}

/**
 * Delete cron tags
 *
 * @param integer $id
 * @param array   $tags
 * @return boolean
 */
function api_cron_tags_delete($id, array $tags = []) {

	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid cron id");
	}

	return api_tags_delete('CRON', $id, $tags);
}

/**
 * @param integer $id
 * @return array|boolean
 */
function api_cron_tags_get_all_details($id) {
	if (!api_cron_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid cron id");
	}

	return api_tags_get_existing_tag_details('CRON', $id, true);
}
