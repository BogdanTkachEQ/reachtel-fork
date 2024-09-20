<?php

function inbound_316($message){

	// 61437274800

	$tags = api_sms_dids_tags_get(316);

	$keywords = implode("|", array_map("trim", explode(",", $tags["autoreply-tags"])));

	if(empty($keywords) || empty($tags["autoreply-message"]) || empty($tags["api-account"])) {
		return true;
	}

	if(preg_match("/^(stop|opt out|optout|do not text|unsubscribe)/i", $message["contents"])) { // Handle opt outs

		api_restrictions_donotcontact_add("phone", $message["e164"], $tags['dnc-listid']);

	} else if(preg_match("/^(" . $keywords . ")/i", $message["contents"])) {

		api_sms_apisend($message['from'], $tags["autoreply-message"], $tags["api-account"]);

	}

	return true;

}