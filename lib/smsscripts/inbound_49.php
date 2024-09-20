<?php

function inbound_49($array){

	if(isset($array["target"]) AND is_array($array["target"])) {

		$array["campaign"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");
		$array['refnumber'] = api_data_merge_get_single($array["target"]["campaignid"], $array["target"]["targetkey"], "refnumber");

		$array['smsreplyemail'] = api_data_merge_get_single($array["target"]["campaignid"], $array["target"]["targetkey"], "smsreplyemail");

		if(preg_match("/RRDialer$/i", $array["campaign"])) return true;

	} else {
		
		$array["targetkey"] = "Unknown/WebSMS";
		$array["campaign"] = "Unknown/WebSMS";
		$array["refnumber"] = "n/a";

	}

	if(preg_match("/^call[ ]?me/i", $array["contents"])){

		if(((date("N") <= 3) AND ((time() > strtotime(date("Y-m-d") . " 08:00:00 Australia/Sydney")) AND (time () < strtotime(date("Y-m-d") . " 20:00:00 Australia/Sydney")))) OR (date("N") <= 5 AND ((time() > strtotime(date("Y-m-d") . " 08:00:00 Australia/Sydney")) AND (time () < strtotime(date("Y-m-d") . " 17:00:00 Australia/Sydney"))))){

			$campaignname = "RadioRent-RadioRent-" . date("Fy") . "-CallMe";
			$campaignid = api_campaigns_checkorcreate($campaignname, 9672);

			$targetkey = api_misc_uniqueid();

			$elements["date"] = date("Y-m-d H:i:s");

			if($array["targetid"] = api_targets_add_single($campaignid, $array["from"], $targetkey, 1, $elements)) {

				api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "source", "sms");
				api_campaigns_setting_set($campaignid, "status", "ACTIVE");

			}

		} else {

			$message = "Our Office is currently closed. Please call between 8am-8pm EST Monday-Wednesday or 8am-5pm EST Thursday-Friday.";
			api_sms_apisend($array['from'], $message, 43);
		}


		return true;

	} else {

		if($array["campaign"] == "Unknown/WebSMS") $email["to"] = api_sms_dids_tags_get(49, "smstoemail-catchall");
		elseif($array['smsreplyemail'] != "") $email["to"] = $array["smsreplyemail"];
		else $email["to"]	    = "dialercollections@radio-rentals.com.au";


		$email["subject"]     = "[ReachTEL] SMS Response from " . $array['from'];
		$email["textcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\nNumber: " . $array['from'] . "\n\nCampaign: " . $array["campaign"] . "\n\nRef Number: " . $array["refnumber"] . "\n\nMessage: " . $array['contents'] . "\n\n";
		$email["htmlcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $array['from'] . "</span></td></tr><tr><td style=\"width: 100px;\">Campaign:</td><td><span style=\"color: red;\">" . $array['campaign'] . "</span></td></tr><tr><td style=\"width: 100px;\">Ref Number:</td><td><span style=\"color: red;\">" . $array['refnumber'] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $array['contents'] . "</span></td></tr></table>";
		$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

		api_email_template($email);

		return true;
	}

}

?>
