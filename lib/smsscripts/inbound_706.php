<?php

function inbound_706($message){
	// 61488822629

	$tags = api_sms_dids_tags_get(706);

	if(preg_match("/^({$tags['callme-keywords']})/i", $message["contents"])){
		// Check if it is a weekday during open hours
		if((date("N") < 6) AND ((time() > strtotime(date("Y-m-d") . " " . $tags['callme-weekday-openhour'])) AND (time () < strtotime(date("Y-m-d") . " " . $tags['callme-weekday-closehour'])))
		   OR (date("N") >= 6) AND ((time() > strtotime(date("Y-m-d") . " " . $tags['callme-weekend-openhour'])) AND (time () < strtotime(date("Y-m-d") . " " . $tags['callme-weekend-closehour'])))) {
		   	if (!api_campaigns_checkidexists($campaignid = $tags["campaignid"])) {
				return true;
			}

			$targetkey = api_misc_uniqueid();

			$elements = [
				"date" => date("Y-m-d H:i:s"),
				"customernumber" => $message["e164"],
				"customerrefnum" => $message["target"]["targetkey"],
			];

			if($message["targetid"] = api_targets_add_single($campaignid, $message["target"]["destination"], $targetkey, 1, $elements)) {
				if(!empty($message["target"])) {
					$message["target"]["campaign"] = api_campaigns_setting_getsingle($campaignid, "name");
					api_data_responses_add($campaignid, 0, $message["targetid"], $targetkey, "sourcecampaign", $message["target"]["campaign"]);

				}

				api_data_responses_add($campaignid, 0, $message["targetid"], $targetkey, "source", "sms");
				api_campaigns_setting_set($campaignid, "status", "ACTIVE");
			}
		} else {
			api_sms_apisend($message["from"], $tags["callme-afterhours-message"], $tags["callme-afterhours-apiaccount"]);
		}
	}

	return true;

}
