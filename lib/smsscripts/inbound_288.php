<?php

function inbound_288($array){

	$campaignid = 58716;

	if(isset($array["target"]) AND is_array($array["target"])) {

		$array["campaign"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");
		$to = api_campaigns_setting_getsingle($array["target"]["campaignid"], "smsreplyemail");

		if(preg_match("/now/i", $array["contents"])){

			$targetkey = api_misc_uniqueid();

			if($array["targetid"] = api_targets_add_single($campaignid, $array["from"], $targetkey)) {

				api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "source", "sms");
				api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "sourcecampaign", $array["campaign"]);
				api_campaigns_setting_set($campaignid, "status", "ACTIVE");

			}

		}
	}

	return true;

}