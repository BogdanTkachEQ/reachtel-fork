<?php

function inbound_53($array){

	// 61419227067
	$tags = api_sms_dids_tags_get(53);

	$email["to"]          = "ReachTEL Support <support@reachtel.com.au>";
	$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
	$email["subject"]     = "[ReachTEL] SMS received on demo service: " . $array['from'];
	$email["textcontent"] = "Hello,\n\nWe received an SMS on the demo service number:\n\n\n\nNumber: " . $array['from'] . "\n\nMessage: " . $array['contents'] . "\n\n";
	$email["htmlcontent"] = "Hello,\n\nWe received an SMS on the demo service number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\"><a href='https://morpheus.reachtel.com.au/admin_search.php?destination=" . $array['from'] . "'>" . $array['from'] . "</a></span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $array['contents'] . "</span></td></tr></table>";

	api_email_template($email);

	if(preg_match("/^advert/i", $array["contents"]))          $campaignid = 3702;
	elseif(preg_match("/^lions/i", $array["contents"]))       $campaignid = 3704;
	elseif(preg_match("/^broncos/i", $array["contents"]))     $campaignid = 3705;
	elseif(preg_match("/^demons/i", $array["contents"]))      $campaignid = 3706;
	elseif(preg_match("/^rabbitohs/i", $array["contents"]))   $campaignid = 3707;
	elseif(preg_match("/^community/i", $array["contents"]))   $campaignid = 3708;
	elseif(preg_match("/^election/i", $array["contents"]))    $campaignid = 3709;
	elseif(preg_match("/^satisfaction/i", $array["contents"]))$campaignid = 3712;
	elseif(preg_match("/^product/i", $array["contents"]))	  $campaignid = 3713;
	elseif(preg_match("/^debt/i", $array["contents"]))        $campaignid = 3714;
	elseif(preg_match("/^debit/i", $array["contents"]))       $campaignid = 3715;
	elseif(preg_match("/^staff/i", $array["contents"]))       $campaignid = 3716;
	elseif(preg_match("/^gaming/i", $array["contents"]))      $campaignid = 3730;
	elseif(preg_match("/^supermarket/i", $array["contents"])) $campaignid = 3754;
	elseif(preg_match("/^call[ ]?me/i", $array["contents"]))  $campaignid = 3758;
	elseif(preg_match("/^unionmeeting/i", $array["contents"])) $campaignid = 3926;
	elseif(preg_match("/^unionsurvey/i", $array["contents"])) $campaignid = 3925;
	elseif(preg_match("/^getup/i", $array["contents"])) 	  $campaignid = 4188;
	elseif(preg_match("/^utility/i", $array["contents"])) 	  $campaignid = 5437;
	elseif(preg_match("/^collingwood/i", $array["contents"])) $campaignid = 5649;
	elseif(preg_match("/^member/i", $array["contents"])) 	  $campaignid = 7825;
	elseif(preg_match("/^gws/i", $array["contents"])) 	  $campaignid = 8447;
	elseif(preg_match("/^ybrs/i", $array["contents"])) 	  $campaignid = 9659;
	elseif(preg_match("/^sponsor/i", $array["contents"])) 	  $campaignid = 12045;
	elseif(preg_match("/^surf/i", $array["contents"])) 	  $campaignid = 13598;
	else api_sms_apisend($array["from"], "Sorry. We didn't understand that request. Need help? You can always call us on 1800 42 77 06 for assistance.", 31);

	$api_endpoint = "https://api.reachtel.com.au/api?user={$tags['api_user']}&pass={$tags['api_password']}";

	if(!empty($campaignid) AND is_numeric($campaignid)){

		$targetkey = api_misc_uniqueid();

		if($campaignid != 3758){

			$message = "Thanks for that. The call will come through shortly. Don't forget to say hello when answering! For more info, simply reply with CALLME and we'll call you.";

			api_sms_apisend($array['from'], $message, 31);

			$sendafter = date("Y-m-d H:i:s", time() + 20);

			$contents = file_get_contents("{$api_endpoint}&action=addtarget&destination=" . $array['from'] . "&campaignid=" . $campaignid . "&targetkey=" . urlencode($targetkey) . "&priority=1&startonload=1&sendafter=" . urlencode($sendafter));

		} else 	file_get_contents("{$api_endpoint}&action=addtarget&destination=" . $array['from'] . "&campaignid=" . $campaignid . "&targetkey=" . urlencode($targetkey) . "&priority=1&startonload=1");

	}

	return true;

}