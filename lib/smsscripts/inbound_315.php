<?php

function inbound_315($message){

	// 61429951864

	// Only destinations we know about can particpate.
	if(empty($message["target"])) return true;

	$tags = api_sms_dids_tags_get(315);

	$response_data = api_data_responses_getall($message["target"]["targetid"]);

	$message["contents"] = trim($message["contents"]);

	if(preg_match("/^(stop|opt out|optout|do not text|unsubscribe)/i", $message["contents"])) { // Handle opt outs

		api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "OPTOUT", "YES");
		api_restrictions_donotcontact_add("phone", $message["e164"], api_campaigns_setting_getsingle($message["target"]["campaignid"], "donotcontactdestination"));

		api_sms_apisend($message['from'], $tags["optout-message"], $tags["api-account"]);

	} else if(!isset($response_data["Q1-rating"])) { // No questions have been answered yet

		api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "Q1", $message["contents"]);

		if(preg_match("/^(10|[1-9])/", $message["contents"], $matches)) {

			api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "Q1-rating", $matches[1]);
			api_sms_apisend($message['from'], $tags["q2"], $tags["api-account"]);

		} else {

			return true;
		}

	} else if(!isset($response_data["Q2-rating"])) {

		api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "Q2", $message["contents"]);

		if(preg_match("/^([1-7])/", $message["contents"], $matches)) {

			api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "Q2-rating", $matches[1]);
			api_sms_apisend($message['from'], $tags["q3"], $tags["api-account"]);

		} else {

			return true;
		}

	} else if (!isset($response_data["Q3-rating"])){

		api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "Q3", $message["contents"]);

		if(preg_match("/^([1-4])/", $message["contents"], $matches)) {

			api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "Q3-rating", $matches[1]);
			api_sms_apisend($message['from'], $tags["thank-you-message"], $tags["api-account"]);

		} else {

			return true;
		}

	}

	return true;

}