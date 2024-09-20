<?php

function inbound_320($message){

	// 61429987281

	$tags = api_sms_dids_tags_get(320);

	api_sms_apisend($message['from'], $tags["autoreply-message"], $tags["api-account"]);

	return true;

}