<?php

function api_system_setting_load($id){

	if(!is_numeric($id)) return api_error_raise("Sorry, that is not a valid application config ID");

	$sql = "SELECT `item`, `value` FROM `key_store` WHERE `type` = ? AND `id` IN (?, ?) ORDER BY `id` ASC";
	$rs = api_db_query_write($sql, array("SETTINGS", 0, $id));

	if($rs AND ($rs->RecordCount() > 0)){
		
		$items = $rs->GetAssoc();

		foreach($items as $item => $value) if(!defined($item)) define($item, $value);

	} else {

		api_error_raise("Count not load application config.");
		exit;

	}

}

// System settings

  // Add or update setting

function api_system_setting_set($setting, $value) { return api_keystore_set("SYSTEM", 0, $setting, $value); }

  // Delete setting

    // Single

function api_system_setting_delete_single($setting) { return api_keystore_delete("SYSTEM", 0, $setting); }

    // All

function api_system_setting_delete_all() { return api_keystore_purge("SYSTEM", 0); }

  // Get

    // Single

function api_system_setting_getsingle($setting) { return api_keystore_get("SYSTEM", 0, $setting); }

    // All settings

function api_system_setting_getall() { return api_keystore_getnamespace("SYSTEM", 0); }

function api_system_tags_get($tags = null){
	return api_tags_get('SYSTEM', 0, $tags);
}

function api_system_tags_set(array $tags = [], array $encrypt_tags = []){

	if(!is_array($tags)) return api_error_raise("Sorry, that is not a valid tag");

	return api_tags_set('SYSTEM', 0, $tags, $encrypt_tags);

}

function api_system_tags_get_all_details() {
    return api_tags_get_existing_tag_details('SYSTEM', 0, true);
}

function api_system_tags_delete(array $tags = []){

	return api_tags_delete('SYSTEM', 0, $tags);

}

?>