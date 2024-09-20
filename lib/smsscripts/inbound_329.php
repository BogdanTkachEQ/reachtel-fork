<?php

function inbound_329($message){

	// 61437116202

	$tags = api_sms_dids_tags_get(329);

	// Opt out handling
	if(preg_match("/^(no|stop|n0|opt out|optout|do not text|unsubscribe)/i", trim($message["contents"]))) {

		api_restrictions_donotcontact_add("phone", $message["e164"], $tags["dnclist"]);
	}

	return true;

}