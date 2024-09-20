<?php

// Add or Update security zones

function api_security_zone_add($securityzone){

	if(api_security_zone_checknameexists($securityzone)) return api_error_raise("Sorry, that zone already exists");

	if(!preg_match("/^(.{3,50})$/i", $securityzone)) return api_error_raise("Sorry, that is not a valid zone name");

	$lastid = api_keystore_increment("SECURITYZONE", 0, "nextid");

	api_security_zone_setting_set($lastid, "name", $securityzone);

	return $lastid;

}

// Check if security zone already assigned

function api_security_zone_checknameexists($securityzone){ return api_keystore_checkkeyexists("SECURITYZONE", "name", $securityzone); }

// Check if the current securityzoneid exists

function api_security_zone_checkidexists($securityzoneid){

	if(!is_numeric($securityzoneid)) return api_error_raise("Sorry, that is not a valid zone id");

	if(api_keystore_get("SECURITYZONE", $securityzoneid, "name") !== FALSE) return true;
	else return false;

}

// Delete security zone plan

function api_security_zone_delete($securityzoneid){

	if(!api_security_zone_checkidexists($securityzoneid)) return api_error_raise("Sorry, that is not a valid zone id");

	api_keystore_purge("SECURITYZONE", $securityzoneid);

	return true;

}

// List security zones

function api_security_zone_listall($short = 0){

	$securityzones = api_keystore_getids("SECURITYZONE", "name", true);

	if($securityzones) asort($securityzones);

	return $securityzones;


}

/**
 * Check security zone status
 *
 * @param integer $zoneid
 * @param integer $groupid
 * @param boolean $return_false
 * @param integer $userid
 *
 * @return boolean|null
 */
function api_security_check($zoneid, $groupid = null, $return_false = null, $userid = null) {
	if ($userid === null) {
		$userid = $_SESSION['userid'];
	}

	// No user
	if (!is_numeric($userid)) {
		if ($return_false) {
			return false;
		} else {
			api_security_set_privilege_error_template("Sorry, you need to log in first.");
			exit;
		}
	}

	// Get user's security zones
	$zones = api_users_setting_getsingle($userid, "securityzones");

	if ($zones !== false) {
		$zones = unserialize($zones);
	}

	// Get user's groups
	$groups = api_security_groupaccess($userid);

	// Morpheus access is not implied by admin
	$admin_override = (
		$groups["isadmin"] &&
		!in_array(
			(int) $zoneid,
			[
				ZONE_MORPHEUS_ACCESS,
				ZONE_PLOTTER_ACCESS,
				ZONE_DIALPLANGEN_ACCESS
			]
		)
	);

	// If user isn't admin, and doesn't have the requisite zone
	if (!$admin_override
		&& (
			!is_array($zones) // doesn't have any zones
			|| !in_array($zoneid, $zones) // doesn't have THIS zone
		)
	) {
		if ($return_false) {
			return false;
		} else {
			api_security_set_privilege_error_template("Sorry, you don't have access to this function.");
			exit;
		}
	}

	// If user isn't admin, and doesn't have the requisite group membership
	if (is_numeric($groupid)
		&& (
			!$groups["isadmin"]
			&& !in_array($groupid, $groups["groups"])
		)
	) {
		if ($return_false) {
			return false;
		} else {
			api_security_set_privilege_error_template("Sorry, you don't have access to this specific resource.");
			exit;
		}
	} else {
		return true;
	}
}

function api_security_set_privilege_error_template($message) {
	api_templates_assign("title", "Privilege error");
	api_templates_notify("error", $message);
	api_templates_display("header.tpl");
	api_templates_display("securityerror.tpl");
	api_templates_display("footer.tpl");
}

function api_security_groupaccess($id){

	if(!api_users_checkidexists($id)) return api_error_raise("Sorry, that user doesn't exist");

	$groups = array("isadmin" => false, "groups" => array());

	$groupowner = api_users_setting_getsingle($id, "groupowner");

	if($groupowner == 2) $groups["isadmin"] = true;
	else {

		$a = api_users_setting_getsingle($id, "usergroups");

		if($a) $groups["groups"] = unserialize($a);

		$groups["groups"][] = $groupowner;

		$groups["groups"] = array_unique($groups["groups"]);

	}

	return $groups;

}

function api_security_isadmin($id, $exit_with_access_privilege_error = false){

	if(!api_users_checkidexists($id)) return api_error_raise("Sorry, that user doesn't exist");

	$groups = api_security_groupaccess($id);

	if (!isset($groups['isadmin']) || !$groups['isadmin']) {
		if (!$exit_with_access_privilege_error) {
			return $groups['isadmin'];
		}

		api_security_set_privilege_error_template("Sorry, you don't have access to this specific resource.");
		exit;
	}

	return $groups['isadmin'];
}

// Security zone settings

  // Add or update setting

function api_security_zone_setting_set($zoneid, $setting, $value) { return api_keystore_set("SECURITYZONE", $zoneid, $setting, $value); }

  // Delete setting

    // Single
function api_security_zone_setting_delete_single($zoneid, $setting) { return api_keystore_delete("SECURITYZONE", $zoneid, $setting); }

  // Get

    // Single

function api_security_zone_setting_getsingle($zoneid, $setting) { return api_keystore_get("SECURITYZONE", $zoneid, $setting); }

    // All

function api_security_zone_setting_getall($zoneid) { return api_keystore_getnamespace("SECURITYZONE", $zoneid); }

?>