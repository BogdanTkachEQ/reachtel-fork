<?php

function api_sms_send_supplier_3($from, $to, $content, $eventid = null, $options = array()){

        global $statsd;

	$starttime = microtime(true);

	$tags = api_sms_supplier_tags_get(3);

	$parameters = array("action" => "sendsms",
			    "user" => $tags["username"],
			    "password" => $tags["password"],
			    "from" => $from,
			    "to" => $to,
			    "maxsplit" => 10,
			    "clientcharset" => "ISO-8859-1",
			    "text" => $content,
			    "detectcharset" => 1);

        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $tags["host"] . "?" . http_build_query($parameters));
        curl_setopt($ch, CURLOPT_TIMEOUT, SMS_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

        //execute post
        $result = curl_exec($ch);

        $info = curl_getinfo($ch);

        $statsd->timing("morpheus.sms.send.smsglobal", (microtime(true) - $starttime)*1000);

        if(($result === false) OR ($info["http_code"] != 200)) {
                $statsd->increment("morpheus.sms.send.smsglobal.errors." . $info["http_code"]);
                return api_error_raise("SMS send failed - SMS Global - " . curl_error($ch));
        }

        curl_close($ch);

        if(preg_match("/^OK: ([0-9]+);.+SMSGlobalMsgID:([0-9]+)/i", $result, $matches)) return $matches[2];
        else return false;

}