<?php

function inbound_343($message){

	// 61437286811

	$tags = api_sms_dids_tags_get(343);

	$message["contents"] = trim($message["contents"]);

	if(time() > strtotime($tags["closetime"])) {

		return true;

	} else if(preg_match("/^(yes|y)/i", $message["contents"])) {

		api_sms_apisend($message["e164"], $tags["autoreply"], $tags["apiaccount"]);

	}

	return true;
}