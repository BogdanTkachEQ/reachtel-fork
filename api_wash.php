<?php

// Data washing

function api_wash_out_save($id, $status, $reason, $carriercode = null){

	$sql = "UPDATE `wash_out` SET `status` = ?, `reason` = ?, `carriercode` = ? WHERE `id` = ?";
	$rs = api_db_query_write($sql, array($status, $reason, $carriercode, $id));

	return true;

}

function api_wash_preflight($destination, $usecache = true, $options = array()){

	if(!is_array($destination)) return array("status" => "DISCONNECTED", "reason" => "INVALID_LENGTH", "destination" => null, "carriercode" => null);

	$supportedtypes = array("aufixedline", "aumobile", "nzfixedline", "nzmobile", "sgmobile", "gbmobile");

	if(!in_array($destination["type"], $supportedtypes)) return array("status" => "INDETERMINATE", "reason" => "UNSUPPORTED_PREFIX", "destination" => $destination["destination"], "carriercode" => null, "hlrcode" => 998);

	$sql = "SELECT * FROM `number_washing_ranges` WHERE `from` <= ? AND `to` >= ? LIMIT 1";
	$rs = api_db_query_read($sql, array($destination["destination"], $destination["destination"]));

	if(!$rs OR ($rs->RecordCount() == 0)) return array("status" => "DISCONNECTED", "reason" => "INVALID_RANGE", "destination" => $destination["destination"], "carriercode" => null);

	if($usecache){

		$sql = "SELECT * FROM `wash_out` WHERE `destination` = ? AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? HOUR) AND `status` IN (?, ?) AND `reason` != ? ORDER BY `id` DESC LIMIT 1";
		$rs = api_db_query_read($sql, array($destination["destination"], WASH_BACKCHECK_HOURS, "CONNECTED", "DISCONNECTED", "CACHED_RESULT"));

		if($rs->RecordCount() > 0) return array("status" => $rs->Fields("status"), "reason" => "CACHED_RESULT", "destination" => $destination["destination"], "carriercode" => $rs->Fields("carriercode"), "hlrcode" => $rs->Fields("hlrcode"));

		// Check if we are trying to process the same number in quick succession

		if(!empty($options["eventid"])){

			$sql = "SELECT * FROM `wash_out` WHERE `destination` = ? AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? SECOND) AND `status` = ? AND `id` != ? ORDER BY `id` DESC LIMIT 1";
			$rs = api_db_query_read($sql, array($destination["destination"], 60, "QUEUED", $options["eventid"]));

			if($rs->RecordCount() > 0) {

				$i = 0;

				do{
					$sql = "SELECT * FROM `wash_out` WHERE `id` = ? AND `status` != ?";
					$rs2 = api_db_query_read($sql, array($rs->Fields("id"), "QUEUED"));

					$i++;

					if($rs2->RecordCount() > 0) {

						if(in_array($rs2->Fields("status"), array("CONNECTED", "DISCONNECTED"))) return array("status" => $rs2->Fields("status"), "reason" => "CACHED_RESULT", "destination" => $destination["destination"], "carriercode" => $rs2->Fields("carriercode"), "hlrcode" => $rs2->Fields("hlrcode"));
						else return true;

					} else usleep(250000);

				} while($i < 40);

			}
		}

		// Add back bad data checks when appropriate

	}

	return true;

}

function api_wash_generate($target, $settings){

	if(!is_array($target)) return false;

	$formatteddestination = api_data_numberformat($target["destination"], $settings["region"]);

	$result = api_wash_preflight($formatteddestination);

	$treatment = api_wash_prefixtreatment($formatteddestination);

	if($treatment["method"] == "none") $result = array("status" => "INDETERMINATE", "carriercode" => $treatment["carriercode"]);

	if(is_array($result)) {

		$uniqueid = api_misc_uniqueid();

		$add = api_data_responses_add($target["campaignid"], $uniqueid, $target["targetid"], $target["targetkey"], "status", $result["status"]);

		if(!empty($result["carriercode"])) api_data_responses_add($target["campaignid"], $uniqueid, $target["targetid"], $target["targetkey"], "rt-carriercode", $result["carriercode"]);

		if($add) {

			api_targets_updatestatus($target["targetid"], "COMPLETE", NULL);
			api_campaigns_update_lastsend($target['campaignid']);
			return true;
		}

	}

	if($treatment["method"] == "hlr") api_queue_add("wash", array("targetid" => $target["targetid"], "destination" => $formatteddestination), null, 0, array("priority" => "low"));
	else {

		$overridesettings = array("type" => "wash", "dialplan" => 325, "redialtimeout" => 60, "retrylimit" => 1, "ringoutlimit" => 1, "ringtime" => WASH_RING_TIME, "voicedid" => WASH_VOICE_DID, "voicesupplier" => 0, "withholdcid" => "on");

		$settings = array_merge($settings, $overridesettings);

		$target["destination"] = $formatteddestination["fnn"];

		return api_voice_generate($target, $settings);

	}

	api_campaigns_update_lastsend($target['campaignid']);
}

function api_wash_check($destination, $userid, $options = array()){

	if(empty($userid) or !is_numeric($userid)) return api_error_raise("Invalid userid provided");

	if(empty($destination)) return array("id" => "E" . api_keystore_increment("SYSTEM", 0, "washerrors"), "status" => "DISCONNECTED", "reason" => "UNSUPPORTED_PREFIX", "destination" => $destination, "carriercode" => null, "hlrcode" => 998);

	$destination = api_data_numberformat($destination);

	$supported = array("aufixedline", "aumobile", "nzfixedline", "nzmobile", "sgmobile");

	if(empty($destination["destination"])) return array("id" => "E" . api_keystore_increment("SYSTEM", 0, "washerrors"), "status" => "DISCONNECTED", "reason" => "UNSUPPORTED_PREFIX", "destination" => $destination, "carriercode" => null, "hlrcode" => 998);
	elseif(!in_array($destination["type"], $supported)) return array("id" => "E" . api_keystore_increment("SYSTEM", 0, "washerrors"), "status" => "DISCONNECTED", "reason" => "UNSUPPORTED_PREFIX", "destination" => $destination, "carriercode" => null, "hlrcode" => 998);

	$sql = "INSERT INTO `wash_out` (`userid`, `destination`, `billingtype`, `status`, `reason`, `returncarrier`, `billing_products_region_id`, `billing_products_destination_type_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
	$rs = api_db_query_write($sql, array($userid, $destination["destination"], "wash" . $destination["type"], "QUEUED", "QUEUED", 1, $destination['billing_region_id'], $destination['billing_destination_type_id']));

	$eventid = api_db_lastid();

	$result = api_queue_add("wash_out", array("destination" => $destination, "eventid" => $eventid, "userid" => $userid), null, 0, array("priority" => "high"));

	$runs = 0;

	if (!api_misc_is_test_environment()) {
		usleep(100000);
	}

	$totalWaitSeconds = api_users_tags_get($userid, "wash_timeout_seconds") ?: 60;
	if ($totalWaitSeconds < 1) {
		$totalWaitSeconds = 1;
	} else if ($totalWaitSeconds > 60) {
		$totalWaitSeconds = 60;
	}

	$totalWaitMicSeconds = $totalWaitSeconds * 1000000;
	$totalSleep = 550000;
	$totalRuns = floor($totalWaitMicSeconds/$totalSleep);

	do {

		$sql = "SELECT * FROM `wash_out` WHERE `id` = ? AND `userid` = ?";
		$rs = api_db_query_read($sql, array($eventid, $userid));
		if(($rs->RecordCount() > 0) AND ($rs->Fields("status") != "QUEUED")) return array("id" => $eventid, "status" => $rs->Fields("status"), "reason" => $rs->Fields("reason"), "destination" => $destination, "carriercode" => $rs->Fields("carriercode"), "hlrcode" => $rs->Fields("hlrcode"));
      
		if (!api_misc_is_test_environment()) {
			usleep($totalSleep);
		}

		$runs++;

	} while ($runs < $totalRuns);
	return array("id" => $eventid, "status" => "INDETERMINATE", "reason" => "PING_FAILED", "destination" => $destination, "carriercode" => null, "hlrcode" => 997);

}

function api_wash_prefixtreatment($destination){

	if(!is_array($destination)) return false;

	if(preg_match("/mobile/", $destination["type"])) return array("method" => "hlr");
	else return array("method" => "call");
}
