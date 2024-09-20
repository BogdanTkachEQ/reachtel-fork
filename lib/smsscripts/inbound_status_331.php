<?php

function inbound_status_331($array){

	if($array["status"] != "DELIVERED"){

		$email["to"]          = "settlements.hmfs@boqfinance.com.au";
		$email["subject"]     = "[ReachTEL] SMS delivery failure " . $array["to"];
		$email["textcontent"] = "Hello,\n\nThe following message could not be delivered:\n\n\n\nTime: " . date("H:i:s d M Y T", $array['supplierdate']) . "\n\nNumber: " . $array["to"] . "\n\nStatus: " . $array['status'] . "\n\nMessage: " . $array["contents"];
		$email["htmlcontent"] = "Hello,\n\nThe following message could not be delivered:\n\n<table style=\"width: 650px;\"><tr><td style=\"width: 100px;\">Time:</td><td><span style=\"color: red;\">" . date("H:i:s d M Y T", $array['supplierdate']) . "</span></td></tr><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $array["to"] . "</span></td></tr><tr><td style=\"width: 100px;\">Status:</td><td><span style=\"color: red;\">" . $array['status'] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" .  $array["contents"] . "</span></td></tr></table>\n\n";
		$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

		api_email_template($email);

	}

	return true;

}