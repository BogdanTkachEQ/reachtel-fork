<?php

require_once("Morpheus/api.php");
require_once("Morpheus/lib/bounce-handler/BounceHandler.php");

$options = array("searchString" => "UNSEEN",
	"returnHeadersArray" => true);

$handler = new BounceHandler(EMAIL_IMAP_CONNECTION, EMAIL_WEBAPP_USERNAME, EMAIL_WEBAPP_PASSWORD, $options);

if(!empty($handler->getErrors())) {

	api_error_raise("EMAIL_BOUNCE", "Error: " . serialize($handler->getErrors()));
	exit;
}

foreach($handler->getResults() as $message){

	if(empty($message["bounceType"])) {

		$handler->move($message["messageid"], "INBOX.BounceNotSure");
		continue;
	}

	if(preg_match("/smtp\-(.{8}\-.{4}\-.{4}\-.{4}\-.{12})@broadcast.reachtel.com.au/i", $message["emailHeadersArray"]["To"], $matches)) {

		$guid = $matches[1];

		//open connection
		$ch = curl_init();

		$parameters = array("action" => "post",
			"event" => array("guid" => $guid,
				"event" => "bounce",
				"status" => (!empty($message["statusCode"])) ? $message["statusCode"] : "5.1.1",
				"reason" => (!empty($message["diagnosticCode"])) ? $message["diagnosticCode"] : "550 Invalid recipient"));

		curl_setopt($ch, CURLOPT_URL, "https://api.reachtel.com.au/webhooks/smtpevents.php");
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

		//execute post
		$response = curl_exec($ch);

		curl_close($ch);

	} elseif(preg_match("/([a-z0-9]+)@broadcast.reachtel.com.au/i", $message["emailHeadersArray"]["To"], $matches)) {

		if(isset($matches[1])) $enctargetid = $matches[1];
		else {

			// We couldn't find a valid targetid so move it to the NoTargetId IMAP folder
			$handler->move($message["messageid"], "INBOX.NoTargetId");
			continue;
		}

		$targetid = api_misc_decrypt_safe($enctargetid);

		if(!is_numeric($targetid)) $handler->move($message["messageid"], "INBOX.InvalidTargetId");

		$target = api_targets_getinfo($targetid);

		if($message["bounceType"] == "hard") {

			api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "HARDBOUNCEREASON", $message["diagnosticCode"]);
			api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "HARDBOUNCE", "YES");
			api_restrictions_baddata_add("email", $target["destination"]);

		} else {

			api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "SOFTBOUNCE", "YES");
		}

		// Message processing is successful so delete the message
		$handler->delete($message["messageid"]);

	} else {
		$handler->move($message["messageid"], "INBOX.NoTargetId");
		continue;
	}

}