<?php

function api_campaigns_hooks_momentumsmspreprocess($campaignid){

	$groupid = 328;

	if(is_numeric(api_campaigns_setting_getsingle($campaignid, "finishtime"))) return api_error_raise("Sorry, it looks as though this campaign has already finished");

	if((api_campaigns_setting_getsingle($campaignid, "type") != "sms") OR (api_campaigns_setting_getsingle($campaignid, "groupowner") != $groupid)) return api_error_raise("Sorry, this campaign doesn't look to be in the correct format.");

	$tags = api_groups_tags_get($groupid);

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		// Create message variable

		$campaign = api_data_merge_get_single($campaignid, $target["targetkey"], "Campaign");

		// Check if we have a "Campaign" value
		if(empty($campaign)) {
			return api_error_raise("Unable to find a campaign type for destination '" . $destination . "'");
		}

		// Check if we have a "Campaign" message type
		if(empty($tags["message-" . $campaign])) {
			return api_error_raise("Unable to find message content for campaign type '" . $campaign . "'");
		}

		$content = api_data_merge_process($tags["message-" . $campaign], $targetid, true);

		// Check if the content was generated ok
		if(empty($content)) {
			return api_error_raise("Unable to generate the message content for destination '" . $destination . "'");
		}

		api_targets_add_extradata_single($campaignid, $target["targetkey"], "MessageContent", $content);

		if(!empty($tags["delay-" . $campaign])) {

			// Check if the delay is in the required format
			if(!preg_match("/^[0-9]{1,2}:[0-9]{2}$/", $tags["delay-" . $campaign])) {
				return api_error_raise("Unable to generate the message content for destination '" . $destination . "'");
			}

			$nextattempt = strtotime($tags["delay-" . $campaign] . ":00 Australia/Melbourne"); // Everything else

			api_targets_updatestatus($target["targetid"], "REATTEMPT", $nextattempt);
		}

	}

	return true;

}