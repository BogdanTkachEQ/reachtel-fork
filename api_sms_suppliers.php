<?php

// Service supplier

  // Add supplier

function api_sms_supplier_add($name){

	if((strlen($name) < 4) OR (strlen($name) > 30)) return api_error_raise("Sorry, the sms supplier name must be between 4 and 30 characters in length.");

	if(api_sms_supplier_checkexists($name)) return api_error_raise("Sorry, the sms supplier already exists.");

	$lastid = api_keystore_increment("SMSSUPPLIER", 0, "nextid");

	api_sms_supplier_setting_set($lastid, "name", $name);
	api_sms_supplier_setting_set($lastid, "status", "DISABLED");
	api_sms_supplier_setting_set($lastid, "smspersecond", 2);
	api_sms_supplier_setting_set($lastid, "counter", 0);
	api_sms_supplier_setting_set($lastid, "capabilities", serialize(array()));

	return $lastid;

}

  // Delete supplier

function api_sms_supplier_delete($id){

	if(!api_sms_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid supplier");

	api_keystore_purge("SMSSUPPLIER", $id);

	return true;

}

  // List all suppliers

function api_sms_supplier_listall($short = false, $activeonly = false){

	$suppliers = api_keystore_getentirenamespace("SMSSUPPLIER");

	$allsuppliers = array();

	if($suppliers) foreach($suppliers as $supplier => $keys) {
		if(!$activeonly OR ($activeonly AND ($keys["status"] == "ACTIVE"))){
			if($short) $allsuppliers[$supplier] = $keys["name"];
			else $allsuppliers[$supplier] = $keys;
		}
	}

	return $allsuppliers;

}

function api_sms_supplier_select($capabilities = null, $options = array()){
	if ($capabilities) {
		if (!is_array($capabilities)) {
			$capabilities = [$capabilities];
		}

		array_walk($capabilities, function (&$capability) {
			// Match all other mobile destinations to the "othermobile" category. This includes destinations that are "fixedlineormobile"
			$capability = (
				preg_match("/mobile/", $capability) &&
				!in_array($capability, ["aumobile", "nzmobile", "sgmobile", "gbmobile", "phmobile"])
			) ? 'othermobile' : $capability;
		});
	}

	$providers = api_sms_supplier_listall(false, true);

	if(!is_array($providers) OR (count($providers) == 0)) return false;

	$available = array();
	$ordered = array();

	uksort($providers, function() { return rand() > rand(); });

	$sort_by_capabilities = isset($options['sort_by_capabilities']) ? $options['sort_by_capabilities'] : [];

	foreach($providers as $providerid => $provider){

		if (isset($provider['capabilities']) && $provider['capabilities']) {
			$provider["capabilities"] = unserialize($provider["capabilities"]);

			if ($sort_by_capabilities) {
				$provider_capabilities_map[$providerid] = is_array($provider['capabilities']) ?
					$provider['capabilities'] :
					[$provider['capabilities']];
			}

			if (
				$capabilities != null &&
				is_array($provider['capabilities']) &&
				array_diff($capabilities, $provider['capabilities'])
			) {
				continue;
			}
		} else {
			api_error_raise("Capabilities for SMS supplier id={$providerid} are misconfigured");
			continue;
		}

		if(!api_restrictions_caps_sms_provider($providerid, $provider)) $available[$provider["priority"]][] = $providerid;
	}

	ksort($available);

	foreach($available as $priority => $providers) foreach($providers as $providerid) $ordered[] = $providerid;

	$providers_sorted = array_reverse($ordered);

	// Sorting by those that satisfies all capabilities given higher priority
	if ($sort_by_capabilities && isset($provider_capabilities_map)) {
		// Using usort() here would affect the order in which it was sorted by priority, when there are two providers
		// in the list close together that satisfies required capabilities.
		$providers_with_capabilities = [];
		$providers_without_capabilities = [];
		foreach ($providers_sorted as $providerid) {
			if (isset($provider_capabilities_map[$providerid]) && !array_diff($sort_by_capabilities, $provider_capabilities_map[$providerid])) {
				$providers_with_capabilities[] = $providerid;
			} else {
				$providers_without_capabilities[] = $providerid;
			}
		}

		$providers_sorted = array_merge($providers_with_capabilities, $providers_without_capabilities);
	}

	return $providers_sorted;
}

  // Check supplier name already exists

function api_sms_supplier_checkexists($name){ return api_keystore_checkkeyexists("SMSSUPPLIER", "name", $name); }

  // Check supplier id already exists

function api_sms_supplier_checkidexists($id){

	if(!is_numeric($id)) return false;

	if(api_keystore_get("SMSSUPPLIER", $id, "name") !== FALSE) return true;
	else return false;

}

// Check if name already exists

function api_sms_supplier_checknameexists($name) { return api_keystore_checkkeyexists("SMSSUPPLIER", "name", $name); }

// Supplier settings

  // Add or update setting

function api_sms_supplier_setting_set($id, $setting, $value){ return api_keystore_set("SMSSUPPLIER", $id, $setting, $value); }

  // Delete setting

    // Single
function api_sms_supplier_setting_delete_single($id, $setting){ return api_keystore_delete("SMSSUPPLIER", $id, $setting); }

    // All

function api_sms_supplier_setting_delete_all($id){ return api_keystore_purge("SMSSUPPLIER", $id); }

  // Get

    // Single

function api_sms_supplier_setting_getsingle($id, $setting){ return api_keystore_get("SMSSUPPLIER", $id, $setting); }

function api_sms_supplier_increment($id, $setting) { return api_keystore_increment("SMSSUPPLIER", $id, $setting); }

    // All settings

function api_sms_supplier_setting_getall($id){ return api_keystore_getnamespace("SMSSUPPLIER", $id); }


    // Multi

function api_sms_supplier_setting_get_multi_byitem($id, $items) { return api_keystore_get_multi_byitem("SMSSUPPLIER", $id, $items); }

function api_sms_supplier_tags_get($id, $tags = null){

	if(!api_sms_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid SMS supplier");

	return api_tags_get('SMSSUPPLIER', $id, $tags);

}

function api_sms_supplier_tags_get_all_details($id) {
	if(!api_sms_supplier_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid SMS supplier");
	}
	return api_tags_get_existing_tag_details('SMSSUPPLIER', $id, true);
}

function api_sms_supplier_tags_set($id, array $tags = [], array $encrypt_tags = []){

	if(!api_sms_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid SMS supplier");

	return api_tags_set('SMSSUPPLIER', $id, $tags, $encrypt_tags);

}

function api_sms_supplier_tags_delete($id, array $tags = []){

	if(!api_sms_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid SMS supplier");

	return api_tags_delete('SMSSUPPLIER', $id, $tags);

}

function api_sms_supplier_get_all_capabilities() {
	return [
		"aumobile" => "Australia - Mobile",
		"nzmobile" => "New Zealand - Mobile",
		"sgmobile" => "Singapore - Mobile",
		"gbmobile" => "Great Britain - Mobile",
		"phmobile" => "Philippines - Mobile",
		SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE => SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE_LABEL,
		"othermobile" => "All other countries - Mobile"
	];
}
