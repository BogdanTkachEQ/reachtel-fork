<?php

function api_campaigns_hooks_destinationdelay($campaignid){

	if(is_numeric(api_campaigns_setting_getsingle($campaignid, "finishtime"))) return api_error_raise("Sorry, it looks as though this campaign has already finished");

	$tags = api_campaigns_tags_get($campaignid);

	$timezone = api_campaigns_gettimezone($campaignid)->getName();

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		// Ensure we only update ready or reattempt targets.
		if (!in_array($target["status"], array("READY", "REATTEMPT"))) continue;

		$state = "fallback";

		if (!empty($tags["delay-postcode"]) && ($field = api_data_merge_get_single($campaignid, $target["targetkey"], $tags["delay-postcode"]))) {

			if(preg_match("/^3/", $field)) $state = "VIC";
			elseif(preg_match("/^2/", $field)) $state = "NSW";
			elseif(preg_match("/^2[69]/", $field)) $state = "ACT";
			elseif(preg_match("/^7/", $field)) $state = "TAS";
			elseif(preg_match("/^5/", $field)) $state = "SA";
			elseif(preg_match("/^4/", $field)) $state = "QLD";
			elseif(preg_match("/^0?8/", $field)) $state = "NT";
			elseif(preg_match("/^6/", $field)) $state = "WA";
			else $state = "fallback";

		}

		if (($state == "fallback") && !empty($tags["delay-state"]) && ($field = api_data_merge_get_single($campaignid, $target["targetkey"], $tags["delay-state"]))) {

			if(preg_match("/^vic|victoria$/i", $field)) $state = "VIC";
			elseif(preg_match("/^nsw|new south wales$/i", $field)) $state = "NSW";
			elseif(preg_match("/^act|australian capital territory$/i", $field)) $state = "ACT";
			elseif(preg_match("/^tas|tasmania$/i", $field)) $state = "TAS";
			elseif(preg_match("/^sa|south australia$/i", $field)) $state = "SA";
			elseif(preg_match("/^qld|queensland$/i", $field)) $state = "QLD";
			elseif(preg_match("/^nt|northern territory$/i", $field)) $state = "NT";
			elseif(preg_match("/^wa|western australia$/i", $field)) $state = "WA";
			else $state = "fallback";

		}

		if (($state == "fallback") && isset($tags["delay-destination"]) && (api_campaigns_setting_getsingle($campaignid, "type") == "phone")) {

			if(preg_match("/^036/i", $target["destination"])) $state = "TAS";
			elseif(preg_match("/^03/i", $target["destination"])) $state = "VIC";
			elseif(preg_match("/^02[56][12]/i", $target["destination"])) $state = "ACT";
			elseif(preg_match("/^02/i", $target["destination"])) $state = "NSW";
			elseif(preg_match("/^07/", $target["destination"])) $state = "QLD";
			elseif(preg_match("/^08[78]9/", $target["destination"])) $state = "NT";
			elseif(preg_match("/^08[78]/", $target["destination"])) $state = "SA";
			elseif(preg_match("/^08/", $target["destination"])) $state = "WA";
			else $state = "fallback";

		}

		if(!empty($tags["delay-" . $state])){

			if(!preg_match("/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $tags["delay-" . $state])) {
				return api_error_raise("Sorry, the tag 'delay-" . $state . "' is not in a valid format");
			}

			$nextattempt = strtotime($tags["delay-" . $state] . ":00 " . $timezone);

			if(!$nextattempt) {
				return api_error_raise("Sorry, we failed to set the reattempt time for '". $tags["delay-" . $state] . ":00 " . api_campaigns_gettimezone($campaignid) . "'");
			}

			api_targets_updatestatus($target["targetid"], "REATTEMPT", $nextattempt);

		}

	}

	return true;

}