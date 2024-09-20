<?php

function inbound_715($message) {
	// 61488822638

	// Check this message was received a target of a campaign
	if(empty($message['target'])) {
		return true;
	}

	// Check that they responded "Y" or "yes"
	if (! preg_match('/^y$|yes/i', trim($message['contents']))) {
		return true;
	}

	// Get the source campaign name
	$message["target"]["campaignname"] = api_campaigns_setting_getsingle($message["target"]["campaignid"], "name");

	// Get the voice campaign name from the SMS campaign
	$campaignname = api_campaigns_tags_get($message['target']['campaignid'], 'Inbound-Research-Campaign');
	if (! $campaignname) {
		return true;
	}

	// Try to get the voice campaign ID
	$campaignid = api_campaigns_checknameexists($campaignname);
	if (! is_numeric($campaignid)) {
		return true;
	}

	// Add the target to the voice campaign
	$mergedata = array_merge(
		api_data_merge_get_all($message['target']['campaignid'], $message['target']['targetkey']),
		['sourcecampaign' => $message['target']['campaignname']]
	);
	api_targets_add_single($campaignid, $message['target']['destination'], $message['target']['targetkey'], null, $mergedata);
	api_campaigns_setting_set($campaignid, "status", "ACTIVE");

	return true;
}
