<?php

function inbound_status_60($array){

	if($array["status"] == "DELIVERED"){

		if(!empty($array["target"])) {

			$array["callmedestination"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "callmedestination");

                // If more than 72 hours has passed then ignore this
			if((time() - strtotime($array["sent"])) > 259200) return true;

                // If it's less than 15 minutes since being sent, ignore it
			if((time() - strtotime($array["sent"])) < 900) return true;

                // If it's in business hours then generate a call
			if(((date("N") <= 3) AND ((time() > strtotime(date("Y-m-d") . " 08:00:00 Australia/Sydney")) AND (time () < strtotime(date("Y-m-d") . " 20:00:00 Australia/Sydney")))) OR (date("N") <= 5 AND ((time() > strtotime(date("Y-m-d") . " 08:00:00 Australia/Sydney")) AND (time () < strtotime(date("Y-m-d") . " 17:00:00 Australia/Sydney"))))){

				$campaignname = "RadioRent-Cashfirst-" . date("Fy") . "-CallMe";
				$campaignid = api_campaigns_checkorcreate($campaignname, 9772);
				$targetkey = api_misc_uniqueid();

				$elements["date"] = date("Y-m-d H:i:s");
				if(!empty($array["callmedestination"])) $elements["callmedestination"] = $array["callmedestination"];

				if($array["targetid"] = api_targets_add_single($campaignid, $array["to"], $targetkey, 1, $elements)) {

					api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "source", "deliveryreport");
					api_campaigns_setting_set($campaignid, "status", "ACTIVE");

				}

			}

		}

		return true;

	}

	return true;

}