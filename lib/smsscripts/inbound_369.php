<?php

function inbound_369($array){

	// 61429558822

	$tags = api_sms_dids_tags_get(369);

	if(strtotime($tags["closetime"]) < time()) return true;

	api_sms_apisend($array["e164"], $tags["reply"], $tags["apiaccount"]);

	return true;

}