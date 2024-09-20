<?php

// Add voice server

function api_voice_servers_add($server){

	if(api_voice_servers_checknameexists($server)) return api_error_raise("Sorry, that server name already exists.");

	if(!preg_match("/^[a-z0-9\-]{3,15}$/i", $server)) return api_error_raise("Sorry, that is not a valid server name.");

	$id = api_keystore_increment("VOICESERVERS", 0, "nextid");

	api_voice_servers_setting_set($id, "name", $server);
	api_voice_servers_setting_set($id, "status", "disabled");
	api_voice_servers_setting_set($id, "ip", "");

	$mode = 0766;
	mkdir(SAVE_LOCATION . "/sip/" . $server, $mode);
	mkdir(SAVE_LOCATION . "/iax/" . $server, $mode);

	return $id;

}

// Check if server already assigned

function api_voice_servers_checknameexists($server){

	if(api_keystore_checkkeyexists("VOICESERVERS", "name", $server)) return true;
	else return false;

}

// Check if the current serverid exists

function api_voice_servers_checkidexists($serverid){

	if(!is_numeric($serverid)) return false;

	if(api_keystore_get("VOICESERVERS", $serverid, "name") !== FALSE) return true;
	else return false;

}

// Update voice server

// Remove voice server

function api_voice_servers_delete($serverid){
	$dirs = ['sip', 'iax'];

	if(!is_numeric($serverid)) {
		return false;
	}

	if(api_keystore_checkkeyexists("VOICESUPPLIER", "voiceserver", $serverid)) {
		return api_error_raise("Sorry, cannot delete a server that is assigned to a supplier");
	}

	$name = api_voice_servers_setting_getsingle($serverid, "name");

	// sanity checks
	foreach($dirs as $directory) {
		$path = SAVE_LOCATION . "/{$directory}/{$name}";
		if(!is_dir($path)) {
			return api_error_raise("Sorry, the directory '{$path}' does not exists.");
		}
		// directory mkuist be empty
		if(count(glob("$path/*")) !== 0) {
			return api_error_raise("Sorry, the directory '{$path}' is not empty.");
		}
	}

	// safely remove them
	foreach($dirs as $directory) {
		$path = SAVE_LOCATION . "/{$directory}/{$name}";
		rmdir($path);
	}

	api_keystore_purge("VOICESERVERS", $serverid);

	return true;

}

// List all voice servers

function api_voice_servers_listall($short = 0){

	return api_keystore_getids("VOICESERVERS", "name", true);

}

function api_voice_servers_listall_active($short = 0){

	$serverids = api_keystore_getidswithvalue("VOICESERVERS", "status", "active");
	$servers = api_keystore_get_multi_byid("VOICESERVERS", $serverids, "name");

	// If a SITE_IDENTIFIER is set, only return servers that match this identifier
	if(defined('SITE_IDENTIFIER')) {

		// Only use servers that match our voice server site prefix
		foreach($servers as $serverid => $name) {

			$siteidentifier = trim(api_voice_servers_setting_getsingle($serverid, "siteidentifier"));

			if(empty($siteidentifier) || ($siteidentifier != SITE_IDENTIFIER)) unset($servers[$serverid]);

		}
	}

	return $servers;

}

// Server settings

  // Add or update setting

function api_voice_servers_setting_set($id, $setting, $value){ return api_keystore_set("VOICESERVERS", $id, $setting, $value); }

  // Delete setting

    // Single
function api_voice_servers_setting_delete_single($id, $setting){ return api_keystore_delete("VOICESERVERS", $id, $setting); }

  // Get

    // Single

function api_voice_servers_setting_getsingle($id, $setting){ return api_keystore_get("VOICESERVERS", $id, $setting); }


    // Multi

function api_voice_servers_setting_get_multi_byitem($id, $items) { return api_keystore_get_multi_byitem("VOICESERVERS", $id, $items); }

      // All settings

function api_voice_servers_setting_getall($id) { return api_keystore_getnamespace("VOICESERVERS", $id); }


// Connect to Asterisk using HTTP

function api_voice_servers_connect_http($serverid){

	global $SOCKET_HANDLER_HTTP;

	unset($SOCKET_HANDLER_HTTP[$serverid]);

	$ip = api_voice_servers_setting_getsingle($serverid, "ip");

	$keys = array("action" => "login", "username" => "manager", "secret" => sha1($ip));

	$result = api_voice_servers_send_http($serverid, $keys, true);

	if(preg_match("/Authentication accepted/", $result)) {

		$SOCKET_HANDLER_HTTP[$serverid] = true;

		return true;

	} else return false;

}


// Send to Asterisk using HTTP

function api_voice_servers_send_http($serverid, $keys, $newsocket = false){

	global $SOCKET_HANDLER_HTTP;
	global $VOICE_SERVER_SOCKETS;

	$url = "https://" . api_voice_servers_setting_getsingle($serverid, "ip") . ":8089/rawman?";

	foreach($keys as $key => $value) {
		if(($key == "variables") AND is_array($value)) {
			foreach($value as $varkey => $varvalue) {
				$url .= "&variable=" . urlencode(preg_replace('/([\(\)])/', '\\\\\\0', $varkey) . "=" . $varvalue);
			}
		} else $url .= "&" . $key . "=" . urlencode($value);
	}

	$attempts = 0;

	do{

		$VOICE_SERVER_SOCKETS[$serverid] = curl_init();

		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_URL, $url);
		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_TIMEOUT, 10);
		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_COOKIEJAR, '/tmp/morpheus-cookie-' . $serverid . '-' . posix_getuid() . '.txt');
		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_COOKIEFILE, '/tmp/morpheus-cookie-' . $serverid . '-' . posix_getuid() . '.txt');
		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_RETURNTRANSFER, true);
		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_PROXY, "");
		curl_setopt($VOICE_SERVER_SOCKETS[$serverid], CURLOPT_SSL_VERIFYPEER, false);

		// Execute post
		$result = curl_exec($VOICE_SERVER_SOCKETS[$serverid]);

		$info = curl_getinfo($VOICE_SERVER_SOCKETS[$serverid]);

		curl_close($VOICE_SERVER_SOCKETS[$serverid]);

		if(($info["http_code"] == 200) AND (!preg_match("/Permission denied/", $result))) return $result;
		else {

			$attempts++;

			unset($SOCKET_HANDLER_HTTP[$serverid]);
			unset($VOICE_SERVER_SOCKETS[$serverid]);

			if(!$newsocket AND !isset($SOCKET_HANDLER_HTTP[$serverid]) AND !api_voice_servers_connect_http($serverid)) {
				api_misc_audit('ASTERISK_CONNECT_FAILED', "VoiceSupplier=" . $serverid . "; Response=" . serialize($result) . "; status_code=" . $info["http_code"]);
				return false;
			}

		}

	} while($attempts < 2);

	api_misc_audit("ASTERISK_SEND_FAILED", "VoiceSupplier=" . $serverid . "; Response=" . serialize($result));

	return false;

}


  // Server ping status

function api_voice_servers_ping($serverid){

	if(!is_numeric($serverid));

	api_voice_servers_send_http($serverid, array("action" => "ping"));

	return true;

}

function api_voice_servers_hangup($serverid, $channel){

	if(!is_numeric($serverid));

	$result = api_voice_servers_send_http($serverid, array("action" => "hangup", "channel" => $channel));

	if(preg_match("/success/i", $result)) return true;
	else return false;

}

  // Server channel count

  // Server channels

    // Counts

function api_voice_servers_channels_count($serverid){

	if(!is_numeric($serverid)) return false;

	$results = api_voice_servers_send_http($serverid, array("action" => "command", "command" => "core show channels count"));

	if(preg_match("/([0-9]+) active call/i", $results, $result)) return $result[1];
	else return false;

}

    // Summary

function api_voice_servers_channels_summary($servers = array()){

	if(empty($servers)) $servers = array_keys(api_voice_servers_listall_active());
	elseif(is_numeric($servers)) $servers = array($servers);

	$items = array();

	$i = 0;

	if(is_array($servers))
	foreach($servers as $serverid){

		// Suck in the call list
		$results = api_voice_servers_send_http($serverid, array("action" => "status"));

		if(!empty($results)){

			$calls = explode("\r\n\r\n", $results);

			foreach($calls as $call) {
				foreach(explode("\r\n", $call) as $value) {

					if(!empty($value)) {
						list($k, $v) = explode(": ", $value);
						if(!empty($k)) $items[$i][$k] = $v;
					}

				}

				$items[$i]["serverid"] = $serverid;

				if(isset($items[$i]["Channel"])) $items[$i]["EncChannel"] = api_misc_crypt_safe($serverid . "::" . $items[$i]["Channel"]);

				if(!isset($items[$i]["Event"]) OR ($items[$i]["Event"] != "Status")) unset($items[$i]);
				else $i++;

			}

		}

	}

	return $items;

}

function api_voice_servers_channels_json(){

	$status = [
		"Ring" => 0,
		"Up" => 0,
	];

	$results = $calls = [];

	foreach(api_voice_servers_channels_summary() as $call => $details) {

        if(!isset($details["Seconds"]) || !in_array($details["ChannelStateDesc"], ["Up", "Ring"])) continue;

        // Asterisk v13 uses "Exten" whereas v11 uses "Extension". Handle appropriately.
        $details["Exten"] = (!empty($details["Extension"])) ? $details["Extension"] : $details["Exten"];

        if(empty($details["Exten"])) continue;


		if(empty($details["Linkedid"])) {
			$details["Linkedid"] = $details["Uniqueid"];
		}

		$results[$details["serverid"] . $details["Linkedid"]][] = $details;

	}

	foreach($results as $callid => $legs) {

        $call = [
			"source" => $legs[0]["CallerIDNum"],
			"destination" => $legs[0]["ConnectedLineNum"],
			"context" =>  $legs[0]["Context"] . "," . $legs[0]["Exten"] . "," . $legs[0]["Priority"],
			"duration" => $legs[0]["Seconds"],
			"state" => $legs[0]["ChannelStateDesc"],
			"hangup" => $legs[0]["EncChannel"],
			"campaignid" => false,
        ];

        if(!empty($legs[0]["Accountcode"]) && ($target = api_targets_getinfo($legs[0]["Accountcode"]))) $call["campaignid"] = $target["campaignid"];

        if(in_array($legs[0]["Context"], ["ReachTEL-CallMe", "RT-AutoCallbackHandler", "ReachTEL-Research-Inbound"])) {

			$call["source"] = $legs[0]["CallerIDNum"];
			$call["destination"] = $legs[0]["Exten"];

        } elseif((count($legs) == 1) && in_array($legs[0]["Context"], ["ReachTEL-CallSpooler", "ReachTEL-WashSpooler", "outboundcall"])) {

			$call["destination"] = $legs[0]["Exten"];
			$call["context"] = $legs[0]["Context"] . ",s," . $legs[0]["Priority"];

        }

        $status[$legs[0]["ChannelStateDesc"]]++;

        $calls[] = $call;

	}


	if(count($calls) > 100) $calls = [];

	return json_encode([
		"status" => $status,
		"calls" => $calls,
	]);

}


// Select a server

function api_voice_servers_select(){

	$servers = api_voice_servers_listall();

	$max = 0;

	if(is_array($servers))
	foreach($servers as $serverid => $servername){

		$array[$serverid] = api_voice_servers_setting_getsingle($serverid, "percent");
		$score = $array[$serverid] * rand(0,100);

		if($score >= $max){

			$max = $score;
			$winner = $serverid;

		}

	}

	return $winner;

}

// Reload all

function api_voice_servers_reloadall($type = null){

	$servers = array_keys(api_voice_servers_listall_active());

	if($type == "sip") $reload = "sip ";
	elseif($type == "iax2") $reload = "iax2 ";
	else $reload = "";

	if(is_array($servers))
	foreach($servers as $serverid){

		api_voice_servers_send_http($serverid, array("action" => "command", "command" => $reload . " reload"));

	}

}

function api_voice_servers_hangup_bytargetid($targetid){

	if(!is_numeric($targetid)) return api_error_raise("Sorry, that is not a valid target");

	$sql = "SELECT `key_store`.`value` FROM `provider_map`, `key_store` WHERE `key_store`.`id` = `provider_map`.`providerid` AND `key_store`.`type` = ? AND `item` = ? AND `provider_map`.`targetid` = ?";
	$rs = api_db_query_read($sql, array("VOICESUPPLIER", "voiceserver", $targetid));

	if($rs->RecordCount() > 0){

		$channels = api_voice_servers_channels_summary($rs->Fields("value"));

		foreach($channels as $channel) {

			if(!empty($channel["Channel"]) AND preg_match("/ Local\//", $channel["Channel"])) continue;

			if(!empty($channel["Accountcode"]) AND ($channel["Accountcode"] == $targetid)) return api_voice_servers_hangup($channel["serverid"], $channel["Channel"]);
		}

		return false;	// Return false if there is a call to hang up but we couldn't find it

	} else return true;	// Return true if there is no call to hang up

}

function api_voice_servers_confbridge_list($conference, $servers = null){

	if(!is_numeric($conference)) return api_error_raise("Sorry, that is not a valid conference ID");

	if(empty($servers)) $servers = array_keys(api_voice_servers_listall_active());
	elseif(is_numeric($servers)) $servers = array($servers);

	$participants = array();

	foreach($servers as $serverid){

		$results = api_voice_servers_send_http($serverid, array("action" => "confbridgelist", "conference" => $conference));

		if($results == false) continue;

		if(preg_match("/No active conferences/", $results)) continue;

		$events = explode("\r\n\r\n", $results);

		foreach($events as $event){

			$items = array();

			foreach(explode("\r\n", $event) as $line){

				if(!empty($line)) {
					list($k, $v) = explode(": ", $line);

					if($v == "Yes") $v = true;
					elseif($v == "No") $v = false;
					elseif($v == "<no name>") $v = null;
					elseif($v == "<no num>") $v = null;

					if(!empty($k)) $items[strtolower($k)] = $v;
				}

			}

			if(!isset($items["event"]) OR ($items["event"] != "ConfbridgeList")) continue;

			$items["serverid"] = $serverid;

			unset($items["event"]);

			$participants[] = $items;

		}
	}

	return $participants;

}

function api_voice_servers_confbridge_kick($conference, $serverid, $channel){

	if(!is_numeric($serverid)) return api_error_raise("Sorry, that is not a valid server ID");

	if(empty($channel)) return api_error_raise("Sorry, that is not a valid conference channel");

	$results = api_voice_servers_send_http($serverid, array("action" => "confbridgekick", "conference" => $conference, "channel" => $channel));

	if(preg_match("/User kicked/", $results)) return true;
	else return false;

}
