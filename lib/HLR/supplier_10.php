<?php

function api_hlr_send_supplier_10($msisdn){

    // Pivotel

	$supplier = 10;

	global $curl_handle, $statsd;

	$starttime = microtime(true);

	$tags = api_hlr_supplier_tags_get($supplier);

	$supplierid = uniqid();

	$xml = new SimpleXMLElement("<RSQuery />");

	$xml->addAttribute("ClientName", $tags["username"]);
	$xml->addAttribute("Password", $tags["password"]);
	$xml->addAttribute("RequestID", $supplierid);

	$xml->Number = $msisdn;

    //open connection
	if(!isset($curl_handle["hlr"][$supplier])) $curl_handle["hlr"][$supplier] = curl_init();

	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_URL, $tags["host"]);
	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_POSTFIELDS, $xml->asXML());
	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_TIMEOUT, HLR_TIMEOUT);
	curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($curl_handle["hlr"][$supplier], CURLOPT_PROXY, PROXY_EXTERNAL);

    //execute post
	$response = curl_exec($curl_handle["hlr"][$supplier]);

	$info = curl_getinfo($curl_handle["hlr"][$supplier]);

	if(($info["http_code"] != 200) OR empty($response)) {
		$statsd->increment("morpheus.sms.hlrlookup.errors." . $tags["shortname"] . "." . $info["http_code"]);
		api_misc_audit("PIVOTEL_ERROR", "EMPTY RESPONSE: HTTP=" . $info["http_code"] . "; MSISDN=" . $msisdn);
		return false;
	}

	if(!empty($tags["debug"])) api_misc_audit("PIVOTEL_LATENCY", serialize($info));

	$xml_response = new SimpleXMLElement($response);

	if (empty($xml_response)) {

		api_misc_audit("PIVOTEL_ERROR", "INVALID XML: " . serialize($response) . "; MSISDN:" . $msisdn);
		return false;
	}

	$result = array("msisdn" => $msisdn, "status" => "INDETERMINATE", "active" => false, "hlrcode" => 997, "response" => $response, "carriercode" => null, "supplierid" => $supplierid);

	if(!empty($xml_response->MCC) AND !empty($xml_response->MNC)){

		$result["status"] = "CONNECTED";
		$result["active"] = true;
		$result["hlrcode"] = 0;
		$result["carriercode"] = $xml_response->MCC . $xml_response->MNC;

	} else {

		switch($xml_response->ErrorCode) {

            case "-1": // Invalid client name or password

	            api_misc_audit("PIVOTEL_ERROR", "AUTHFAIL; MSISDN:" . $msisdn);
            	return false;

            case "1": // Number is not connected

            	$result["status"] = "DISCONNECTED";
            	$result["active"] = false;
            	$result["hlrcode"] = 1;
	            break;

            case "11": // SMS service is not provisioned

				$result["status"] = "DISCONNECTED";
            	$result["active"] = false;
				$result["hlrcode"] = 11;
				break;

            case "13": // Call barred

	            $result["status"] = "DISCONNECTED";
            	$result["active"] = false;
	            $result["hlrcode"] = 13;
            	break;

            case "21": // Facility not supported

	            $result["status"] = "CONNECTED";
            	$result["active"] = true;
	            $result["hlrcode"] = 21;
            	break;

            case "27": // Absent subscriber

            	$result["status"] = "CONNECTED";
            	$result["active"] = true;
            	$result["hlrcode"] = 6;

            	if(!empty($xml_response->CAC)) $result["carriercode"] = api_hlr_supplier_cactomccmnc((string)$xml_response->CAC);

            	break;

            default:

	            api_misc_audit("PIVOTEL_ERROR", "Err=" . $xml_response->ErrorCode . "; ErrText=" . $xml_response->ErrorText . "; MSISDN=" . $msisdn);
            	return false;
        }

    }

    $statsd->timing("morpheus.sms.hlrlookup.latency." . $tags["shortname"], (microtime(true) - $starttime)*1000);

	if(in_array($result["status"], array("CONNECTED", "DISCONNECTED"))) return $result;
	else return false;
}