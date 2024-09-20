<?php

function inbound_700($message){

	// Disabling this inbound until further notice.
	return true;

	// 61488822623

	$tags = api_sms_dids_tags_get(700);

	if (! isset($message['target'])) {
		$sql = "SELECT t.targetid targetid FROM targets t, response_data rd WHERE (t.destination = ? OR t.destination = ?) AND t.targetid = rd.targetid AND t.campaignid = rd.campaignid AND rd.action = 'HANGUPSMS' ORDER BY rd.timestamp DESC LIMIT 1";
		$rs = api_db_query_read($sql, array($message["from"], $message["e164"]));
		
		if ($array = $rs->FetchRow()) {
			$target = api_targets_getinfo($array['targetid']);
			if ($target) $message["target"] = $target;
		}
	}

	if (isset($message['target']) && preg_match("/^cal+(\s+)?me?/i", $message["contents"])){

		$timezone = new DateTimeZone(isset($tags['timezone']) ? $tags['timezone'] : 'Australia/Melbourne');
		$currentDateTime = new DateTime('now', $timezone);

		if(
			($currentDateTime->format('N') < 7) /* Not on sunday */ &&
			($currentDateTime > new DateTime($tags['callme-openhour'], $timezone)) &&
			($currentDateTime < new DateTime($tags['callme-closehour'], $timezone))
		) {

			$campaignname = "Transurban-CallMe-" . date("FY");
			$campaignid = api_campaigns_checkorcreate($campaignname, $tags['callme-duplicate-campaign-id']);

			$targetkey = api_misc_uniqueid();

			$callmedest = api_campaigns_setting_getsingle($message["target"]["campaignid"], "callmedestination");
			if(! preg_match("/^[0-9]{10,11}$/", $callmedest)) $callmedest = api_campaigns_setting_getsingle($campaignid, "callmedestination");

			
			$elements = array(
				"date" => date("Y-m-d H:i:s"),
				"customernumber" => $message["e164"],
			);

			if (!empty($message["target"])) {
				$elements = array_merge(
					api_data_merge_get_all($message["target"]["campaignid"], $message["target"]["targetkey"]),
					$elements,
					array("customerrefnum" => $message["target"]["targetkey"])
				);
			}

			if($message["targetid"] = api_targets_add_single($campaignid, $callmedest, $targetkey, 1, $elements)) {

				if(!empty($message["target"])) {
					$message["target"]["campaign"] = api_campaigns_setting_getsingle($message["target"]["campaignid"], "name");
					api_data_responses_add($campaignid, 0, $message["targetid"], $targetkey, "sourcecampaign", $message["target"]["campaign"]);

				}

				api_data_responses_add($campaignid, 0, $message["targetid"], $targetkey, "source", "sms");
				api_campaigns_setting_set($campaignid, "status", "ACTIVE");

			}

		} else {

			api_sms_apisend($message["target"]["destination"], $tags["callme-afterhours-message"], $tags["callme-afterhours-apiaccount"]);

		}

	}

	return true;

}
