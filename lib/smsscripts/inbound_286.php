<?php

function inbound_286($array){

	// 0427293243

	api_restrictions_donotcontact_add("phone", $array["from"], api_sms_dids_tags_get(286, "optout-listid"));

	return true;

}