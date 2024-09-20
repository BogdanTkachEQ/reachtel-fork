<?php

function inbound_status_10($array){

	if($array["status"] == "DELIVERED"){

		if(isset($array["target"]) AND is_array($array["target"])){

			if((time() - strtotime($array["sent"])) < 86400) return true;

			$array["campaign"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");

			$email["to"]          = "IFS Accounts <accounts@impactfs.com.au>, Mitos Lambino <mitosl@impactfs.co.nz>";
			$email["subject"]     = "[ReachTEL] SMS delivery notification from " . $array["to"];
			$email["textcontent"] = "Hello,\n\nFollowing is a delivery notification on your dedicated SMS number:\n\n\n\nTime: " . date("H:i:s d M Y T", $array['supplierdate']) . "\n\nNumber: " . $array["to"] . "\n\nStatus: " . $array['status'] . "\n\nUnique ID " . $array["target"]["targetkey"] . "\n\nCampaign: " . $array["campaign"] . "";
			$email["htmlcontent"] = "Hello,\n\nFollowing is a delivery notification on your dedicated SMS number:\n\n<table style=\"width: 650px;\"><tr><td style=\"width: 100px;\">Time:</td><td><span style=\"color: red;\">" . date("H:i:s d M Y T", $array['supplierdate']) . "</span></td></tr><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $array["to"] . "</span></td></tr><tr><td style=\"width: 100px;\">Status:</td><td><span style=\"color: red;\">" . $array['status'] . "</span></td></tr><tr><td style=\"width: 100px;\">Unique ID:</td><td><span style=\"color: red;\">" .  $array["target"]["targetkey"] . "</span></td></tr><tr><td style=\"width: 100px;\">Campaign:</td><td><span style=\"color: red;\">" . $array["campaign"] . "</span></td></tr></table>\n\n";
			$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

			api_email_template($email);

		}

		return true;

	}

	return true;

}