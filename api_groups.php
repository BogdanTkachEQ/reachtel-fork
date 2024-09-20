<?php

// Add group

function api_groups_add($name){

	if(!preg_match("/^([a-z0-9\-_ ]{3,35})$/i", $name)) return api_error_raise("Sorry, that is not a valid group name");

	if(api_groups_checknameexists($name)) return api_error_raise("Sorry, a group with the name '" . $name . "' already exists");

	$lastid = api_keystore_increment("GROUPS", 0, "nextid");

	api_groups_setting_set($lastid, "name", $name);
	api_groups_setting_set($lastid, "created", time());

	if(isset($_SESSION['userid']) AND api_users_checkidexists($_SESSION['userid'])){

		$usergroups = api_users_setting_getsingle($_SESSION['userid'], "usergroups");

		if($usergroups != "") $usergroups = unserialize($usergroups);
		else $usergroups = array();

		$usergroups[] = $lastid;

		if(is_array($usergroups)) api_users_setting_set($_SESSION['userid'], "usergroups", serialize($usergroups));

	}

	return $lastid;

}

// Check if group already exists

function api_groups_checkgroupexists($name) { return api_keystore_checkkeyexists("GROUPS", "name", $name); }

// Check if username already assigned

function api_groups_checknameexists($name){ return api_keystore_checkkeyexists("GROUPS", "name", $name); }

// Check if group id exists

function api_groups_checkidexists($groupid){

	if(!is_numeric($groupid)) return api_error_raise("Sorry, that is not a valid group id");

	if(api_keystore_get("GROUPS", $groupid, "name") !== FALSE) return true;
	else return false;
}

// Delete group

function api_groups_delete($groupid){

	if(!api_groups_checkidexists($groupid)) return api_error_raise("Sorry, that is not a valid group id");

	if(api_keystore_checkkeyexists("CAMPAIGNS", "groupowner", $groupid)) return api_error_raise("Sorry, cannot delete a group that is assigned to a campaign");

	if($groupid == 2) return api_error_raise("Sorry, you can't delete the admin group");

    // Remove group

	api_keystore_purge("GROUPS", $groupid);

	return true;

}


// List groups

function api_groups_listall(){

	$groups = api_keystore_getids("GROUPS", "name", true);

	if(empty($groups) OR !is_array($groups)) return array();

	natcasesort($groups);

	return $groups;

}

/**
 * @param $user_id
 * @return array
 */
function api_groups_listall_for_user($user_id) {
	$usergroups = api_users_setting_getsingle($user_id, "usergroups");
	$usergroups = $usergroups ? unserialize($usergroups) : [];
	$groups = api_keystore_getids("GROUPS", "name", true);

	$group_array = [];

	foreach ($usergroups as $groupid) {
		$group_array[$groupid] = $groups[$groupid];
	}

    $groupowner = api_users_setting_getsingle($user_id, "groupowner");
	$group_array[$groupowner] = $groups[$groupowner];

	return $group_array;
}

// Group settings

  // Add or update setting

function api_groups_setting_set($id, $setting, $value) { return api_keystore_set("GROUPS", $id, $setting, $value); }

  // Delete setting

    // Single

function api_groups_setting_delete_single($id, $setting) { return api_keystore_delete("GROUPS", $id, $setting); }

  // Get

    // Single

function api_groups_setting_getsingle($id, $setting) { return api_keystore_get("GROUPS", $id, $setting); }

    // Multi

function api_groups_setting_get_multi_byitem($id, $items) { return api_keystore_get_multi_byitem("GROUPS", $id, $items); }

    // All

function api_groups_setting_getall($id) { return api_keystore_getnamespace("GROUPS", $id); }

function api_groups_setting_increment($id, $setting) { return api_keystore_increment("GROUPS", $id, $setting); }

function api_groups_tags_get($id, $tags = null){

	if(!api_groups_checkidexists($id)) return api_error_raise("Sorry, that is not a valid group id");

	return api_tags_get('GROUPS', $id, $tags);

}

function api_groups_tags_set($id, array $tags = [], array $encrypt_tags = []){

	if(!api_groups_checkidexists($id)) return api_error_raise("Sorry, that is not a valid group id");

	return api_tags_set('GROUPS', $id, $tags, $encrypt_tags);

}

function api_groups_tags_delete($id, array $tags = []){

	if(!api_groups_checkidexists($id)) return api_error_raise("Sorry, that is not a valid group id");

	return api_tags_delete('GROUPS', $id, $tags);

}

function api_group_tags_get_all_details($id) {
	if(!api_groups_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid group id");
	}
	return api_tags_get_existing_tag_details('GROUPS', $id, true);
}

/**
 * Return all campaign ids for a specific group
 *
 * @param int $groupid
 */
function api_groups_get_all_campaignids($groupid, \MabeEnum\EnumSet $types = null) {
	$sql = "SELECT `id`
		FROM `key_store`
		WHERE `type` = 'CAMPAIGNS'
			AND `item` = 'groupowner'
			AND `value` = ? ";

	$params = [$groupid];
	if ($types && $types->count()){
		$sql .= " AND id IN (SELECT id FROM key_store WHERE type = 'CAMPAIGNS' AND item = 'type' AND value IN (" ;
		$sql .= rtrim(str_repeat("?,", $types->count()), ",");
		$sql .= "))";
		$params = array_merge($params, $types->getValues());
	}
	$rs = api_db_query_read($sql, $params);

	return array_map('current', $rs->GetArray());
}

/**
 * Return all dids for a group based on the did type
 * @param integer $groupid
 * @param string  $didtype
 * @return array
 */
function api_groups_get_all_dids($groupid, $didtype) {
	if (!in_array($didtype, [KEY_STORE_TYPE_SMS_DIDS, KEY_STORE_TYPE_VOICE_DIDS])) {
		return api_error_raise('Invalid did type received when fetching dids for user group');
	}

	$sql = "SELECT k3.`id` AS id, k3.`value` AS `name`, k4.`value` AS `use` FROM" .
		" `key_store` k1 JOIN `key_store` k2 ON (k1.`type`=? AND k1.`item`=? AND k1.`value` = k2.`id`" .
		" AND k2.`type`=? AND k2.`item`=?) JOIN `key_store` k3 ON (k3.`type`=? AND k3.`id`=k1.`id` AND k3.`item`=?)" .
		" LEFT JOIN `key_store` k4 ON (k4.`type`=? AND k4.`id`=k1.`id` AND k4.`item`=?) WHERE k2.`id`=?";
	$rs = api_db_query_read($sql, [$didtype, 'groupowner', KEY_STORE_TYPE_GROUPS, 'name', $didtype, 'name', $didtype, 'use', $groupid]);

	if (!$rs) {
		return api_error_raise('SQL error when fetching all sms dids for user group');
	}

	return $rs->GetAssoc();
}
