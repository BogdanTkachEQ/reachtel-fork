<?php

function api_hlr_send_supplier_1($msisdn){

	// InfoBIP

	$supplier = 1;

	global $curl_handle, $statsd;

	$starttime = microtime(true);

	$tags = api_hlr_supplier_tags_get($supplier);

	$parameters = array("output" => "json",
		"user" => $tags["username"],
		"pass" => $tags["password"],
		"destination" => $msisdn);

    //open connection
	if(!isset($curl_handle["hlr"][$supplier])) $curl_handle["hlr"][$supplier] = curl_init();

	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_URL, $tags["host"] . "?" . http_build_query($parameters));
	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_TIMEOUT, HLR_TIMEOUT);
	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_SSL_VERIFYPEER, 0); // skip ssl verification
	if(defined("PROXY_EXTERNAL")) curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_PROXY, PROXY_EXTERNAL);

    //execute post
	$response = curl_exec($curl_handle["hlr"][$supplier]);

	$info = curl_getinfo($curl_handle["hlr"][$supplier]);

	if($info["http_code"] != 200) $statsd->increment("morpheus.sms.hlrlookup.errors." . $tags["shortname"] . "." . $info["http_code"]);

	if(empty($response) OR ($response == "FAILED")) {
		api_misc_audit("INFOBIP_ERROR", "EMPTY RESPONSE: MSISDN:" . $msisdn);
		return false;
	}

	$elements = @json_decode($response);

	if($elements == false) {
		api_misc_audit("INFOBIP_ERROR", "INVALID JSON: " . serialize($response) . "; MSISDN:" . $msisdn);
		return false;
	}

    // err 11 = TELESERVICE_NOT_PROVISIONED
    // err 27 = ABSENT_SUBSCRIBER
    // err 13 = CALL_BARRED
	// err 21 = FACILITY_NOT_SUPPORTED

	$result = array("msisdn" => $msisdn, "status" => "INDETERMINATE", "active" => false, "hlrcode" => 997, "response" => $response, "carriercode" => null, "supplierid" => null);

	if(!empty($elements->{'id'})) $result["supplierid"] = $elements->{'id'};

	if(($elements->{'stat'} == "DELIVRD") OR in_array($elements->{'err'}, array(27, 21))){

		if($elements->{'stat'} == "DELIVRD") $result["hlrcode"] = 0;
		else $result["hlrcode"] = $elements->{'err'};

		$result["status"] = "CONNECTED";
		$result["active"] = true;
		$result["carriercode"] = $elements->{'mccmnc'};

	} elseif($elements->{'err'} == 13) {

		$result["status"] = "DISCONNECTED";
		$result["hlrcode"] = $elements->{'err'};

	} elseif($elements->{'stat'} == "UNDELIV"){

		if($elements->{'err'} == 1) {

			$result["status"] = "DISCONNECTED";
			$result["hlrcode"] = $elements->{'err'};

		} else if($elements->{'err'} == 11) {

			$result["status"] = "DISCONNECTED";
			$result["carriercode"] = $elements->{'mccmnc'};
			$result["hlrcode"] = $elements->{'err'};

		} else if($elements->{'err'} == 6) {

			$result["status"] = "CONNECTED";
			$result["active"] = true;
			$result["carriercode"] = $elements->{'mccmnc'};
			$result["hlrcode"] = $elements->{'err'};

		} else if($elements->{'err'} == 1153) {

			$result["status"] = "DISCONNECTED";
			$hlrresponse = 1;

		} else if($elements->{'err'} == 502) {

			return false;

		} else {
			api_misc_audit("INFOBIP_ERROR", "UNHANDLED RESPONSE: UNDELIV Code=" . $elements->{'err'} . "; MSISDN:" . $msisdn);
			return false;
		}

	} elseif($elements->{'stat'} == "REJECTD"){

		$result["status"] = "DISCONNECTED";
		$result["hlrcode"] = 996;

	} elseif($elements->{'stat'} == "UNKNOWN"){

		api_misc_audit("INFOBIP_ERROR", "UNHANDLED RESPONSE: UNKNOWN Code=" . $elements->{'err'} . "; MSISDN:" . $msisdn);
		return false;

	} else {

		api_misc_audit("INFOBIP_ERROR", "UNHANDLED RESPONSE: OTHER Code=" . $elements->{'err'} . "; MSISDN:" . $msisdn);
		return false;
	}

	$statsd->timing("morpheus.sms.hlrlookup.latency." . $tags["shortname"], (microtime(true) - $starttime)*1000);

	if(in_array($result["status"], array("CONNECTED", "DISCONNECTED"))) return $result;
	else return false;

}