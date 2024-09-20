<?php

function inbound_319($message){

	$tags = api_sms_dids_tags_get(319);

	$keywords = implode("|", array_map("trim", explode(",", $tags["autoreply-tags"])));

	if(empty($keywords) || empty($tags["campaignid"])  || empty($tags["start-date"]) || empty($tags["end-date"])) {
		return true;
	}

	$start = strtotime($tags["start-date"]);
	$end = strtotime($tags["end-date"]);
	$now = strtotime('now');

	if($start <= $now && $now <= $end) {
	    if(preg_match("/(" . $keywords . ")/i", $message["contents"])) {
	        api_targets_add_single($tags["campaignid"], $message['from'], $message['from'] . uniqid());
	        api_campaigns_setting_set($tags["campaignid"], "status", "ACTIVE");
	    }
	}

	return true;

}
