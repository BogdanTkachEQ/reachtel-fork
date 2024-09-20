<?php

function api_sms_send_supplier_6($from, $to, $content, $eventid = null, $options = array()){

        global $statsd;

        $starttime = microtime(true);

	$tags = api_sms_supplier_tags_get(6);

	$options["username"] = $tags["username"];
	$options["password"] = $tags["password"];

        $xml_parts = api_sms_send_mblox_getxml($from, $to, $content, $eventid, $options);

        $mh = curl_multi_init();

        if(count($xml_parts) > 0)
        foreach($xml_parts as $key => $xml){

                $ch[$key] = curl_init();

                curl_setopt($ch[$key], CURLOPT_URL, $tags["host"]);
                curl_setopt($ch[$key], CURLOPT_POST, true);
                curl_setopt($ch[$key], CURLOPT_POSTFIELDS, "XMLDATA=" . urlencode($xml));
                curl_setopt($ch[$key], CURLOPT_TIMEOUT, SMS_TIMEOUT);
                curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
				if(defined("PROXY_EXTERNAL")) curl_setopt($ch[$key], CURLOPT_PROXY, PROXY_EXTERNAL);

                curl_multi_add_handle($mh, $ch[$key]);

        }

        do {
                $mrc = curl_multi_exec($mh, $active);
		curl_multi_select($mh);
        } while ($active > 0);

        while ($active && $mrc == CURLM_OK) {
		if (curl_multi_select($mh) == -1) usleep(100);
                if (curl_multi_select($mh) != -1) {
                        do {
                                $mrc = curl_multi_exec($mh, $active);
                        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
        }

        //execute post
        $result = curl_multi_getcontent($ch[0]);

        $info = curl_getinfo($ch[0]);

        $statsd->timing("morpheus.sms.send.mblox", (microtime(true) - $starttime)*1000);

        if(($result === false) OR ($info["http_code"] != 200)) {
                $statsd->increment("morpheus.sms.send.mblox.errors." . $info["http_code"]);
                return api_error_raise("SMS send failed - MBlox - " . curl_error($ch[0]));
        }

        curl_multi_close($mh);

        $xml_response = new SimpleXMLElement($result);

	if (!empty($xml_response) AND !empty($xml_response->NotificationResultList->NotificationResult->SubscriberResult->SubscriberResultText) AND ($xml_response->NotificationResultList->NotificationResult->SubscriberResult->SubscriberResultText == "OK")) return $eventid;
	elseif(!empty($xml_response) AND !empty($xml_response->NotificationResultList->NotificationResult->SubscriberResult->SubscriberResultText)) return api_error_raise("Invalid mBlox response - " . $xml_response->NotificationResultList->NotificationResult->SubscriberResult->SubscriberResultText);
	else return api_error_raise("Invalid mBlox response - " . $xml_response->NotificationResultList->NotificationResult->NotificationResultText);

}