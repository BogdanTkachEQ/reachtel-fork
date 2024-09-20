<?php

function inbound_256($array){

	$campaignid = 24329;
	$message = "Simplytel: Our contact centre is currently closed. Please reply CALL ME between 8am - 8pm or call 1300 822 955 Thank you.";
	$userid = 1062;
	$callmedest = "1300822955";
	$email["to"] = "creditsupport@Simplytel.com.au";

	if(isset($array["target"]) AND is_array($array["target"])) {

		$array["campaign"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");
		$to = api_campaigns_setting_getsingle($array["target"]["campaignid"], "smsreplyemail");

	} else $array["target"]["targetkey"] = "Unknown/WebSMS";


	if(1 AND preg_match("/^call[ ]?me/i", $array["contents"])){

		if((date("N") < 6) AND ((time() > strtotime(date("Y-m-d") . " 08:00:00 Australia/Victoria")) AND (time () < strtotime(date("Y-m-d") . " 20:00:00 Australia/Victoria")))){

			$targetkey = api_misc_uniqueid();

			$elements["date"] = date("Y-m-d H:i:s");

			$callmedest = api_campaigns_setting_getsingle($array["target"]["campaignid"], "callmedestination");

			if(preg_match("/^[0-9]{10}$/", $callmedest)) $elements["callmedestination"] = $callmedest;
			else $elements["callmedestination"] = $callmedest;

			if($array["targetid"] = api_targets_add_single($campaignid, $array["from"], $targetkey, 1, $elements)) {

				api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "source", "sms");
				api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "sourcecampaign", $array["campaign"]);
				api_campaigns_setting_set($campaignid, "status", "ACTIVE");

			}

		} else api_sms_apisend($array["target"]["destination"], $message, $userid);

	} else {

		$email["subject"]     = "[ReachTEL] SMS Response from " . $array['from'];
		$email["textcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\nNumber: " . $array['from'] . "\n\nMessage: " . $array['contents'] . ".";
		$email["htmlcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $array['from'] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $array['contents'] . "</span></td></tr></table>";
		$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

		api_email_template($email);


	}

	return true;

}