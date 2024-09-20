<?php

function inbound_19($array){

	// 61447227755

	$tags = api_sms_dids_tags_get(19);

	if(time() > strtotime($tags["closetime"])){

		return true;

	} else {

		if(preg_match("/tuesday/i", $array["contents"])) {

			api_sms_apisend($array["from"], $tags["reply-tuesday"], 66);

		} else if(preg_match("/weekend/i", $array["contents"])) {

			api_sms_apisend($array["from"], $tags["reply-weekend"], 66);
		}

		api_misc_competitions_add($tags["competitionname"], $array["from"], $array["contents"]);

		return true;

  	}

}