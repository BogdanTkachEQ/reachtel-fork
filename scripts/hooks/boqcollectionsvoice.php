<?php

function api_campaigns_hooks_boqcollectionsvoice($campaignid){
	
	// Get this campaign's name
	$name = api_campaigns_setting_getsingle($campaignid, "name");

	// Work out what the name of the FollowUp campaign should be
	$followupname = preg_replace("/Voice/", "SMS", $name);

	api_misc_audit("CAMPAIGN_HOOK", "Received " . $name . "; Now looking for " . $followupname);

	$followupid = api_campaigns_checknameexists($followupname);

	if(!is_numeric($followupid)) return api_error_raise("Cannot find the SMS campaign for " . $name);

	$targets = api_data_target_status($followupid);

	if($targets["READY"] > 0) return api_error_raise("The SMS campaign already has data. We won't reprocess again");

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		if(empty($target) OR ($target["status"] != "ABANDONED")) continue;

		$response_data = api_data_responses_getall($targetid);

		if(!empty($response_data["DUPLICATE"]) OR !empty($response_data["REMOVED"])) continue;

		api_targets_add_single($followupid, $target["destination"], $target["targetkey"], $target["priority"], api_data_merge_get_all($campaignid, $target["targetkey"]));
	}

	api_campaigns_setting_set($followupid, "status", "ACTIVE");

	return true;

}

?>