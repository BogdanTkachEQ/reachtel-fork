<?php

function api_campaigns_hooks_dnbcollectionspreprocess($campaignid){

	if(is_numeric(api_campaigns_setting_getsingle($campaignid, "finishtime"))) return api_error_raise("Sorry, it looks as though this campaign has already finished");

	if((api_campaigns_setting_getsingle($campaignid, "type") != "phone") OR (api_campaigns_setting_getsingle($campaignid, "groupowner") != 461)) return api_error_raise("Sorry, that doesn't look like a DnB campaign");

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		// Ensure we only update ready or reattempt targets.
		if(!in_array($target["status"], array("READY", "REATTEMPT"))) continue;

		$timezone = api_data_merge_get_single($campaignid, $target["targetkey"], "Timezone");

		if(empty($timezone) OR !is_numeric($timezone)) $nextattempt = strtotime("11:00:00 Australia/Melbourne"); // Empty or non-numeric
		elseif(preg_match("/^3/", $timezone)) $nextattempt = strtotime("08:00:00 Australia/Melbourne");   // VIC
		elseif(preg_match("/^2/", $timezone)) $nextattempt = strtotime("08:00:00 Australia/Melbourne");   // NSW
		elseif(preg_match("/^7/", $timezone)) $nextattempt = strtotime("08:00:00 Australia/Melbourne");   // TAS
		elseif(preg_match("/^5/", $timezone)) $nextattempt = strtotime("08:00:00 Australia/Melbourne");   // SA
		elseif(preg_match("/^4/", $timezone)) $nextattempt = strtotime("09:00:00 Australia/Melbourne");   // QLD
		elseif(preg_match("/^0?8/", $timezone)) $nextattempt = strtotime("09:30:00 Australia/Melbourne"); // NT
		elseif(preg_match("/^6/", $timezone)) $nextattempt = strtotime("11:00:00 Australia/Melbourne");   // WA
		else $nextattempt = strtotime("11:00:00 Australia/Melbourne"); // Everything else

		api_targets_updatestatus($target["targetid"], "REATTEMPT", $nextattempt);

	}

	api_targets_dedupe($campaignid);

	api_campaigns_tags_delete($campaignid, ["RETURNTIMESTAMP"]);

	return true;

}