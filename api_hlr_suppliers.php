<?php

// Service supplier

  // Add supplier

function api_hlr_supplier_add($name){

	if((strlen($name) < 4) OR (strlen($name) > 30)) return api_error_raise("Sorry, the hlr supplier name must be between 4 and 30 characters in length.");

	if(api_hlr_supplier_checkexists($name)) return api_error_raise("Sorry, the hlr supplier already exists.");

	$id = api_keystore_increment("HLRSUPPLIER", 0, "nextid");

	api_hlr_supplier_setting_set($id, "name", $name);
	api_hlr_supplier_setting_set($id, "status", "DISABLED");
	api_hlr_supplier_setting_set($id, "hlrpersecond", 2);
	api_hlr_supplier_setting_set($id, "counter", 0);
	api_hlr_supplier_setting_set($id, "capabilities", serialize(array()));

	return $id;

}

  // Delete supplier

function api_hlr_supplier_delete($id){

	if(!api_hlr_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid supplier");

	api_keystore_purge("HLRSUPPLIER", $id);

	return true;

}

  // List all suppliers

function api_hlr_supplier_listall($short = false, $activeonly = false){

	$suppliers = api_keystore_getentirenamespace("HLRSUPPLIER");

	$allsuppliers = array();

	if($suppliers) foreach($suppliers as $supplier => $keys) {
		if(!$activeonly OR ($activeonly AND ($keys["status"] == "ACTIVE"))){
			if($short) $allsuppliers[$supplier] = $keys["name"];
			else $allsuppliers[$supplier] = $keys;
		}
	}

	return $allsuppliers;

}

function api_hlr_supplier_select($capabilities = null){

	$providers = api_hlr_supplier_listall(false, true);

	if(!is_array($providers) OR (count($providers) == 0)) return false;

	$available = array();
	$ordered = array();

	uksort($providers, function() { return rand() > rand(); });

	foreach($providers as $providerid => $provider){

		if(($capabilities != null) AND isset($provider["capabilities"])) {
			$provider["capabilities"] = unserialize($provider["capabilities"]);

			if(is_array($provider["capabilities"]) AND !in_array($capabilities, $provider["capabilities"])) continue;
		}

		if(!api_restrictions_caps_hlr_provider($providerid, $provider)) $available[$provider["priority"]][] = $providerid;
	}

	ksort($available);

	foreach($available as $priority => $providers) foreach($providers as $providerid) $ordered[] = $providerid;

	return array_reverse($ordered);

}

function api_hlr_process($msisdn){

	if(!is_numeric($msisdn)) return api_error_raise("Sorry, that is not a valid number");

	$destination = api_data_numberformat($msisdn);

	if(!is_array($destination)) return api_error_raise("Sorry, that isn't a valid number");

	$blockedmsisdns = array("61400000000");

	if(in_array($destination["destination"], $blockedmsisdns)) return array("msisdn" => $destination["destination"], "status" => "DISCONNECTED", "active" => false, "response" => "BLOCKED_LIST", "supplierid" => 1, "hlrcode" => 995);

	$i = 0;

	do{
		$providers = api_hlr_supplier_select($destination["type"]);

		$i++;

		if(!is_array($providers) OR (count($providers) == 0)) usleep(100000);
		else break;

	} while($i <= 5);

	if(!is_array($providers)) return api_error_raise("Sorry, no available HLR suppliers were found");

	foreach($providers as $provider){

		try {
			include_once(__DIR__ . "/lib/HLR/supplier_" . $provider . ".php");

			$function = "api_hlr_send_supplier_" . $provider;

			if(!is_callable($function)) continue;

		} catch (Exception $e){

			continue;

		}

		$result = $function($destination["destination"]);

		if($result) {

			if($result["status"] == "DISCONNECTED") api_restrictions_baddata_add("phone", $destination["destination"]);

			api_hlr_supplier_log($provider, $result["msisdn"], $result);

			api_hlr_supplier_setting_increment($provider, "counter");
			api_hlr_supplier_setting_set($provider, "lasthlr", microtime(true));

			return $result;

		}
	}

	return array("msisdn" => $destination["destination"], "status" => "INDETERMINATE", "active" => false, "response" => "ALL_SUPPLIERS_FAILED", "supplierid" => 1, "hlrcode" => 996);

}

function api_hlr_supplier_log($supplier, $msisdn, $response = array()){

	if(!is_numeric($supplier)) return api_error_raise("Sorry, cannot log that HLR supplier code");

	if(empty($response["response"])) $response["response"] = null;
	if(empty($response["carriercode"])) $response["carriercode"] = null;
	if(empty($response["supplierid"])) $response["supplierid"] = null;
	if(empty($response["status"])) $response["status"] = null;
	if(!isset($response["hlrcode"]) OR !is_numeric($response["hlrcode"])) $response["hlrcode"] = null;

	if(!empty($response["lookupid"])){

		$sql = "UPDATE `sms_lookups` SET `msisdn` = ?, `results` = ?, `status` = ?, `carrier` = ?, `hlrcode` = ? WHERE `lookupid` = ?";
		api_db_query_write($sql, array($msisdn, $response["response"], $response["status"], $response["carriercode"], $response["hlrcode"], $response["lookupid"]));

		$lookupid = $response["lookupid"];

	} else {

		$sql = "INSERT INTO `sms_lookups` (`msisdn`, `supplier`, `supplierid`, `results`, `status`, `carrier`, `hlrcode`) VALUES (?, ?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array($msisdn, $supplier, $response["supplierid"], $response["response"], $response["status"], $response["carriercode"], $response["hlrcode"]));

		$lookupid = api_db_lastid();
	}

	return $lookupid;

}

function api_hlr_supplier_msctomccmnc($msc){

	if(!is_numeric($msc)) return false;

	$mscprefixed = array(   "61418" => "50501",
		"61408" => "50501",
		"61411" => "50502",
		"61414" => "50503",
		"61415" => "50503",
		"61430" => "50503",
		"61414" => "50506",
		"61415" => "50506",
		"61418" => "50519",
		"61408" => "50519",
		"6421"  => "53001",
		"6427744" => "53005",
		"6427745" => "53005",
		"6422" => "53024");

	foreach($mscprefixed as $prefix => $mccmnc) if(preg_match("/^" . $prefix . "/", $msc)) return $mccmnc;

	api_misc_audit("HLR_ERROR", "UNKNOWN_MSC; MSC:" . $msc);

	return false;
}

function api_hlr_supplier_mccmnctoname($mccmnc){

	if(!is_numeric($mccmnc)) return false;

	$carriers = array("50501" => "Telstra Corporation Limited",
		"50502" => "Optus Mobile Pty Ltd",
		"50503" => "Vodafone Network Pty Ltd",
		"50506" => "Hutchison 3G Australia Pty Limited",
		"50588" => "Localstar Holding Pty. Ltd.",
		"53001" => "vodafone NZ",
		"53002" => "Telecom New Zealand",
		"53003" => "Woosh Wireless",
		"53005" => "Telecom New Zealand",
		"53006" => "Telecom New Zealand",
		"53024" => "2degrees",
		"52501" => "SingTel (Singapore Telecom Mobile)",
		"52502" => "SingTel (Singapore Telecom Mobile)",
		"52503" => "M1 (M1 Limited)",
		"52504" => "MobileOne",
		"52505" => "StarHub Mobile",
		"20404" => "Vodafone Libertel B.V.",
		"23455" => "Telecom New Zealand",
		"23401" => "Mapesbury C. Ltd",
		"23402" => "O2 Ltd.",
		"23403" => "Airtel/Vodafone",
		"23407" => "Cable and Wireless",
		"23408" => "OnePhone",
		"23409" => "Tismi",
		"23410" => "O2 Ltd.",
		"23411" => "O2 Ltd.",
		"23412" => "Railtrack Plc",
		"23414" => "HaySystems",
		"23415" => "Vodafone",
		"23416" => "Opal Telecom",
		"23417" => "FlexTel",
		"23418" => "Cloud9/wire9 Tel.",
		"23419" => "PMN/Teleware",
		"23420" => "Hutchinson 3G",
		"23422" => "Routotelecom",
		"23423" => "Vectofone Mobile Wifi",
		"23424" => "Stour Marine",
		"23425" => "Truphone",
		"23426" => "Lycamobile",
		"23427" => "Vodafone",
		"23428" => "Marthon Telecom",
		"23430" => "Everyth. Ev.wh./T-Mobile",
		"23431" => "Everyth. Ev.wh./T-Mobile",
		"23432" => "Everyth. Ev.wh./T-Mobile",
		"23433" => "Everyth. Ev.wh./Orange",
		"23434" => "Everyth. Ev.wh./Orange",
		"23435" => "JSC Ingenicum",
		"23436" => "Cable and Wireless Isle of Man",
		"23437" => "Synectiv Ltd.",
		"23450" => "Jersey Telecom",
		"23451" => "Jersey Telecom",
		"23458" => "Manx Telecom",
		"23475" => "Inquam Telecom Ltd",
		"23476" => "BT Group",
		"23477" => "BT Group",
		"23478" => "Wave Telecom Ltd",
		"23491" => "Vodafone",
		"23492" => "Cable and Wireless",
		"23494" => "Hutchinson 3G");

	if(!empty($carriers[$mccmnc])) return $carriers[$mccmnc];

	api_misc_audit("HLR_ERROR", "UNKNOWN_MCCMNC; MCCMNC:" . $mccmnc);

	return "Unknown";
}

function api_hlr_supplier_cactomccmnc($cac){

	if(!is_numeric($cac)) return false;

	$cacs = array("1411" => "50501",
		"1414" => "50514",
		"1415" => "50503",
		"1431" => "50506",
		"1450" => "50588",
		"1456" => "50502");

	if(!empty($cacs[$cac])) return $cacs[$cac];

	api_misc_audit("HLR_ERROR", "UNKNOWN_CAC; CAC:" . $cac);

	return false;
}

function api_hlr_supplier_cactoname($cac){

	if(!is_numeric($cac)) return false;

	$cacs = array("1411" => "Telstra Corporation Limited",
		"1412" => "Chime Communications Pty Ltd",
		"1414" => "AAPT Limited",
		"1415" => "Vodafone Australia Limited",
		"1422" => "Premier Technologies Pty Ltd",
		"1423" => "IPTEL Pty Limited",
		"1428" => "Verizon Australia Pty Limited",
		"1431" => "Vodafone Hutchison Australia Pty Limited",
		"1434" => "Pacific Gateway Exchange (Australia) Pty Ltd",
		"1440" => "AMPM Telecom Pty Ltd",
		"1441" => "Rsl Com Australia Pty Limited",
		"1447" => "Transact Capital Communications Pty Ltd",
		"1450" => "Pivotel Satellite Pty Limited",
		"1456" => "Singtel Optus Pty Limited",
		"1464" => "Silk Telecom (WA) Pty Ltd",
		"1466" => "Primus Telecommunications Pty Limited",
		"1468" => "Tel.Pacific Pty Limited",
		"1469" => "Lycamobile Pty Ltd",
		"1474" => "Powertel Limited",
		"1477" => "Vocus Pty Ltd",
		"1488" => "Symbio Networks Pty Ltd",
		"1499" => "VIRTUTEL PTY LTD");

	if(!empty($cacs[$cac])) return $cacs[$cac];

	api_misc_audit("HLR_ERROR", "UNKNOWN_CAC; CAC:" . $cac);

	return false;
}

function api_hlr_supplier_hlrcodetodesc($code) {

	if(!is_numeric($code)) return false;

	switch($code) {
		case 0:
			return "NO_ERROR";
			break;
		case 1:
			return "UNKNOWN_SUBSCRIBER";
			break;
		case 6:
			return "ABSENT_SUBSCRIBER";
			break;
		case 11:
			return "TELESERVICE_NOT_PROVISIONED";
			break;
		case 13:
			return "CALL_BARRED";
			break;
		case 21:
			return "FACILITY_NOT_SUPPORTED";
			break;
		case 995:
			return "BLOCKED_MSISDN";
			break;
		case 996:
			return "NO_RESPONSE";
			break;
		case 997:
			return "UNSPECIFIED_ERROR";
			break;
		case 998:
			return "UNSUPPORTED_PREFIX";
			break;
		case 999:
			return "INVALID_NUMBER";
			break;
		default:
			return "UNSPECIFIED_RESPONSE";
	}
}

  // Check supplier name already exists

function api_hlr_supplier_checkexists($name){ return api_keystore_checkkeyexists("HLRSUPPLIER", "name", $name); }

  // Check supplier id already exists

function api_hlr_supplier_checkidexists($id){

	if(!is_numeric($id)) return false;

	if(api_keystore_get("HLRSUPPLIER", $id, "name") !== FALSE) return true;
	else return false;

}

// Check if name already exists

function api_hlr_supplier_checknameexists($name) { return api_keystore_checkkeyexists("HLRSUPPLIER", "name", $name); }

// Supplier settings

  // Add or update setting

function api_hlr_supplier_setting_set($id, $setting, $value){ return api_keystore_set("HLRSUPPLIER", $id, $setting, $value); }

function api_hlr_supplier_setting_increment($id, $setting){ return api_keystore_increment("HLRSUPPLIER", $id, $setting); }

  // Delete setting

    // Single
function api_hlr_supplier_setting_delete_single($id, $setting){ return api_keystore_delete("HLRSUPPLIER", $id, $setting); }

    // All

function api_hlr_supplier_setting_delete_all($id){ return api_keystore_purge("HLRSUPPLIER", $id); }

  // Get

	// Single

function api_hlr_supplier_setting_getsingle($id, $setting){ return api_keystore_get("HLRSUPPLIER", $id, $setting); }

	// All settings

function api_hlr_supplier_setting_getall($id){ return api_keystore_getnamespace("HLRSUPPLIER", $id); }


	// Multi

function api_hlr_supplier_setting_get_multi_byitem($id, $items) { return api_keystore_get_multi_byitem("HLRSUPPLIER", $id, $items); }

function api_hlr_supplier_tags_get($id, $tags = null){

	if(!api_hlr_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid supplier id");

	return api_tags_get('HLRSUPPLIER', $id, $tags);

}

function api_hlr_supplier_tags_set($id, array $tags = [], array $encrypt_tags = []){

	if(!api_hlr_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid supplier id");

	return api_tags_set('HLRSUPPLIER', $id, $tags, $encrypt_tags);

}

function api_hlr_supplier_tags_delete($id, array $tags = []){

	if(!api_hlr_supplier_checkidexists($id)) return api_error_raise("Sorry, that is not a valid supplier id");

	return api_tags_delete('HLRSUPPLIER', $id, $tags);

}

function api_hlr_supplier_tags_get_all_details($id) {
	if(!api_hlr_supplier_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid supplier id");
	}
	return api_tags_get_existing_tag_details('HLRSUPPLIER', $id, true);
}