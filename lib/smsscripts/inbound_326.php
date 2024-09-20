<?php

function inbound_326($message){

	// 61437065093

	// Only destinations we know about can particpate.
	if(empty($message["target"])) return true;

	$tags = api_sms_dids_tags_get(326);

	$response_data = api_data_responses_getall($message["target"]["targetid"]);

	$message["contents"] = trim($message["contents"]);

	if(preg_match("/^(stop|opt out|optout|do not text|unsubscribe)/i", $message["contents"])) { // Handle opt outs

		api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "OPTOUT", "YES");
		api_restrictions_donotcontact_add("phone", $message["e164"], api_campaigns_setting_getsingle($message["target"]["campaignid"], "donotcontactdestination"));

		api_sms_apisend($message['from'], $tags["optout-message"], $tags["api-account"]);

	} else if(preg_match("/^(yes|yeah|ok)/i", $message["contents"], $matches)) { // No questions have been answered yet

		api_data_responses_add($message["target"]["campaignid"], 0, $message["target"]["targetid"], $message["target"]["targetkey"], "PARTICIPATE", "YES");

		$merge_data = api_data_merge_get_all($message["target"]["campaignid"], $message["target"]["targetkey"]);

		$targetkey = api_misc_uniqueid();

		$merge_data["date"] = date("Y-m-d H:i:s");

		if(api_targets_add_single($tags["campaignid"], $message["from"], $targetkey, 1, $merge_data)) api_campaigns_setting_set($tags["campaignid"], "status", "ACTIVE");

	}

	return true;

}