<?php

// Generate call

function api_voice_generate($call, $settings = false){

	global $statsd;

	if(empty($call["destination"]) OR (!is_numeric($call["campaignid"]))) return false;

	if($settings == false) $settings = api_campaigns_setting_getall($call["campaignid"]);

	$destination = api_data_numberformat($call["destination"], $settings["region"]);

	if(!is_array($destination)) return false;
	else {
		$call["e164"] = $destination["destination"];
		$call["destination"] = $destination["fnn"];
	}

	if(!is_numeric($settings["voicesupplier"]) OR ($settings["voicesupplier"] == 0)) {
		if($settings["type"] == "wash") $calltype = "wash" . $destination["country"];
		elseif(preg_match("/^ReachTEL-NumberWash-Base/", $settings["name"])) $calltype = "wash" . $destination["country"];
		else $calltype = $destination["type"];

		for($i = 0; $i < 10; $i++) {

			$settings["voicesupplier"] = api_voice_supplier_assign($calltype);

			if(is_numeric($settings["voicesupplier"])) break;
			else usleep(100000 + ($i * 20000));

		};

		if(!is_numeric($settings["voicesupplier"])) {
			$statsd->increment("morpheus.voice.errors.nosupplier");
			return false;
		}

	}

	$eventid = api_misc_uniqueid();

	if(!empty($settings["withholdcid"]) AND ($settings["withholdcid"] == "on")) $call["withholdcid"] = 1;
	else $call["withholdcid"] = 0;

	$multi = api_voice_supplier_setting_get_multi_byitem($settings["voicesupplier"], array("type", "name", "voiceserver"));

    $spooler = 'ReachTEL-CallSpooler';
    $override_spooler = api_campaigns_tags_get($call['campaignid'], 'override_spooler');

    if ($override_spooler) {
        $spooler = api_dialplans_setting_getsingle($override_spooler, 'name');

        if (!$spooler) {
            return api_error_raise(
                sprintf("Dial plan for override spooler not found for campaign id %s.", $call['campaignid'])
            );
        }
    }

	$spool_call_http = array("action" => "originate",
		"channel" => "Local/" . $call["e164"] . "@$spooler/n",
		"callerid" => api_voice_dids_setting_getsingle($settings["voicedid"], "name"),
		"exten" => "s",
		"account" => $call["targetid"],
		"context" => api_dialplans_setting_getsingle($settings["dialplan"], "name"),
		"priority" => 1,
		"timeout" => $settings["ringtime"] * 1000,
		"async" => true,
		"actionid" => $eventid);

	$spool_call_http["variables"] = array("eventid" => $eventid,
		"targetid" => $call["targetid"],
		"campaignid" => $call["campaignid"],
		"type" => $settings["type"],
		"priority" => $call["priority"],
		"retrylimit" => $settings["retrylimit"],
		"ringoutlimit" => $settings["ringoutlimit"],
		"redialtimeout" => $settings["redialtimeout"],
		"targetkey" => $call["targetkey"],
		"retries" => $call["reattempts"],
		"ringouts" => $call["ringouts"],
		"errors" => $call["errors"],
		"status" => $call["status"],
		"agiserver" => api_voice_servers_setting_getsingle($multi["voiceserver"], "agiserver"),
		"cid" => $spool_call_http["callerid"],
		"destination" => $call["destination"],
		"e164" => $call["e164"],
		"method" => $multi["type"] . "/" . $multi["name"],
		"provider" => $settings["voicesupplier"],
		"ringtime" => $settings["ringtime"],
		"dncdest" => $settings["donotcontactdestination"],
		"withholdcid" => $call["withholdcid"]);

	$extravariables = array("extravariable1", "extravariable2", "extravariable3", "extravariable4", "extravariable5");

	foreach($extravariables as $key){
		if(!empty($settings[$key])) $spool_call_http["variables"][$key] = $settings[$key];
		else $spool_call_http["variables"][$key] = "";
	}

	foreach(api_data_merge_get_all($call["campaignid"], $call["targetkey"]) as $element => $value) $spool_call_http["variables"]["ed" . $element] = $value;

	$result = api_voice_servers_send_http($multi["voiceserver"], $spool_call_http);

	if(preg_match("/Success/", $result)) {

		api_data_callresult_add($call["campaignid"], $eventid, $call["targetid"], "GENERATED");

		api_restrictions_channels_provider_madecall($settings["voicesupplier"], $call["targetid"]);

		$statsd->increment("morpheus." . ($settings['type'] === 'wash' ? 'wash' : 'voice') . ".calls." . $multi["name"]);

		api_campaigns_update_lastsend($call['campaignid']);
		return $eventid;

	} else {
		$statsd->increment("morpheus.voice.errors.sendfailed");
		return api_error_raise("Call attempt failed - " . $multi["name"] . " - " . $result);
	}
}



// DID

  // Add range or DID

function api_voice_dids_add($start, $end = null){

	if(!is_numeric($start)) return api_error_raise("Sorry, voice DIDs must be numeric");

	if(preg_match("/^0/", $start)) return api_error_raise("Sorry, voice DIDs must be supplied in e164 format");

	if(!empty($end)) {

		if(!is_numeric($end)) return api_error_raise("Sorry, voice DIDs must be numeric");

		if(preg_match("/^0/", $end)) return api_error_raise("Sorry, voice DIDs must be supplied in e164 format");

		if($start >= $end) return api_error_raise("Sorry, the start DID must be less than the end DID");

		if(($end - $start) > 199) return api_error_raise("Sorry, we can only add up to 200 numbers in one batch");

		// First, lets check that each number is valid
		for($number = $start; $number <= $end; $number++) if(api_data_numberformat($number) == false) return api_error_raise("Sorry, that is not a valid voice DID");

		// Then, add the number
		for($number = $start; $number <= $end; $number++) api_voice_dids_add($number);

		return true;

	} else {

		if(api_voice_dids_checkexists($start)) return true;
		elseif(api_data_numberformat($start)) {

			$id = api_keystore_increment("DIDS", 0, "nextid");

			api_voice_dids_setting_set($id, "name", $start);
			api_voice_dids_setting_set($id, "use", "Spare");
			api_voice_dids_setting_set($id, "groupowner", 2);

			return $id;

		} else return api_error_raise("Sorry, that is not a valid voice DID");

	}

}

  // Delete range or DID by did

function api_voice_dids_delete_bydid($start, $end = null){

	if(preg_match("/^0/", $start)) return api_error_raise("Sorry, voice DIDs must be supplied in e164 format");

	if(!empty($end)){

		if(!is_numeric($end)) return api_error_raise("Sorry, voice DIDs must be numeric");

		if(preg_match("/^0/", $end)) return api_error_raise("Sorry, voice DIDs must be supplied in e164 format");

		if($start >= $end) return api_error_raise("Sorry, the start DID must be less than the end DID");

		if(($end - $start) > 199) return api_error_raise("Sorry, we can only delete up to 200 numbers in one batch");

		for($number = $start; $number <= $end; $number++){

			$didid = api_keystore_checkexists("DIDS", "name", $number);

			if(is_numeric($didid)) {

				$result = api_voice_dids_delete_bydidid($didid);

				if(!$result) return $result;
			}

		}

	} else {

		$didid = api_voice_dids_checkexists($start);

		if(is_numeric($didid)) return api_voice_dids_delete_bydidid($didid);
		else return api_error_raise("Sorry, that is not a valid telephone number");

	}

	return true;

}

  // Delete by didid

function api_voice_dids_delete_bydidid($didid){

	if(!api_voice_dids_checkidexists($didid)) return api_error_raise("Sorry, that voice DID doesn't exist");

	if(api_keystore_checkkeyexists("CAMPAIGNS", "voicedid", $didid)) return api_error_raise("Sorry, cannot delete a DID that is assigned to a campaign");

	return api_keystore_purge("DIDS", $didid);

}

  // List DIDs

function api_voice_dids_listall($long = 0){

	$names = api_keystore_getids("DIDS", "name", true);

	if(empty($names) OR !is_array($names)) return array();

	natcasesort($names);

	if(!$long) return $names;

	$use = api_keystore_getids("DIDS", "use", true);

	$dids = array();

	foreach($names as $id => $name) {

		$dids[$id]["name"] = $name;

		if(isset($use[$id])) $dids[$id]["use"] = $use[$id];
		else $dids[$id]["use"] = "";
	}

	return api_misc_natcasesortbykey($dids, "name");

}



    // Assigned

    // Unassigned

  // List DID assigns

  // Check if DID exists

function api_voice_dids_checkexists($did){ return api_keystore_checkkeyexists("DIDS", "name", $did); }

// Check if the current didid exists

function api_voice_dids_checkidexists($didid){

	if(!is_numeric($didid)) return false;

	if(api_keystore_get("DIDS", $didid, "name") !== FALSE) return true;
	else return false;

}

function api_voice_dids_nametoid($name){

	$id = api_voice_dids_checkexists($name);

	if(is_numeric($id)) return $id;
	else return false;

}


  // Did settings

    // Add or update setting

function api_voice_dids_setting_set($didid, $setting, $value){ return api_keystore_set("DIDS", $didid, $setting, $value); }

    // Delete setting

      // Single
function api_voice_dids_setting_delete_single($didid, $setting){ return api_keystore_delete("DIDS", $didid, $setting); }

      // All

function api_voice_dids_setting_delete_all($didid){ return api_keystore_purge("DIDS", $didid); }

    // Get

      // Single

function api_voice_dids_setting_getsingle($didid, $setting){ return api_keystore_get("DIDS", $didid, $setting); }

      // All settings

function api_voice_dids_setting_getall($didid){ return api_keystore_getnamespace("DIDS", $didid); }


// Service supplier

  // Add supplier

function api_voice_supplier_add($supplier, $type){

	if((strlen($supplier) < 4) OR (strlen($supplier) > 30)) return false;

	if(($type != "IAX2") AND ($type != "SIP")) return false;

	if(api_voice_supplier_checkexists($supplier)) return false;

	$id = api_keystore_increment("VOICESUPPLIER", 0, "nextid");

	api_voice_supplier_setting_set($id, "name", $supplier);
	api_voice_supplier_setting_set($id, "type", $type);
	api_voice_supplier_setting_set($id, "status", "DISABLED");
	api_voice_supplier_setting_set($id, "callspersecond", 2);
	api_voice_supplier_setting_set($id, "maxchannels", 10);
	api_voice_supplier_setting_set($id, "priority", 5);
	api_voice_supplier_setting_set($id, "capabilities", serialize(array()));
    api_voice_supplier_setting_set($id, VOICE_SUPPLIER_SETTING_LASTCALL, 0);

	return $id;

}

  // Delete supplier

function api_voice_supplier_delete($supplierid){

	if(!api_voice_supplier_checkidexists($supplierid)) return api_error_raise("Sorry, that is not a valid supplier");

	if($campaignid = api_keystore_checkkeyexists("CAMPAIGNS", "voicesupplier", $supplierid)) return api_error_raise("Sorry, cannot delete a voice provider that is assigned to a campaign (e.g. " . api_campaigns_setting_getsingle($campaignid, "name") . ")");

	$type = api_voice_supplier_setting_getsingle($supplierid, "type");

	if($type == "SIP") $prefix = SIP_LOCATION;
	else $prefix = IAX_LOCATION;

	$voiceserver = api_voice_servers_setting_getsingle(api_voice_supplier_setting_getsingle($supplierid, "voiceserver"), "name");

	$filename = SAVE_LOCATION . $prefix . "/" . $voiceserver . "/autodialer-" . api_voice_supplier_setting_getsingle($supplierid, "name") . ".conf";

	foreach(api_voice_servers_listall_active() as $serverid => $name) api_queue_add("filesync", array("paths" => array($type), "servers" => array($serverid => $name)));

	api_keystore_purge("VOICESUPPLIER", $supplierid);

	@unlink($filename);

	return true;

}

  // List all voice suppliers

function api_voice_supplier_listall($short = false, $activeonly = false){

	$suppliers = api_keystore_getentirenamespace("VOICESUPPLIER");

	$allsuppliers = array();

	if($suppliers) foreach($suppliers as $supplier => $keys) {
		if(!$activeonly OR ($activeonly AND ($keys["status"] == "ACTIVE"))){
			if($short) $allsuppliers[$supplier] = $keys["name"];
			else $allsuppliers[$supplier] = $keys;
		}
	}

	return $allsuppliers;

}

function api_voice_supplier_assign($capabilities = null) {
    // We don't need 100% ACID compliant numbers - an approximation is fine. Use a dirty read for the next statement.
    $sql = "SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED";
    $rs = api_db_query_read($sql);

    $sql = "SELECT `providerid`, COUNT(`providerid`) as `count` FROM `provider_map` GROUP BY `providerid`";
    $rs = api_db_query_read($sql);

    if ($rs && ($rs->RecordCount() > 0)) {
        $providerchannels = $rs->GetAssoc();
    } else {
        $providerchannels = array();
    }

    $providers = api_voice_supplier_listall(false, true);

    if (!is_array($providers) || (count($providers) == 0)) {
        return false;
    }

    uksort($providers, function() { return rand() > rand(); });


    foreach ($providers as $providerid => $provider) {
        /*

                This is where we gracefully handle failing suppliers. Check if we have a value called "disableuntil"
                which contains a timestamp of when we should not send calls to this supplier until such time has
                passed.

         */
        if (isset($provider["disableuntil"]) && ($provider["disableuntil"] > time())) {
            unset($providers[$providerid]);
            continue;
        }

        if (($capabilities != null) AND isset($provider["capabilities"])) {
            $provider["capabilities"] = unserialize($provider["capabilities"]);

            if (is_array($provider["capabilities"]) && !in_array($capabilities, $provider["capabilities"])) {
                unset($providers[$providerid]);
                continue;
            }
        }

        // If the server is disabled, we cannot use this supplier
        if (api_voice_servers_setting_getsingle($provider['voiceserver'], "status") != "active") {
            unset($providers[$providerid]);
            continue;
        }

        // If a SITE_IDENTIFIER is set, only return servers that match this identifier
        if (defined('SITE_IDENTIFIER')) {
            // Only use servers that match our voice server site prefix
            $siteidentifier = trim(api_voice_servers_setting_getsingle($provider['voiceserver'], "siteidentifier"));

            if(empty($siteidentifier) || ($siteidentifier != SITE_IDENTIFIER)) {
                unset($providers[$providerid]);
                continue;
            }
        }

        if ((isset($providerchannels[$providerid]) && $providerchannels[$providerid] >= $provider["maxchannels"]) || api_restrictions_caps_provider($providerid, $provider)) {
            unset($providers[$providerid]);
            continue;
        }
    }

    // Sort by priority
    uasort($providers, function($a, $b) {
        if ($a['priority'] === $b['priority']) {
            return 0;
        }

        return ($a['priority'] > $b['priority']) ? -1 : 1;
    });

    foreach ($providers as $providerid => $provider) {
        // This is required to make sure that no other processes have started using the supplier and is reserved.
        if (
            api_voice_supplier_setting_cas(
                $providerid, 'lastcall', $provider['lastcall'], microtime(true)
            ) !== false
        ) {
            return $providerid;
        }
    }

    return false;
}

function api_voice_supplier_channels($providerid){

	$sql = "SELECT COUNT(`providerid`) as `count` FROM `provider_map` WHERE `providerid` = ?";
	$rs = api_db_query_read($sql, array($providerid));

	if($rs AND ($rs->RecordCount() > 0)) return $rs->Fields("count");
	else return 0;

}

function api_voice_supplier_deletecall($targetid){

	if(!is_numeric($targetid)) return false;

	$sql = "DELETE FROM `provider_map` WHERE `targetid` = ?";
	$rs = api_db_query_write($sql, array($targetid));

	if($rs) return true;
	else return false;

}

  // Check supplier name already exists

function api_voice_supplier_checkexists($supplier){ return api_keystore_checkkeyexists("VOICESUPPLIER", "name", $supplier); }

  // Check supplier id already exists

function api_voice_supplier_checkidexists($supplierid){

	if(!is_numeric($supplierid)) return false;

	if(api_keystore_get("VOICESUPPLIER", $supplierid, "name") !== FALSE) return true;
	else return false;

}

// Check if name already exists

function api_voice_supplier_checknameexists($name) { return api_keystore_checkkeyexists("VOICESUPPLIER", "name", $name); }

// Supplier settings

  // Add or update setting

function api_voice_supplier_setting_set($supplierid, $setting, $value){ return api_keystore_set("VOICESUPPLIER", $supplierid, $setting, $value); }

function api_voice_supplier_setting_cas($supplierid, $setting, $check, $value) {
    return api_keystore_cas(KEYSTORE_TYPE_VOICESUPPLIER, $supplierid, $setting, $check, $value);
}

  // Delete setting

    // Single
function api_voice_supplier_setting_delete_single($supplierid, $setting){ return api_keystore_delete("VOICESUPPLIER", $supplierid, $setting); }

    // All

function api_voice_supplier_setting_delete_all($supplierid){ return api_keystore_purge("VOICESUPPLIER", $supplierid); }

  // Get

    // Single

function api_voice_supplier_setting_getsingle($supplierid, $setting){ return api_keystore_get("VOICESUPPLIER", $supplierid, $setting); }

    // All settings

function api_voice_supplier_setting_getall($supplierid){ return api_keystore_getnamespace("VOICESUPPLIER", $supplierid); }


    // Multi

function api_voice_supplier_setting_get_multi_byitem($id, $items) { return api_keystore_get_multi_byitem("VOICESUPPLIER", $id, $items); }



  // Check supplier status

function api_voice_supplier_status($supplierid){

	if(!api_voice_supplier_checkidexists($supplierid)) return false;

	$multi = api_voice_supplier_setting_get_multi_byitem($supplierid, array("type", "name", "voiceserver"));

	$result = api_voice_servers_send_http($multi["voiceserver"], array("action" => "command", "command" => $multi["type"] . " show peer " . $multi["name"]));

	if(preg_match("/Status([ ]+): OK/i", $result)) {
		return true;
	} else {
		api_misc_audit("VOICE_SUPPLIER_OUTAGE", "VoiceSupplier=" . $supplierid . "; Response=" . serialize($result));
		return false;
	}
}

function api_voice_channels_quality(){

	$allServers = api_voice_servers_listall();

	foreach($allServers as $serverid => $servername) $servers[$serverid]["name"] = $servername;

	$allPeers = api_voice_supplier_listall();

	foreach($allPeers as $supplierid => $supplier) $servers[$supplier["voiceserver"]]["voicesuppliers"][$supplier["host"]] = $supplier["name"];

	$calls = array();
	$peers = array();

	if(is_array($allServers))
	foreach($allServers as $key => $value){

            // Suck in the call list
		if(api_voice_servers_setting_getsingle($key, "status") == "active") $results = api_voice_servers_send_http($key, array("action" => "command", "command" => "sip show channelstats"));
		else continue;

		$lines = explode("\n", $results);

		foreach($lines as $line) {

			if(preg_match("/^(\S+)[ ]+(\S+)[ ]+(\S*)[ ]+(\S+)[ ]+(\S+)[ ]+\([ ](\S+)\)[ ]+(\S+)[ ]+(\S+)[ ]+(\S+)[ ]+\( (\S+) (\S+)$/", $line, $matches)){

				if(!empty($matches[3])) $duration = (substr($matches[3], 0, 2) * 3600) + (substr($matches[3], 3, 2) * 60) + substr($matches[3], 6, 2);
				else $duration = null;

			// Sometimes Asterisk says we've lost more packets than we've sent. Ignore this and move on.
				if($matches[5] > $matches[4]) continue;
				if($matches[9] > $matches[8]) continue;

				$calls[] = array("peer" => $matches[1], "serverid" => $key, "callid" => $matches[2], "duration" => $duration, "packets" => array("recv" => (int)$matches[4], "recvlost" => (int)$matches[5], "sent" => (int)$matches[8], "sentlost" => (int)$matches[9]));

				if(isset($peers[$matches[1]])){

					$peers[$matches[1]]["calls"]++;
					$peers[$matches[1]]["packets"]["recv"] += (int)$matches[4];
					$peers[$matches[1]]["packets"]["recvlost"] += (int)$matches[5];
					$peers[$matches[1]]["packets"]["sent"] += (int)$matches[8];
					$peers[$matches[1]]["packets"]["sentlost"] += (int)$matches[9];

				} else {

					$peers[$matches[1]]["calls"] = 1;
					$peers[$matches[1]]["packets"]["recv"] = (int)$matches[4];
					$peers[$matches[1]]["packets"]["recvlost"] = (int)$matches[5];
					$peers[$matches[1]]["packets"]["sent"] = (int)$matches[8];
					$peers[$matches[1]]["packets"]["sentlost"] = (int)$matches[9];
					if(!empty($servers[$key]["voicesuppliers"][$matches[1]])) $peers[$matches[1]]["name"] = $servers[$key]["voicesuppliers"][$matches[1]];
					else $peers[$matches[1]]["name"] = "Unknown";

				}

                    } // If preg_match
            } // Foreach line
    } // Foreach server

    foreach($peers as $peer => $data){

    	if($data["packets"]["recv"] > 0) $data["packets"]["recvlostpercent"] = sprintf("%01.2f", ($data["packets"]["recvlost"] / $data["packets"]["recv"]) * 100);
    	else $data["packets"]["recvlostpercent"] = 0;

    	if($data["packets"]["sent"] > 0) $data["packets"]["sentlostpercent"] = sprintf("%01.2f", ($data["packets"]["sentlost"] / $data["packets"]["sent"]) * 100);
    	else $data["packets"]["sentlostpercent"] = 0;

    	if(($data["packets"]["recvlostpercent"] > SIPQOS_ALERTPERCENT) OR ($data["packets"]["sentlostpercent"] > SIPQOS_ALERTPERCENT)) {

    		api_misc_audit("SIP_QOS_ERROR", "Supplier=" . $data["name"] . "; Peer=" . $peer . "; Calls=" . $data["calls"] . "; Recv=" . $data["packets"]["recv"] . "; Lost=" . $data["packets"]["recvlost"] . "; Percent=" . $data["packets"]["recvlostpercent"] . "%; Sent=" . $data["packets"]["sent"] . "; Lost=" . $data["packets"]["sentlost"] . "; Percent=" . $data["packets"]["sentlostpercent"] . "%");
    	}
    }

    return $peers;

}
