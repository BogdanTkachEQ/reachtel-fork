<?php

function api_sms_send_supplier_2($from, $to, $content, $eventid = null, $options = array()){

	global $statsd;

	$starttime = microtime(true);

	$tags = api_sms_supplier_tags_get(2);

	$postfields = array("user" => $tags["username"],
		"pass" => $tags["password"],
		"ownnum" => $from,
		"number" => $to,
		"mess_id" => $eventid,
		"delivery" => 1,
		"type" => "LongSMS",
		"message" => $content);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $tags["host"]);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_TIMEOUT, SMS_TIMEOUT);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

        //execute post
	$result = curl_exec($ch);

	$info = curl_getinfo($ch);

	$statsd->timing("morpheus.sms.send.routomessaging", (microtime(true) - $starttime)*1000);

	if(($result === false) OR ($info["http_code"] != 200)) {
		$statsd->increment("morpheus.sms.send.routomessaging.errors." . $info["http_code"]);
		return api_error_raise("SMS send failed - Routo Messaging - " . curl_error($ch));
	}

	curl_close($ch);

	if (!preg_match("/^success$/i", $result)) return false;
	else return $eventid;

}