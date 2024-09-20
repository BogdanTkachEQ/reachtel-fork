<?php

require_once('Morpheus/api.php');

$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);

$reportEmail = isset($tags['report-email']) ? $tags['report-email'] : 'ReachTEL Support <support@reachtel.com.au>';

if(empty($qos) OR ($qos < (time() - 600))) {

	$email["to"]          = $reportEmail;
	$email["subject"]     = "[ReachTEL] Outage Report - " . date("Y-m-d H:i:s");

	api_system_setting_set("QOSLOCK", time());

	// DB connectivity

	$result = api_db_ping();

	if($result === false){

		$email["textcontent"] = "We have detected a MySQL database outage event.";
		$email["htmlcontent"] = "We have detected a MySQL database outage event.";
		api_email_template($email);

	}

	// Apache connectivity

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://www.reachtel.com.au");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	curl_exec($ch);

	if(curl_errno($ch)) {
		$email["htmlcontent"] = "www.reachtel.com.au appears to be down";
		$email["textcontent"] = "www.reachtel.com.au appears to be down";

		api_email_template($email);
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://portals.reachtel.com.au");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	curl_exec($ch);

	if(curl_errno($ch)) {
		$error_msg = curl_error($ch);
		$email["htmlcontent"] = "portals.reachtel.com.au appears to be down. Error: " . $error_msg;
		$email["textcontent"] = "portals.reachtel.com.au appears to be down. Error: " . $error_msg;

		api_email_template($email);
	}

	// Asterisk connectivity

	foreach(api_voice_servers_listall(1) as $serverid) {
		if((api_voice_servers_setting_getsingle($serverid, "status") == "active") AND (api_voice_servers_ping($serverid) != TRUE)) {

			$email["textcontent"] = "We have detected an Asterisk outage event. " . api_voice_servers_setting_getsingle($serverid, "name") . " is not responding";
			$email["htmlcontent"] = "We have detected an Asterisk outage event. " . api_voice_servers_setting_getsingle($serverid, "name") . " is not responding";
			api_email_template($email);
		}

	}

	// Internet/proxy connectivity

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://www.mynetfone.com.au/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	curl_exec($ch);

	if(curl_errno($ch)) {
		$email["htmlcontent"] = "External internet access appears to be down (https://www.mynetfone.com.au/)";
		$email["textcontent"] = "External internet access appears to be down (https://www.mynetfone.com.au/)";

		api_email_template($email);
	}

	// Voice suppliers

	foreach(api_voice_supplier_listall(1) as $supplierid => $name) {
		if((api_voice_supplier_setting_getsingle($supplierid, "status") == "ACTIVE") AND (api_voice_supplier_status($supplierid) != TRUE)) {

			$email["textcontent"] = "We have detected a voice supplier outage event. " . api_voice_supplier_setting_getsingle($supplierid, "name") . " is not responding";
			$email["htmlcontent"] = "We have detected a voice supplier outage event. " . api_voice_supplier_setting_getsingle($supplierid, "name") . " is not responding";
			api_email_template($email);
		}
	}

	// SMS Checks

	$qossmssent = api_system_setting_getsingle("QOSSMSSENT");

	if(($qossmssent > 1) AND ((time() - $qossmssent) > 120)){

		$email["textcontent"] = "We have detected that a QOS SMS is delayed and hasn't been received yet. Sent at " . date("Y-m-d H:i:s", $qossmssent);
		$email["htmlcontent"] = "We have detected that a QOS SMS is delayed and hasn't been received yet. Sent at " . date("Y-m-d H:i:s", $qossmssent);
		api_email_template($email);

		api_system_setting_delete_single("QOSSMSSENT");
	}


	$result = api_sms_send("33", "61438696779", "RT-:" . base64_encode(api_misc_crypt("RT-:" . time() . ":ROUTO:" . api_misc_uniqueid())));

	if($result == false){

		$email["textcontent"] = "We have detected a SMS outage event. Unable to send QOS message.";
		$email["htmlcontent"] = "We have detected a SMS outage event. Unable to send QOS message.";
		api_email_template($email);

	} else api_system_setting_set("QOSSMSSENT", time());


	// Bad data check

	$result = api_restrictions_baddata_add("phone", "0731038300");

	if($result == false){

		$email["textcontent"] = "Couldn't add data to the bad data list";
		$email["htmlcontent"] = "Couldn't add data to the bad data list";
		api_email_template($email);
	}

	$result = api_restrictions_baddata_remove_single("phone", "0731038300");

	if($result == false){

		$email["textcontent"] = "Couldn't remove data to the bad data list";
		$email["htmlcontent"] = "Couldn't remove data to the bad data list";
		api_email_template($email);
	}


	// Wash tests
	$destination = "6123132403"; //invalid length
	$result = api_wash_check($destination, 49);

	if($result["reason"] != "UNSUPPORTED_PREFIX"){

		$email["textcontent"] = "We have detected a wash error. Invalid length number is returning: '" . $result["reason"] . "'";
		$email["htmlcontent"] = "We have detected a wash error. Invalid length number is returning: '" . $result["reason"] . "'";
		api_email_template($email);

	}

	require_once("Morpheus/api_queue_gearman.php");

	$gearman = api_queue_gearman_getjobcount();

	foreach($gearman as $queue => $stats) {
		if(($stats["jobs"] > 0) AND ($stats["workers"] == 0)){
			$email["textcontent"] = "Job queue error. There are " . $stats["jobs"] . " jobs in the queue " . $queue . " and no workers to process them.";
			$email["htmlcontent"] = "Job queue error. There are " . $stats["jobs"] . " jobs in the queue " . $queue . " and no workers to process them.";
			api_email_template($email);
		}
	}

	api_system_setting_delete_single("QOSLOCK");

	// Morpheus logger health check
	//api_misc_morpheus_logger_health_check();
}
