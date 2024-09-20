<?php

function inbound_704($message){

	// 61488822627

	$tags = api_sms_dids_tags_get(704);

	// Opt out handling
	if(preg_match("/^(no|stop|n0|opt out|optout|do not text|unsubscribe)/i", trim($message["contents"]))) {

		api_restrictions_donotcontact_add("phone", $message["e164"], $tags["dnclist"]);
	}

	return true;

}