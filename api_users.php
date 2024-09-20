<?php

use Services\User\UserTypeEnum;

/**
 * Add or Update user
 *
 * @param string  $username
 * @param integer $duplicate
 * @param integer $operating_user_id
 * @return integer|boolean
 */
function api_users_add($username, $duplicate = null, $operating_user_id = null, UserTypeEnum $user_type = null) {

	if (!api_users_check_valid_username($username)) {
		return api_error_raise("Sorry, that is not a valid username format");
	}

	if (api_users_checknameexists($username)) {
		return api_error_raise("Sorry, a user with the username '{$username}' already exists");
	}

	if (!empty($duplicate) && !api_users_can_user_be_duplicated($duplicate, $operating_user_id)) {
		return api_error_raise("Cannot duplicate that user.");
	}

	$lastid = api_keystore_increment("USERS", 0, "nextid");

	// Set some reasonable defaults
	api_users_setting_set($lastid, "username", $username);
	api_users_setting_set($lastid, "created", time());
	api_users_setting_set($lastid, "status", -1);
	api_users_setting_set($lastid, "apirequest.post.limit", 60);
	api_users_setting_set($lastid, "apirequest.get.limit", 60);
	api_users_setting_set($lastid, "jobpriority", "normal");
	api_users_setting_set($lastid, "timezone", DEFAULT_TIMEZONE);
	api_users_setting_set($lastid, "autherrors", 0);
	api_users_setting_set($lastid, "passwordresetcount", 0);
	api_users_setting_set($lastid, "smsapidid", 33);
	api_users_setting_set($lastid, "apirateplan", 4);

	if ($user_type) {
	    api_users_setting_set($lastid, "usertype", $user_type->getValue());
    } else {
        api_users_setting_set($lastid, "usertype", UserTypeEnum::getDefault()->getValue());
    }

	if (!empty($duplicate)) {
		// There are some values that shouldn't be copied across to the new user
		$dontdupe = array(
			'username',
			'created',
			'apilastrequest',
			'apithrottleminute',
			'autherrors',
			'apirequest.post.lastrequest',
			'apirequest.get.lastrequest',
			'apirequest.post.throttleperiod',
			'apirequest.get.throttleperiod',
			'apirequest.post.ratelimits',
			'apirequest.get.ratelimits',
			'saltedpassword',
			'passwordresetcount',
			'passwordresetsent',
			'passwordresettime',
			'firstname',
			'lastname',
			'emailaddress',
			'description',
			'lastauth',
		);

		foreach (api_users_setting_getall($duplicate) as $key => $value) {
			if (!in_array($key, $dontdupe)) {
				api_users_setting_set($lastid, $key, $value);
			}
		}

		return $lastid;
	}

	if ($operating_user_id && !api_users_is_admin_user($operating_user_id)) {
		$creatergroup = api_users_setting_getsingle($operating_user_id, 'groupowner');

		if ($creatergroup) {
			api_users_setting_set($lastid, 'groupowner', $creatergroup);
		}
	}

	return $lastid;
}

/**
 * @param integer $duplicate_user_id
 * @param integer $operating_user_id
 * @return boolean
 */
function api_users_can_user_be_duplicated($duplicate_user_id, $operating_user_id = null) {
    if (
        !api_users_checkidexists($duplicate_user_id) ||
        (
            !is_null($operating_user_id) &&
            (
                !api_users_checkidexists($operating_user_id) ||
                !api_users_has_access_to_module($operating_user_id, $duplicate_user_id, KEY_STORE_TYPE_USERS)
            )
        )
    ) {
        return false;
    }

    return true;
}

// Takes a filename and bulk creates new users

function api_users_add_bulk($filename, $filepath, $duplicate, $options = [], $operating_user_id = null) {

	if (!is_numeric($duplicate) || !api_users_checkidexists($duplicate)) {
		return api_error_raise("Sorry, that is not a valid user to create the new users from");
	}

	if (!api_users_can_user_be_duplicated($duplicate, $operating_user_id)) {
		return api_error_raise('Sorry, you do not have access to duplicate this user');
	}

	if (empty($filename) || empty($filepath) || !preg_match("/\.csv$/", $filename) || !is_readable($filepath)) {
		return api_error_raise("Sorry, that is not a valid file");
	}

	$contents = file($filepath);

	if (!$contents || !($file = array_map('str_getcsv', $contents))) {
		return api_error_raise("Sorry, that is not a valid file");
	}

	// Keep a track of successfully created users
	$success = 0;

	foreach($file as $row => $data) {

		if($row == 0) {

            // Excel UTF-8 files may have something called a Byte Order Mark at the start of the file. Strip it off if we see it
			$data[0] = trim($data[0], chr(0xEF) . chr(0xBB) . chr(0xBF));

			// Find the position of the important columns in the header row. If they are missing, bail out.
			foreach(["firstname", "lastname", "username", "emailaddress"] as $searchterm) {
				if (($position[$searchterm] = array_search($searchterm, array_map('strtolower', $data))) === false) {
					return api_error_raise("Sorry, we could not find the '{$searchterm}' column in the data file");
				}
			}

			continue;
		}

		// This is a data row so create the new user
		if ($userid = api_users_add(trim($data[$position["username"]]), $duplicate)) {
			$success++;
			api_users_setting_set($userid, "firstname", trim($data[$position["firstname"]]));
			api_users_setting_set($userid, "lastname", trim($data[$position["lastname"]]));
			api_users_setting_set($userid, "emailaddress", trim($data[$position["emailaddress"]]));

			if(isset($options["sendpasswordreset"]) && $options["sendpasswordreset"]) {
				api_users_password_resetrequest($userid);
			}
		} else {
			return api_error_raise("Sorry, we cannot continue this process");
		}

	}

	unlink($filepath);

	return $success;

}

// Check if username already assigned

function api_users_checknameexists($name){ return api_keystore_checkkeyexists("USERS", "username", $name); }

// Check if the current userid exists

function api_users_checkidexists($userid){

	if(!is_numeric($userid)) return false;

	if(api_keystore_get("USERS", $userid, "username") !== FALSE) return true;
	else return false;

}

function api_users_nametoid($name){

	$id = api_users_checknameexists($name);

	if(is_numeric($id)) return $id;
	else return false;

}

function api_users_idtoname($id) {
	return api_keystore_get('USERS', $id, USER_SETTING_USERNAME);
}


function api_users_gettimezone($userid){

	if(!is_numeric($userid)) return DEFAULT_TIMEZONE;

	$timezone = api_users_setting_getsingle($userid, "timezone");

	if($timezone == false) {

		api_users_setting_set($userid, "timezone", DEFAULT_TIMEZONE);
		return DEFAULT_TIMEZONE;

	} else return $timezone;

}

function api_users_getusertype($userid){

    if(!is_numeric($userid)) return false;

    $usertype = api_users_setting_getsingle($userid, "usertype");
    if ($usertype) {
        return UserTypeEnum::get($usertype);
    }

}

function api_users_getregion($userid){

	if(!is_numeric($userid)) return false;

	$region = api_users_setting_getsingle($userid, "region");

	return !empty($region) ? $region : "AU";
}

// Find a particular key owner

function api_users_findkey($item, $value){ return api_keystore_checkkeyexists("USERS", $item, $value); }


// Delete user

function api_users_delete($userid){

	if(!api_users_checkidexists($userid)) return false;

    // Remove user account

	api_keystore_purge("USERS", $userid);

	return true;

}

function api_users_delete_byuser($name){

	if(!($id = api_users_checknameexists($name))) return api_error_raise("Sorry, that user doesn't exist");

	return is_numeric($id) ? api_users_delete($id) : false;

}

// List users

function api_users_listall(array $options = []){

	$type = "USERS";

	if (isset($options["countonly"]) && $options["countonly"]) {
		$sql = "SELECT COUNT(`id`) as `count` FROM `key_store` WHERE `type` = ? AND `item` = ?";
	} else {
		$sql = "SELECT `id`, `value` FROM `key_store` WHERE `type` = ? AND `item` = ?";
	}

	$parameters = array($type, "username");

	if (isset($options["activeonly"]) && $options["activeonly"]) {

		$sql .= " AND `id` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` = ?)";
		array_push($parameters, $type, "status", "1");

	}

	if (!empty($options["userid"]) && api_users_checkidexists($options["userid"])) {

		$groups = api_security_groupaccess($options["userid"]);

		if (isset($groups["isadmin"]) && (!$groups["isadmin"]) && count($groups["groups"])) {

			$sql .= " AND `id` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` IN (";
			array_push($parameters, $type, "groupowner");

			foreach ($groups["groups"] as $group) {

				$sql .= "?,";

				array_push($parameters, $group);
			}

			$sql = substr($sql, 0, -1) . "))";

		}

	}

	if (isset($options["search"]) && !empty($options["search"])) {

		if (strpos($options["search"], "*") !== false) {
			$options["search"] = str_replace("\\*", ".+", preg_quote($options["search"], "/"));
		} else {

			$options["search"] = preg_quote($options["search"], "/");
		}

		if (empty($options["searchfields"])) {

			$sql .= " AND `value` REGEXP ?";
			array_push($parameters, $options["search"]);

		} elseif (is_array($options["searchfields"])) {

			$sql .= " AND (0 ";

			foreach ($options["searchfields"] as $searchfield) {

				$sql .= " OR `id` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` REGEXP ?)";
				array_push($parameters, $type, $searchfield, $options["search"]);
			}

			$sql .= ")";
		}
	}

	if(!isset($options["orderby"]) OR !in_array($options["orderby"], array("id", "value"))) {

		$options["orderby"] = "id";
	}

	if(!isset($options["order"]) OR !in_array($options["order"], array("ASC", "DESC"))) {

		$options["order"] = "DESC";

	}

	$options["orderby"] = filter_var($options["orderby"], FILTER_SANITIZE_STRING); // filter_var is used to fix fortify issues
	$sql .= " ORDER BY `". $options["orderby"] . "` ". $options["order"];

	if (!empty($options["limit"]) && (!isset($options["countonly"]) || (!$options["countonly"]))) {

		$sql .= " LIMIT ?";
		array_push($parameters, (int)$options["limit"]);

		if (isset($options["offset"]) && is_numeric($options["offset"])) {

			$sql .= " OFFSET ?";
			array_push($parameters, (int)$options["offset"]);

		}

	}

	$rs = api_db_query_read($sql, $parameters);

	if (isset($options["countonly"]) && $options["countonly"]) {
		return $rs->Fields("count");
	}

	if ($rs && ($rs->RecordCount() > 0)) {

		$results = $rs->GetAssoc();

		if (isset($options["short"]) AND $options["short"]) {
			return array_keys($results);
		} else {
			return $results;
		}

	} else {
		return array();
	}

}

function api_users_ratelimit_check($id, $item = "api"){

	if(!is_numeric($id)) return false;
	if(empty($item)) return false;

	$multi = api_users_setting_get_multi_byitem($id, array($item . ".limit", $item . ".lastrequest", $item . ".throttleperiod", "apirequest.get.limit"));

	$period = 60 / 5;
	$period_limit    = (isset($multi[$item . ".limit"])) ? $multi[$item . ".limit"] : null;
	$last_api_request= (isset($multi[$item . ".lastrequest"])) ? $multi[$item . ".lastrequest"] : 0;
	$last_api_diff 	 = microtime(true) - $last_api_request;
	$period_throttle = (isset($multi[$item . ".throttleperiod"])) ? $multi[$item . ".throttleperiod"] : 0;

	if(!is_numeric($period_limit)) {

		if(!empty($multi["apirequest.get.limit"]) AND is_numeric($multi["apirequest.get.limit"])) $period_limit = $multi["apirequest.get.limit"];
		else $period_limit = API_RATE_LIMIT_PERMIN;

		api_users_setting_set($id, $item . ".limit", $period_limit);
	}

	$period_limit = $period_limit / 5;

	if (is_null($period_limit)) $new_period_throttle = 0;
	else {

		$new_period_throttle = $period_throttle - $last_api_diff;
		$new_period_throttle = $new_period_throttle < 0 ? 0 : $new_period_throttle;
		$new_period_throttle += $period / $period_limit;
		$period_hits_remaining = floor(($period - $new_period_throttle) * $period_limit / $period);
        // can output this value with the request if desired:
		$period_hits_remaining = $period_hits_remaining >= 0 ? $period_hits_remaining : 0;
	}

	if ($new_period_throttle > $period) {

		$apiratelimits = api_users_setting_increment($id, $item . ".ratelimits");

		if($apiratelimits == 0) api_users_setting_set($id, $item . ".ratelimits", 1);

        // Sleep for 150ms to try and slow down the request on our end.
		usleep(150000);

		return true;
	}

            // Save the values back to the database.
	api_users_setting_set($id, $item . ".lastrequest", microtime(true));
	api_users_setting_set($id, $item . ".throttleperiod", $new_period_throttle);

	return false;
}

function api_users_password_resetrequest($userid) {

	if (!api_users_checkidexists($userid)) return api_error_raise("Sorry, that is not a valid user");

	$email["to"] = api_users_setting_getsingle($userid, "emailaddress");

	if (empty($email["to"])) return api_error_raise("Sorry, this user doesn't have an email address");

	$username = api_users_setting_getsingle($userid, "username");
	$version  = api_users_setting_getsingle($userid, "passwordresetcount");
	api_users_setting_set($userid, "passwordresetsent", time());

	$version = $version + 1;

	api_users_setting_set($userid, "passwordresetcount", $version);

	$hosttrack = api_hosts_gettrack();
	$link = "{$hosttrack}/reset.php?id=" . api_misc_crypt_safe("PR::" . $userid . "::" . $version);

	$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"]     = "[ReachTEL] Password reset for user '" . $username . "'";
	$email["textcontent"] = "Hello,\n\nWe have received a request to reset the password for the username " . $username . "\n\nIf this was you, please click the below link to get your new password.\n\n" . $link . "\n\nThis reset link will expire in 60 minutes.\n\nIf this was not you, please ignore this email and the password will not be changed.";
	$email["htmlcontent"] = "Hello,\n\nWe have received a request to reset the password for the username <strong>" . $username . "</strong>\n\nIf this was you, please click the below link to get your new password.\n\n<a href=\"" . $link . "\">Reset password</a>\n\nThis reset link will expire in 60 minutes.\n\nIf this was not you, please ignore this email and the password will not be changed.";

	api_misc_audit("AUTH", "Password reset sent for username=" . $username . "; userid=" . $userid);

	return api_email_template($email);
}

function api_users_password_reset($userid, $password, $passwordagain, $options = array()) {

	if (!api_users_checkidexists($userid)) return api_error_raise("Sorry, that is not a valid user id");

	$username = api_users_setting_getsingle($userid, "username");

	if(isset($options["version"])){

		if(api_users_setting_getsingle($userid, "passwordresetcount") !== $options["version"]) {

			api_misc_audit("AUTH", "Password reset already used for username=" . $username . "; userid=" . $userid);

			return api_error_raise("Sorry, this reset link has expired or has already been used.");
		}

		if(api_users_setting_getsingle($userid, "passwordresetsent") < (time() - 3600)) {

			api_misc_audit("AUTH", "Password reset expired for username=" . $username . "; userid=" . $userid);

			return api_error_raise("Sorry, this reset link has expired or has already been used.");
		}
	}

	if(empty($password) OR empty($passwordagain)) return api_error_raise("Sorry, the passwords do not match.");
	elseif($password != $passwordagain) return api_error_raise("Sorry, the passwords do not match.");
	elseif(strlen($password) < 8) return api_error_raise("Sorry, the password must be at least 8 characters in length.");
	elseif(strlen($password) > 128) return api_error_raise("Sorry, the password must be less than 128 characters in length.");
	elseif(preg_match("/" . preg_quote($username) . "/i", $password)) return api_error_raise("Sorry, the password cannot contain your username.");
	elseif(!preg_match("/[A-Z]/", $password)) return api_error_raise("Sorry, the password must contain at lease 1 uppercase character [A-Z].");
	elseif(!preg_match("/[a-z]/", $password)) return api_error_raise("Sorry, the password must contain at lease 1 lowercase character [a-z].");
	elseif(!preg_match("/[0-9]/", $password)) return api_error_raise("Sorry, the password must contain at lease 1 digit [0-9].");

	// Check for password re-use
	$existingpassword = api_users_setting_getsingle($userid, "saltedpassword");

	if(!empty($existingpassword) AND password_verify($password, $existingpassword)) return api_error_raise("Sorry, you cannot re-use your last password");

	// All checks have passed - set the new password
	api_users_setting_set($userid, "saltedpassword", password_hash($password, PASSWORD_ALGO, array("cost" => PASSWORD_COST)));
	api_users_setting_increment($userid, "passwordresetcount");
	api_users_setting_set($userid, "passwordresettime", time());
	api_users_setting_delete_single($userid, "passwordresetsent");

	api_misc_audit("AUTH", "Password reset for username=" . $username . "; userid=" . $userid);

	return true;
}

/**
 * Check if user's password has expired
 *
 * @param int $userid
 * @return boolean
 */
function api_users_has_password_expired($userid) {
	if (defined('USER_LOGIN_PASSWORD_EXPIRATION_DISABLED')) {
		$threshold = constant('USER_LOGIN_PASSWORD_EXPIRATION_DISABLED');

		if (true === $threshold) {
			// password expiration feature disabled for all users
			return false;
		}
	}

	$time = api_users_setting_getsingle($userid, "passwordresettime");

	// Checks password expiration for selected user only (threshold)
	// USER_LOGIN_PASSWORD_EXPIRATION_DISABLED is a datetime
	if (isset($threshold) && (!$time || $time < $threshold)) {
		return false;
	}

	return (!$time || $time < (time() - (86400 * constant('USER_LOGIN_PASSWORD_EXPIRATION'))));
}

/**
 * Print CSV report of active status users who haven't logged in recently
 *
 * @param integer $days
 *
 * @return boolean|void
 */
function api_users_print_recently_inactive_report_csv($days = 90) {
	if (empty($days) || !is_numeric($days)) {
		return api_error_raise("Sorry, that is not a valid number of days");
	}

	header("Content-type: application/text-csv");
	header("Content-disposition: attachment; filename=\"InactiveUsers-" . date("Ymd-His") . ".csv\"");

	print "groupowner,username,name,emailaddress,description,created,lastauth\n";

	foreach (api_keystore_getidswithvalue("USERS", "status", USER_STATUS_ACTIVE) as $userid) {
		$settings = api_users_setting_getall($userid);

		if (empty($groupowners[$settings["groupowner"]])) {
			$groupowners[$settings["groupowner"]] = api_groups_setting_getsingle($settings["groupowner"], "name");
		}

		if (!empty($settings["lastauth"]) && ($settings["lastauth"] > (time() - (86400 * $days)))) {
			continue; // If the account was accessed in the last X days, skip.
		}

		if (!empty($settings["created"]) && ($settings["created"] > (time() - (86400 * $days)))) {
			continue; // If the account was created in the last X days, skip.
		}

		$lastauth = empty($settings["lastauth"]) ? 'unknown' : date("Y-m-d H:i:s", $settings["lastauth"]);
		$created = empty($settings['created']) ? 'unknown' : date("Y-m-d H:i:s", $settings['created']);

		print $groupowners[$settings["groupowner"]] . "," . $settings["username"] . "," . trim($settings["firstname"] . " " . $settings["lastname"]) . "," . $settings["emailaddress"] . "," . $settings["description"] . "," . $created . "," . $lastauth . "\n";
	}

	exit;
}

// User settings

  // Add or update setting

function api_users_setting_set($userid, $setting, $value) { return api_keystore_set("USERS", $userid, $setting, $value); }

function api_users_setting_increment($userid, $setting) { return api_keystore_increment("USERS", $userid, $setting); }

  // Delete setting

    // Single
function api_users_setting_delete_single($userid, $setting) { return api_keystore_delete("USERS", $userid, $setting); }


  // Get

    // Single

function api_users_setting_getsingle($userid, $setting) { return api_keystore_get("USERS", $userid, $setting); }

    // All

function api_users_setting_getall($userid) { return api_keystore_getnamespace("USERS", $userid); }

    // Multi
function api_users_setting_get_multi_byitem($userid, $items) { return api_keystore_get_multi_byitem("USERS", $userid, $items); }

function api_users_tags_get($id, $tags = null){

	if(!api_users_checkidexists($id)) return api_error_raise("Sorry, that is not a valid user ID");

	return api_tags_get('USERS', $id, $tags);

}

function api_users_tags_set($id, array $tags = [], array $encrypt_tags = []){

	if(!api_users_checkidexists($id)) return api_error_raise("Sorry, that is not a valid user ID");

	return api_tags_set('USERS', $id, $tags, $encrypt_tags);

}

function api_users_tags_delete($id, array $tags = []){

	if(!api_users_checkidexists($id)) return api_error_raise("Sorry, that is not a valid user ID");

	return api_tags_delete('USERS', $id, $tags);

}

function api_users_tags_get_all_details($id) {
	if(!api_users_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid user ID");
	}

	return api_tags_get_existing_tag_details('USERS', $id, true);
}

function api_users_check_valid_username($username) {
    return preg_match("/^[A-Za-z0-9_\-\.]{3,25}$/i", $username) === 1;
}

function api_users_has_access_to_module($user_id, $module_id, $module_type) {
	if (api_users_is_admin_user($user_id)) {
		return true;
	}

	$groups = api_security_groupaccess($user_id);

	$group_owner = api_keystore_get($module_type, $module_id, 'groupowner');

	return $group_owner && in_array($group_owner, $groups['groups']);
}

function api_users_is_admin_user($userid) {
	$groups = api_security_groupaccess($userid);

	if ($groups['isadmin']) {
		return true;
	}

	return false;
}

/**
 * Check if a user is active
 *
 * @param integer $userid
 *
 * @return boolean
 */
function api_users_isactive($userid) {
	if(!api_users_checkidexists($userid)) {
		return api_error_raise("Sorry, that is not a valid user ID");
	}

	return api_users_setting_getsingle($userid, 'status') === USER_STATUS_ACTIVE;
}

/**
 * @param integer $userid
 * @param string  $sessionid
 * @param boolean $skip_userid_check
 * @return boolean
 */
function api_users_store_session_id($userid, $sessionid, $skip_userid_check = false) {
	if (!$skip_userid_check && !api_users_checkidexists($userid)) {
		return api_error_raise('Sorry, that is not a valid user ID');
	}

	return api_users_setting_set($userid, USER_SETTING_SESSION_ID, $sessionid);
}

/**
 * @param integer $userid
 * @param boolean $skip_userid_check
 * @return boolean
 */
function api_users_destroy_session_id($userid, $skip_userid_check = false) {
	if (!$skip_userid_check && !api_users_checkidexists($userid)) {
		return api_error_raise('Sorry, that is not a valid user ID');
	}

	return api_users_setting_delete_single($userid, USER_SETTING_SESSION_ID);
}

/**
 * @param integer $userid
 * @return false|string
 */
function api_users_fetch_session_id($userid) {
	return api_users_setting_getsingle($userid, USER_SETTING_SESSION_ID);
}

/**
 * @param $userid
 * @return boolean
 */
function api_users_is_technical_admin($userid) {
	return defined('TECHNICAL_ADMIN_USERIDS') &&
		is_array(constant('TECHNICAL_ADMIN_USERIDS')) &&
		in_array($userid, constant('TECHNICAL_ADMIN_USERIDS'));
}

/**
 * @param $groupid
 * @return array|boolean
 */
function api_users_list_all_by_groupowner($groupid) {
	if (!api_groups_checkidexists($groupid)) {
		return api_error_raise('Sorry, that is not a valid group ID');
	}

	return api_keystore_getidswithvalue(KEY_STORE_TYPE_USERS, USER_SETTING_GROUP_OWNER, $groupid);
}

/**
 * Returns an array of users ids for the given group ids
 * @param array $groupids
 * @return array
 */
function api_users_list_all_by_groupowners(array $groupids) {
	$users = [];
	foreach ($groupids as $id) {
		$userids = api_users_list_all_by_groupowner($id);

		if ($userids == false) {
			continue;
		}
		$users = array_merge($users, $userids);
	}
	return $users;
}

/**
 * Fetches a list of users in the given group ids who have logged in since $last_login_timestamp
 *
 * Returns an array keyed by user_id
 *
 * @param array $groupowner_ids
 * @param DateTime $last_login_timestamp
 * @return an|bool
 */
function api_users_list_by_groupowners_last_login(array $groupowner_ids, DateTime $last_login_timestamp){
	if(empty($groupowner_ids)){
		return false;
	}
	$query = "SELECT
				DISTINCT 
					users.id as user_id,
					lastauth.value as lastauth,
					groupowners.value as groupowner
				FROM
					key_store AS users
				JOIN key_store AS lastauth ON
					(users.id = lastauth.id
						AND lastauth.type = 'USERS'
						AND lastauth.item = 'lastauth')
				JOIN key_store AS groupowners ON
					(users.id = groupowners.id
						AND
						groupowners.type = 'USERS'
						AND groupowners.item = 'groupowner')
				WHERE users.type = 'USERS'
				AND groupowners.value IN (".implode(',', array_fill(0, count($groupowner_ids), '?')).")
				AND lastauth.value >= ?
				ORDER BY groupowners.value;";
		$rs = api_db_query_read(
			$query,
			array_merge($groupowner_ids, [$last_login_timestamp->getTimestamp()])
		);

		if(!$rs){
			return false;
		}
		return $rs->GetAssoc();
}

/**
 * Fetch a list of users in the given group ids with the given status codes (1 = active, etc)
 *
 * @param array $user_ids
 * @param $status
 * @return array
 */
function api_users_list_by_groupowners_and_status(array $groupowner_ids, array $statuses){

	$query = "SELECT
				DISTINCT 
					users.id as user_id,
					statuses.value as status,
					groupowners.value as groupowner
				FROM
					key_store AS users
				JOIN key_store AS statuses ON
					(users.id = statuses.id
						AND statuses.type = 'USERS'
						AND statuses.item = 'status')
				JOIN key_store AS groupowners ON
					(users.id = groupowners.id
						AND
						groupowners.type = 'USERS'
						AND groupowners.item = 'groupowner')
				WHERE users.type = 'USERS'
				AND groupowners.value IN (".implode(',', array_fill(0, count($groupowner_ids), '?')).")
				AND statuses.value IN (".implode(',', array_fill(0, count($statuses), '?')).")
				ORDER BY groupowners.value;";

	$rs = api_db_query_read(
		$query,
		array_merge($groupowner_ids, $statuses)
	);

	if(!$rs){
		return false;
	}
	return $rs->GetAssoc();
}

function api_users_send_google_auth_qr($userId)
{
    $useremail = api_users_setting_getsingle($userId, USER_SETTING_EMAIL);
    if (!$useremail) {
        return api_error_raise('QR could not be sent as the email address is not set for the user.');
    }

    $authenticator = \Services\Container\ContainerAccessor::getContainer()->get(\Services\Authenticators\GoogleMultiFactorAuthenticator::class);

    try {
        $qrUrl = $authenticator->createQR($userId);
    } catch (Exception $exception) {
        return api_error_raise('QR code could not be created for user id ' . $userId . ' to be sent in email');
    }


    $email["to"]      = $useremail;
    $email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Google Authenticator QR code";
    $email["textcontent"] = "Please find the url to access your google authenticator QR code for logging in to Morpheus admin portal. Please scan the code using the google authenticator app. ". $qrUrl;
    $email["htmlcontent"] = "<p>Please find below your  google authenticator QR code for logging in to Morpheus admin portal. Please scan the code using the google authenticator app.</p><br>" .
         "<img src='" . $qrUrl . "'>";

    return api_email_template($email);
}
