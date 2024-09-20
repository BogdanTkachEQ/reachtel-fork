<?php

function api_sms_send_supplier_5($from, $to, $content, $eventid = null, $options = array()){

        global $statsd;

        $starttime = microtime(true);

	if(!empty($array["eventidprefix"])) $messageid = $array["eventidprefix"] . $eventid;
	else $messageid = $eventid;

	if(preg_match("/^rc[0-9]{4}$/", $from)) $rateCode = $from;
	else $rateCode = "rc3105";

	$tags = api_sms_supplier_tags_get(5);

        $fields = array("userId" => $tags["username"],
                        "password" => $tags["password"],
                        "body" => $content,
                        "to" => $to,
                        "messageId" => $messageid,
                        "rateCode" => $rateCode,
                        "fragmentationLimit" => $tags["fragmentation"]);

        //open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $tags["host"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_TIMEOUT, SMS_TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

        //execute post
        $result = curl_exec($ch);

        if ($result === false) {
            return api_error_raise("SMS send failed - Bulletin - " . curl_error($ch));
        }

        $info = curl_getinfo($ch);

        if ($info["http_code"] != 204) {
            $statsd->increment("morpheus.sms.send.bulletin.errors." . $info["http_code"]);
            return api_error_raise(
                "SMS send failed - Bulletin. HTTP code " . $info["http_code"] . " returned (" .
                $result . ")"
            );
        }

        curl_close($ch);

        $statsd->timing("morpheus.sms.send.bulletin", (microtime(true) - $starttime)*1000);

        return $eventid;

}
