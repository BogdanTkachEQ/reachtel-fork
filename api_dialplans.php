<?php

// Add or Update dial plans

function api_dialplans_add($dialplan, $user_group_id = null){

	if(!preg_match("/^([a-z0-9\-_]{4,75})$/i", $dialplan)) return api_error_raise("Sorry, that is not a valid dial plan name");

	if(api_dialplans_checknameexists($dialplan)) return api_error_raise("Sorry, a dial plan with the name '" . $dialplan . "' already exists");

	$lastid = api_keystore_increment("DIALPLANS", 0, "nextid");

	api_dialplans_setting_set($lastid, "name", $dialplan);
	api_dialplans_setting_set($lastid, "groupowner", $user_group_id === null ? 2 : $user_group_id);
	api_dialplans_setting_set($lastid, "version", 1);

	$filename = SAVE_LOCATION . DIALPLAN_LOCATION . "/autodialer-" . $dialplan . ".conf";

	touch($filename);

	chmod($filename, 0755);

	api_misc_audit("DIALPLANCREATE", $lastid, $_SESSION['userid']);

	return $lastid;

}

function api_dialplans_update($dialplanid, $content, $version, $options = array()) {

	if(!api_dialplans_checkidexists($dialplanid)) return api_error_raise("Sorry, that is not a valid dial plan id");

	if(!is_numeric($version)) return api_error_raise("Sorry, that is not a valid dial plan version");

	$currentversion = api_dialplans_setting_getsingle($dialplanid, "version");

	if(empty($options["forceupdate"]) AND ($version != $currentversion)) {

		api_templates_assign("forceupdate", 1);

		return api_error_raise("Sorry, that dial plan has been saved since you've opened it. Save again to force overwrite any changes.");

	}

	$name = api_dialplans_setting_getsingle($dialplanid, "name");

	$filename = SAVE_LOCATION . DIALPLAN_LOCATION . "/autodialer-" . $name . ".conf";

	$content = "[" . $name . "]\n" . stripslashes($content);

	if(file_put_contents($filename, $content)){

		if(!empty($options["groupowner"])) api_dialplans_setting_set($dialplanid, "groupowner", $options["groupowner"]);

		foreach(api_voice_servers_listall_active() as $serverid => $name) api_queue_add("filesync", array("paths" => array("dialplans"), "servers" => array($serverid => $name)));

		api_dialplans_setting_set($dialplanid, "version", $_POST['version']+1);

		$userid = (empty($_SESSION['userid']) ? null : $_SESSION['userid']);
		api_misc_audit("DIALPLANUPDATE", $dialplanid, $userid);

		return true;

	}

	return api_error_raise("Sorry, failed to save that dial plan");

}

function api_dialplans_get($dialplanid) {

	if(!api_dialplans_checkidexists($dialplanid)) return api_error_raise("Sorry, that is not a valid dial plan id");

	$name = api_dialplans_setting_getsingle($dialplanid, "name");

	$filename = READ_LOCATION . DIALPLAN_LOCATION . "/autodialer-" . $name . ".conf";

	if(is_readable($filename)) $content = file_get_contents($filename);
	else return api_error_raise("Sorry, that dial plan is not available");

	return substr($content, strlen("[" . $name . "]\n"));

}

function api_dialplans_object($rawdialplan) {
	// returns an array structure representing the dialplan

	$errors = [];

	// REACHTEL-305 checks for invalid contexts
	foreach(explode("\n", $rawdialplan) as $l => $content) {
		$trimmed = trim($content);
		if($trimmed && preg_match('/^\[(?![\w \-]*\])/', $trimmed, $matches)) {
			$errors[] = sprintf(
				"Line %d: Context '%s' is invalid",
				$l,
				$content
			);
		}
	}

	// Split the raw dialplan into individual contexts
	$contextRegex = "/[\s]*\[([a-z0-9\-]+)\]/i";

	if(!($exploded = preg_split($contextRegex, trim($rawdialplan), 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY))) {
		return api_error_raise("Sorry, we were unable to parse that dial plan");
	}

	/*
		Parse the context into:
		1) Extension
		2) Priority
		3) Label (optional)
		4) Application
		5) Application parameters

	*/
	$dialplanRegex = "/exten => ([^,]+),([0-9n]+)(?:\(([^)]+)\))?,([^\n(]+)(?:\((.+)?\))?/i";

	$agiRegex = "/response_data\?action=(.+)&value=(.+)/i";

	$contexts = [];

	foreach($exploded as $key => $value) {
		if ($key == 0 || !($key & 1)) {
			$name = $value;
		} else if (preg_match_all($dialplanRegex, $value, $matches, PREG_SET_ORDER)){
			foreach($matches as $line){

				$priority = ($line[2] == "n") ? @count($contexts[$name][$line[1]]) + 1 : $line[2];
				$contexts[$name][$line[1]][$priority] = [
					'label' => !empty($line[3]) ? $line[3] : null,
					'application' => isset($line[4]) ? $line[4] : null,
					'parameters' => isset($line[5]) ? $line[5] : null,
				];

				if ($contexts[$name][$line[1]][$priority]['application'] == "Goto") {
					$contexts[$name][$line[1]][$priority]['processedparameters'] = explode(",", $contexts[$name][$line[1]][$priority]['parameters']);
				} else if ($contexts[$name][$line[1]][$priority]['application'] == "AGI") {
					$url = parse_url($contexts[$name][$line[1]][$priority]['parameters']);
					$contexts[$name][$line[1]][$priority]['processedparameters']['application'] = $url['path'];
					if (!empty($url['query'])) {
						parse_str($url['query'], $contexts[$name][$line[1]][$priority]['processedparameters']['query']);
					} else {
						$contexts[$name][$line[1]][$priority]['processedparameters']['query'] = [];
					}
				} else if (in_array($contexts[$name][$line[1]][$priority]['application'], ['Background', 'Playback']) && preg_match("/^audio\/(.+)$/i", $contexts[$name][$line[1]][$priority]['parameters'], $audioMatch)) {
					$contexts[$name][$line[1]][$priority]['processedparameters']['filename'] = $audioMatch[1];
				}
			}
		}
	}

	// syntax errors check
	$lines = array_map('trim', explode("\n", $rawdialplan));
	$exten = $context_map = [];
	foreach($lines as $l => $line) {
		$line = trim($line);
		if ($line) {
			// check invalid line
			if (!preg_match('/^(;|\[|exten|same|include|ignorepat|switch)/i', $line)) {
				$errors[] = sprintf(
					"Line %d: '%s' is invalid",
					$l,
					$line
				);
				continue;
			}
			// grab context
			if ('[' === $line[0]) {
				$context = $line;
				$context_map[$context][] = $l;
			} else { // not a context
				// grouping 'exten => extension,priority' sequences 1 and n
				if (preg_match('/^exten => ([^,]+,\d+)/', $line, $matches)) {
					$exten[$context][$matches[1]][$l] = $line;
				}
			}
		}
	}

	// look for duplicate contexts
	foreach($context_map as $context => $lines) {
		if (count($lines) > 1) {
			$errors[] = sprintf(
				"Lines %s: Duplicate context %s",
				implode(',', $lines),
				$context
			);
		}
	}

	// look for invalid exten
	foreach($exten as $context => $exts) {
		foreach($exts as $ext => $duplicates) {
			// duplicates
			if (count($duplicates) > 1) {
				$errors[] = sprintf(
					"Lines %s: %s Duplicate extension '%s'",
					implode(',', array_keys($duplicates)),
					$context,
					$ext
				);
			}
		}
	}

	return ['structure' => $contexts, 'errors' => $errors];

}


// Check if dialplan already assigned

function api_dialplans_checknameexists($dialplan){ return api_keystore_checkkeyexists("DIALPLANS", "name", $dialplan); }

// Check if the current campaignid exists

function api_dialplans_checkidexists($dialplanid){

	if(!is_numeric($dialplanid)) return api_error_raise("Sorry, that is not a valid dial plan id");

	if(api_keystore_get("DIALPLANS", $dialplanid, "name") !== FALSE) return true;
	else return false;

}

// Delete dial plan

function api_dialplans_delete($dialplanid){

	if(!api_dialplans_checkidexists($dialplanid)) return api_error_raise("Sorry, that is not a valid dial plan id");

    // Unlink dial plan file
	$filename = SAVE_LOCATION . DIALPLAN_LOCATION . "/autodialer-" . api_dialplans_setting_getsingle($dialplanid, "name") . ".conf";
	unlink($filename);

	api_keystore_purge("DIALPLANS", $dialplanid);

	foreach(api_voice_servers_listall_active() as $serverid => $name) api_queue_add("filesync", array("paths" => array("dialplans"), "servers" => array($serverid => $name)));

	api_misc_audit("DIALPLANDELETE", $dialplanid, $_SESSION['userid']);

	return true;

}

// List dial plan files

function api_dialplans_listall($short = 0, $checkUser = false){

	$names = api_keystore_getids("DIALPLANS", "name", true);

	if(empty($names) OR !is_array($names)) return array();

	if($checkUser) {
		$groups = api_security_groupaccess($_SESSION['userid']);
		$groupowners = api_keystore_getids("DIALPLANS", "groupowner", true);
	}

	$dialplans = array();

	foreach($names as $key => $value) {

		if($checkUser AND ($groups["isadmin"] OR in_array($groupowners[$key], $groups["groups"]))) $dialplans[$key] = $value;
		elseif(!$checkUser) $dialplans[$key] = $value;

	}

 	natcasesort($dialplans);

	return $dialplans;

}

function api_dialplans_has_user_got_access($user_id, $dial_plan_id) {
	return api_users_has_access_to_module($user_id, $dial_plan_id, 'DIALPLANS');
}

function api_dialplans_list_user_groups_that_can_update($user_id) {
	$groups = api_security_groupaccess($user_id);
	return ($groups['isadmin']) ? api_groups_listall() : api_groups_listall_for_user($user_id);
}

// Dial plan settings

  // Add or update setting

function api_dialplans_setting_set($dialplanid, $setting, $value) { return api_keystore_set("DIALPLANS", $dialplanid, $setting, $value); }

  // Delete setting

    // Single
function api_dialplans_setting_delete_single($dialplanid, $setting) { return api_keystore_delete("DIALPLANS", $dialplanid, $setting); }


  // Get

    // Single

function api_dialplans_setting_getsingle($dialplanid, $setting) { return api_keystore_get("DIALPLANS", $dialplanid, $setting); }

	// Get all

function api_dialplans_setting_getall($dialplanid) { return api_keystore_getnamespace("DIALPLANS", $dialplanid); }
