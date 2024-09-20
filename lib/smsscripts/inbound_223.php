<?php

function inbound_223($array){

	// 0439753102

	// Unity4

	// URL
	$details["url"] = "https://unity4syd2.unity4.com/u4system/Channels/sysChannelSMSReceive.aspx?MSISDN=" . $array["from"] . "&Content=" . urlencode($array["contents"]) . "&Shortcode=0439753102";

	api_queue_add("postback", $details);

	return true;

}