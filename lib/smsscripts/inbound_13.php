<?php

function inbound_13($array){

	$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"]     = "[ReachTEL] SMS Response from " . $array['from'];

	if(isset($array["target"]) AND is_array($array["target"])) {

		api_data_responses_add($array["target"]["campaignid"], $array["eventid"], $array["target"]["targetid"], $array["target"]["targetkey"], "RESPONSE", $array['contents']);
		$array["groupowner"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "groupowner");
		$smsreplyemail = api_campaigns_setting_getsingle($array["target"]["campaignid"], "smsreplyemail");
		$array["campaign"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");

	} else return true;


	if(preg_match("/^no/i", trim($array['contents'])) OR preg_match("/^stop/i", trim($array['contents'])) OR preg_match("/^n0/i", trim($array['contents'])) OR preg_match("/^opt out/i", trim($array['contents'])) OR preg_match("/^optout/i", trim($array['contents']) OR preg_match("/^do not text/i", trim($array['contents'])))){

		api_data_responses_add($array["target"]["campaignid"], 0, $array["target"]["targetid"], $array["target"]["targetkey"], "OPTOUT", "YES");
		api_restrictions_donotcontact_add("phone", $array["from"], api_campaigns_setting_getsingle($array["target"]["campaignid"], "donotcontactdestination"));
		return true;

	} elseif(!empty($smsreplyemail)){

		$email["to"]          = $smsreplyemail;

		$email["textcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\nNumber: " . $array['from'] . "\n\nCampaign: " . $array["campaign"] . "\n\nMessage: " . $array['contents'] . "\n\n";
		$email["htmlcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $array['from'] . "</span></td></tr><tr><td style=\"width: 100px;\">Campaign:</td><td><span style=\"color: red;\">" . $array['campaign'] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $array['contents'] . "</span></td></tr></table>";

		api_email_template($email);

		return true;

	} else {

		return true;

		$array['campaignname'] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");

		$email["to"]          = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["textcontent"] = "Hello,\n\nFollowing is an unhandled response on your dedicated SMS number:\n\n\n\nCampaign: " . $array['campaignname'] . "\n\nNumber: " . $array['from'] . "\n\nMessage: " . $array['contents'] . "\n\nUnique ID: " . $array["target"]["targetkey"] . ".";
		$email["htmlcontent"] = "Hello,\n\nFollowing is an unhandled response on your dedicated SMS number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">Campaign:</td><td><span style=\"color: red;\">" . $array['campaignname'] . "</span></td></tr><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $array['from'] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $array['contents'] . "</span></td></tr><tr><td style=\"width: 100px;\">Unique ID:</td><td><span style=\"color: red;\">" . $array["target"]['targetkey'] . "</span></td></tr><tr><td colspan='2'>&nbsp;<br /><a href=\"https://bcast.reachtel.com.au/unsubscribe.php?tid=" . api_misc_crypt_safe($array['target']['targetid']) . "&notrack=1\">Add to DNC list</a></td></tr></table>";

		api_email_template($email);

		return true;
	}

}