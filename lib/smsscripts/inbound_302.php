<?php

function inbound_302($array){

    // 0437143643

	// Initialise cURL
	$ch = curl_init();

	$parameters = array("to" => "0437143643", "from" => $array["from"], "message" => $array["contents"]);

	// Set up the cURL object
	curl_setopt($ch, CURLOPT_URL, "https://api.cloudin.net.au/reachtel/msg/rx");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	// Execute the request
	$result = curl_exec($ch);

	// Get status info on the cURL request
	$response_info = curl_getinfo($ch);

	if(($response_info["http_code"] == "200") AND preg_match("/OK/i", $result)) {
		api_misc_audit("POSTBACK_OK", serialize($parameters));
		return true;
	} else {
		api_misc_audit("POSTBACK_FAILED", "Returned: " . $result . "; URL: " . serialize($parameters));
		return false;
	}

}