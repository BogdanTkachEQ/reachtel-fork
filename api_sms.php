<?php

$blocked = array("61400000000", "61411111111");

// Send SMS

function api_sms_generatemessage($message, $settings = false){

	// Send a message with all the preparation

	if(!is_numeric($message["campaignid"])){
		return false;
	}

	if(is_array($settings)) {
		$message["smsdid"] = $settings["smsdid"];
		$message["content"] = $settings["content"];
	} else {
		$message["smsdid"] = api_campaigns_setting_getsingle($message["campaignid"], "smsdid");
		$message["content"] = api_campaigns_setting_getsingle($message["campaignid"], "content");
	}

	if($message["targetkey"]) {

		$message["content"] = api_data_merge_process($message["content"], $message["targetid"]);

		if($message["content"] === FALSE) return false;

		if(isset($settings["shortenurls"]) && ($settings["shortenurls"] == "on")) {
			$message["content"] = api_misc_linkshorten_findandreplace($message["content"], $message["targetid"]);
			$message["content"] = preg_replace('#@(https?://)#i', '$1', $message["content"]);

			if(!$message["content"]) return false;
		}

	}

	$eventid = api_misc_uniqueid();

	api_queue_add("sms", array("eventid" => $eventid, "campaignid" => $message["campaignid"], "region" => $settings["region"], "targetkey" => $message["targetkey"], "targetid" => $message["targetid"], "didid" => $message["smsdid"], "destination" => $message["destination"], "message" =>  $message["content"]), null, 0, array("priority" => "low"));
	api_campaigns_update_lastsend($message['campaignid']);
	return $eventid;

}

function api_sms_apisend($destination, $message, $userid, $sendafter = null, $uid = null, $international_sms_enabled = false){

	$region = api_users_setting_getsingle($userid, "region");

	if(!$region) $region = DEFAULT_REGION;

	$phonenumber = $destination;
	$destination = api_data_numberformat($phonenumber, $region);

	$destination_is_international = false;
	if (!$destination && $international_sms_enabled) {
		$destination_is_international = true;
		$destination = api_data_numberformat($phonenumber, CAMPAIGN_SMS_REGION_INTERNATIONAL);
	}

	// Match all other mobile destinations to the "othermobile" category. This includes destinations that are "fixedlineormobile"
	if(!is_array($destination) || !preg_match("/mobile/", $destination["type"])) return api_error_raise("Sorry, not a valid destination");
	elseif(!in_array($destination["type"], array("aumobile", "nzmobile", "sgmobile", "gbmobile", "phmobile"))) $destination["type"] = "othermobile";

	if((strlen($message) > 1530) OR empty($message)) return api_error_raise("Message field too long or empty");

	if(!api_session_checkuserid($userid)) return api_error_raise("SMSAPI_AUTHERROR", "Tried to send using a disabled account. UserID=" . $userid);

	$smsdid = api_users_setting_getsingle($userid, "smsapidid");

	if(!api_sms_dids_checkidexists($smsdid)) return api_error_raise("Sorry, that SMS DID doesn't exist");

	if(!empty($sendafter)){

		$sendafter = strtotime($sendafter);

		if($sendafter == FALSE)	return api_error_raise("Incorrectly formatted sendafter variable");
		else $sendafter = date("Y-m-d H:i:s", $sendafter);

	}

	if(strlen($uid) > 100) return api_error_raise("UID paremeter too long");

	$eventid = api_misc_uniqueid();

	$sms = api_queue_add(
		"sms",
		[
			"didid" => $smsdid,
			"eventid" => $eventid,
			"region" => ($destination_is_international ? CAMPAIGN_SMS_REGION_INTERNATIONAL : strtoupper($destination["country"])),
			"destination" => ($destination_is_international ? "+{$destination['destination']}" : $destination["fnn"]),
			"message" => $message,
		],
		$sendafter
	);

	if($sms !== FALSE){

		if ($destination_is_international) {
			api_error_audit(CAMPAIGN_SMS_REGION_INTERNATIONAL, "API sms queued {$phonenumber}, eventid={$eventid}, did={$smsdid}", $userid);
		}

		$len = strlen($message);

		if($len <= 160) $messageunits = 1;
		else {
			if (fmod($len/153, 1) != 0) $messageunits = ceil($len/153);
			else $messageunits = ($len/153);
		}

		$sql = "INSERT INTO `sms_api_mapping` (`userid`, `billingtype`, `rid`, `uid`, `messageunits`, `billing_products_region_id`) VALUES (?, ?, ?, ?, ?, ?)";
		api_db_query_write($sql, array($userid, "sms" . $destination["type"], $eventid, $uid, $messageunits, $destination['billing_region_id']));

		return $eventid;

	} else return api_error_raise("Failed to add SMS to queue");

}

function api_sms_out($from, $destination, $message, $eventid = null, $userid = null, $options = array()){

	global $blocked;

	if($eventid == null) $eventid = api_misc_uniqueid();

	if(preg_match("/^04[0-9]{8}$/", $from)) $from = "61" . substr($from, 1);

	if(in_array($destination["destination"], $blocked)) {

		$sql = "UPDATE `sms_out` SET `supplier` = ?, `supplierid` = ? WHERE `id` = ?";
		$rs = api_db_query_write($sql, array(0, $eventid, $eventid));

		$dr = array("supplier" => 0, "supplieruid" => $eventid, "status" => "UNDELIVERED", "code" => "0A", "supplierdate" => time());

		api_queue_add("smsdr", $dr);

		return $eventid;

	}

	$providers = api_sms_fetch_providers($destination, isset($options['smsdidid']) ? $options['smsdidid'] : null);

	if(!is_array($providers)) return api_error_raise("Sorry, no available SMS suppliers were found");

	foreach($providers as $provider){

		try {
			$filename = __DIR__ . "/lib/SMS/supplier_" . $provider . ".php";
			if (file_exists($filename)) {
				include_once($filename);
				$function = "api_sms_send_supplier_" . $provider;

				if(!is_callable($function)) continue;
			} else {
				$service = \Services\Suppliers\SmsServiceFactory::getSmsService($provider);
				if (!\Services\Suppliers\Utils\SmsUtil::isSendable($service)) {
					continue;
				}
				$function = function($from, $to, $content, $eventid, $options) use ($service, $provider) {
					global $statsd;
					$supplier_name = api_sms_supplier_setting_getsingle($provider, SMS_SUPPLIER_SETTING_NAME);
					if ($supplier_name) {
						$supplier_name = strtolower(str_replace(' ', '_', $supplier_name));
					}
					try {
						$sms = new \Models\Sms();
						$sms
							->setFrom($from)
							->setTo($to)
							->setContent($content);
						$starttime = microtime(true);
						$supplieruid = $service->sendSms($sms, $eventid);
						if ($supplier_name) {
							$statsd->timing('morpheus.sms.send.' . $supplier_name, (microtime(true) - $starttime) * 1000);
						}
						return $supplieruid;
					} catch (Exception $e) {
						if ($supplier_name) {
							$statsd->increment('morpheus.sms.send.' . $supplier_name . '.errors');
						}
						api_error_raise($e->getMessage());
						return false;
					}
				};
			}

		} catch (Exception $e){

			continue;

		}

		$result = $function($from, $destination["destination"], $message, $eventid, $options);

		if($result) {

			api_sms_supplier_setting_set($provider, "lastsms", microtime(true));
			api_sms_supplier_increment($provider, "counter");

			if(is_array($result)){

				$sql = "UPDATE `sms_out` SET `supplier` = ?, `supplierid` = ? WHERE `id` = ?";
				$rs = api_db_query_write($sql, array($provider, trim($result["supplierid"]), $eventid));

				if(!empty($result["dr"])) api_queue_add("smsdr", $result["dr"]);

			} else {

				$sql = "UPDATE `sms_out` SET `supplier` = ?, `supplierid` = ? WHERE `id` = ?";
				$rs = api_db_query_write($sql, array($provider, trim($result), $eventid));

			}

			return $eventid;

		} else {

			api_misc_audit("SMS_SEND_ERROR", "Failed to send; Supplier=" . $provider . "; Destination=" . $destination["destination"] . "; Result=" . serialize($result));

		}
	}

	return false;

}

function api_sms_fetch_providers(array $formatted_destination_details, $accountid = null) {
	$capabilities = [$formatted_destination_details['type']];
	$options = ["destination" => $formatted_destination_details];

	if ($accountid) {
		$traffic_onshore_setting = api_sms_dids_setting_getsingle($accountid, SMS_DID_SETTING_USE_ON_SHORE_PROVIDER);
		if ($traffic_onshore_setting == SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_REQUIRED) {
			$capabilities[] = SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE;
		} elseif ($traffic_onshore_setting == SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_PREFERRED) {
			$options['sort_by_capabilities'] = [SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE];
		}
	}

	$i = 0;

	do{
		$providers = api_sms_supplier_select($capabilities, $options);

		$i++;

		if (is_array($providers) && count($providers)) {
			break;
		}

		usleep(100000);

	} while ($i <= 5);

	return $providers;
}

function api_sms_get_on_shore_only_providers_options() {
	return [
		SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_NOT_REQUIRED,
		SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_REQUIRED,
		SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_PREFERRED
	];
}

function api_sms_out_status($supplieruid, $status, $supplierdate, $id = null){

	$sql = "INSERT INTO `sms_out_status` (`id`, `timestamp`, `status`) VALUES (?, FROM_UNIXTIME(?), ?)";
	$rs = api_db_query_write($sql, array($id, $supplierdate, $status));

	if($rs === false) return false;

	// Delivery receipt post back handling

	// For a particular message id, see if the user that sent it has a value set to receive post backs
	$sql = "SELECT `key_store`.`value`, `key_store`.`id` FROM `sms_out`, `key_store` WHERE `sms_out`.`id` = ? AND `sms_out`.`userid` = `key_store`.`id` AND `key_store`.`type` = ? AND `key_store`.`item` =  ?";
	$rs = api_db_query_read($sql, array($id, "USERS", USER_SETTING_RESTPOSTBACK_URL));

	if($rs AND ($rs->RecordCount() > 0) AND $rs->Fields("value")) {

		$date = new DateTime('@' . $supplierdate);
		$details = [
			"url" => $rs->Fields("value"),
			"user_id" => $rs->Fields("id"),
			"payload" => [
				"sms_messages" => [
					[
						"id" => api_misc_crypt_safe($id),
						"receipts" => [$status => $date->format(DateTime::RFC2822)],
					],
				],
			],
		];

		api_queue_add("restpostback", $details);
	}

	return true;

}

function api_sms_send($accountid, $to, $content, $eventid = null){

	global $blocked;

	if($eventid == null) $eventid = api_misc_uniqueid();

	if(!is_numeric($accountid)) return api_error_raise("Sorry, that is not a valid SMS account");

	$from = api_sms_dids_setting_getsingle($accountid, SMS_DID_SETTING_NAME);

    $destination = $to;
	if(!is_array($to)) $to = api_data_numberformat($to, "AU");

	if (!is_array($to)) {
		return api_error_raise("Sorry, '{$destination}' is not a valid SMS destination");
	}

	if(preg_match("/^04[0-9]{8}$/", $from)) $from = "61" . substr($from, 1);

	if(in_array($to["destination"], $blocked)) {

		api_sms_send_log($eventid, $eventid, $accountid, $to["destination"], $content, 0);

		$dr = array("supplier" => 0, "supplieruid" => $eventid, "status" => "UNDELIVERED", "code" => "0A", "supplierdate" => time());

		api_queue_add("smsdr", $dr);

		return $eventid;

	}

	$providers = api_sms_fetch_providers($to, $accountid);

	if ($providers) {
		foreach($providers as $provider){

			try {
				$filename = __DIR__ . "/lib/SMS/supplier_" . $provider . ".php";
				if (file_exists($filename)) {
					include_once($filename);
					$function = "api_sms_send_supplier_" . $provider;

					if(!is_callable($function)) continue;
				} else {
					$service = \Services\Suppliers\SmsServiceFactory::getSmsService($provider);
					if (!\Services\Suppliers\Utils\SmsUtil::isSendable($service)) {
						continue;
					}
					$function = function($from, $to, $content, $eventid) use ($service, $provider) {
						global $statsd;
						$supplier_name = api_sms_supplier_setting_getsingle($provider, SMS_SUPPLIER_SETTING_NAME);
						if ($supplier_name) {
							$supplier_name = strtolower(str_replace(' ', '_', $supplier_name));
						}
						try {
							$sms = new \Models\Sms();
							$sms
								->setFrom($from)
								->setTo($to)
								->setContent($content);
							$starttime = microtime(true);
							$supplieruid = $service->sendSms($sms, $eventid);
							if ($supplier_name) {
								$statsd->timing('morpheus.sms.send.' . $supplier_name, (microtime(true) - $starttime) * 1000);
							}
							return $supplieruid;
						} catch (Exception $e) {
							if ($supplier_name) {
								$statsd->increment('morpheus.sms.send.' . $supplier_name . '.errors');
							}
							api_error_raise($e->getMessage());
							return false;
						}
					};
				}
			} catch (Exception $e){

				api_error_audit("SMS_NO_SUPPLIER", "Failed to include supplier " . $provider);
				continue;

			}

			// Detect if the SMS content contains windows-1252 characters and if so, convert it to UTF-8 as expected
			if(!preg_match('/^\\X*$/u', $content)) {
				$content = iconv("CP1252", "UTF-8//TRANSLIT", $content);
			}

			// MOR-474 Replace all Windows based CR+LF with just a regular LF
			$content = str_replace("\r\n", "\n", $content);

			// Send the SMS
			$result = $function($from, $to["destination"], $content, $eventid);

			if($result) {

				if (isset($to['type']) && 'othermobile' === $to['type']) {
					api_error_audit(
						CAMPAIGN_SMS_REGION_INTERNATIONAL,
						"SMS sent to {$to['destination']} ({$to['countryname']}), eventid={$eventid}, did={$accountid}"
					);
				}

				api_sms_supplier_setting_set($provider, "lastsms", microtime(true));
				api_sms_supplier_increment($provider, "counter");

				if(is_array($result)){

					api_sms_send_log($result["supplierid"], $eventid, $accountid, $to["destination"], $content, $provider);

					if(!empty($result["dr"])) api_queue_add("smsdr", $result["dr"]);

					return $eventid;

				} else return api_sms_send_log($result, $eventid, $accountid, $to["destination"], $content, $provider);

			} else {

				api_misc_audit("SMS_SEND_ERROR", "Failed to send; Supplier=" . $provider . "; Destination=" . $to["destination"] . "; Result=" . serialize($result));
				continue;

			}

		}
	}

	/*
	 * At this stage, SMS was not sent for different reasons:
	 *   - No suppliers found: config issue
	 *   - Suppliers found: All failed
	 */

	// prepare error message depending on actual issue
	if ($providers) {
		$error = "Sorry, could not send SMS to '{$to["destination"]}' with selected providers: " . implode(',', $providers);
	} else {
		$error = "Sorry, no upstream SMS suppliers found for '{$to["destination"]}'";
	}
	$error .= "; country={$to['countryname']}; eventid={$eventid}; did={$accountid}";

	return api_error_raise($error);
}

function api_sms_send_log($supplieruid, $eventid, $sms_account, $to, $content, $supplier = null){

	$sql = "INSERT INTO `sms_sent` (`timestamp`, `supplier`, `supplieruid`, `eventid`, `sms_account`, `to`, `contents`) VALUES (NOW(), ?, ?, ?, ?, ?, ?)";
	$rs = api_db_query_write($sql, array($supplier, trim($supplieruid), $eventid, $sms_account, $to, $content));

	if(empty($rs)) api_error_audit("SMS_LOG_ERROR", "Destination: " . $to . "; Supplier: " . $supplier);

	return $eventid;

}

function api_sms_receive_sms2email($smsdid, $received){

	if(!api_sms_dids_checkidexists($smsdid)) return api_error_raise("Sorry, that is not a valid SMS DID");

	// TODO Deprecate use to tags here once migrated
	$routetouser = ((api_sms_dids_tags_get($smsdid, "sms2email-routetouser") == "true") OR (api_sms_dids_setting_getsingle($smsdid, "sms2emailroutetouser") == "enabled")) ? true : false;

	$recent = api_sms_gethistory($smsdid, $received["from"], 90);

	$textcontent = "";
	$htmlcontent = "";

	if(!is_array($recent) OR !count($recent)){

		$textcontent .= "No messages found";
		$htmlcontent .= "<span style='font-style: italic;'>No messages found</span>";

	} else {

		foreach($recent as $timestamp => $messages) {
			foreach($messages as $message){

				// Check if the "route to user" function is active. If so, and we haven't already set an email address, attempt to get the user's email address.
				if($routetouser && empty($emailto) && !empty($message["userid"])) {

					// Check that the user is active and has an email address
					if(api_users_setting_getsingle($message["userid"], "status") && api_users_setting_getsingle($message["userid"], "emailaddress")) {
						$emailto = api_users_setting_getsingle($message["userid"], "emailaddress");
					}
				}

				// We only want to display messages from the last 7 days. If we get anything older, just continue
				if($timestamp < (time() - (7 * 86400))) continue;

				$textcontent .= ucfirst($message["direction"]) . " at " . date("D, j M H:i T", $timestamp) . ":\n" . $message["contents"] . "\n\n";
				$htmlcontent .= "<span style='font-style: italic;'>" . ucfirst($message["direction"]) . " " . date("D, j M H:i T", $timestamp) . ":</span>\n" . htmlentities($message["contents"]) . "\n\n";

			}
		}

	}

	if(empty($emailto)) {

		// TODO Deprecate use to tags here once migrated
		$emailto = api_sms_dids_tags_get($smsdid, "sms2email-destination");

		if(empty($emailto)) $emailto = api_sms_dids_setting_getsingle($smsdid, "sms2emaildestination");

		if(empty($emailto)) return api_error_raise("No sms2email-destination to send email to");
	}

	$to = api_data_numberformat(api_sms_dids_setting_getsingle($smsdid, "name"));

	$email["to"]	      = $emailto;
	$email["Message-Id"]  = "<" . microtime(true) . "-" . $received["e164"] . "-" . $smsdid . "@sms.ReachTEL.com.au>";
	$email["from"]        = $received["from"] . " <" . $received["from"] . "@sms.ReachTEL.com.au>";
	$email["subject"]     = "[ReachTEL] SMS Response from " . $received['from'];
	$email["textcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\nFrom: " . $received['from'] . "\n\nTo: " . $to["fnn"] . "\n\nMessage: " . htmlspecialchars($received['contents']) . "\n\nTo send a response, simply reply to this message and put your response in the subject line.\n\nConversation history for the last seven days: " . $textcontent;
	$email["htmlcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">From:</td><td><span style=\"color: red;\">" . $received['from'] . "</span></td></tr><tr><td style=\"width: 100px;\">To:</td><td><span style=\"color: red;\">" . $to["fnn"] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $received['contents'] . "</span></td></tr></table>\n\nTo send a response, simply reply to this message and put your response in the subject line.\n\n<span style='text-decoration: underline;'>Conversation history for the last seven days:</span>\n\n" . $htmlcontent;

	api_email_template($email);

	return true;

}

function api_sms_sm2email_message_has_exclusion_filters($sms_account, $contents) {
    $exclusionKeywords = explode('|', (api_sms_dids_setting_getsingle($sms_account, 'sms2emailexclusionfilters') ? : ''));

    foreach ($exclusionKeywords as $keyword) {
        if ($keyword && preg_match("/" . preg_quote($keyword) . "/i", $contents)) {
            return true;
        }
    }

    return false;
}

  // Providers

function api_sms_send_mblox_getxml($from, $to, $content, $eventid = null, $options = array()){

	// Some characters aren't supported so replace them with appropriate characters
	$content = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $content);

	$find = array('[', ']', '\t');
	$replace = array('(', ')', ' ');

	$content = str_replace($find, $replace, $content);

	if(strlen($content) <= 160) $parts[] = $content;
	else $parts = str_split($content, 153);

	$xml_parts = array();

	foreach($parts as $key => $part){

		$xml = new SimpleXMLElement("<NotificationRequest />");
		$xml->addAttribute("Version", 3.5);

		$notification_header = $xml->addChild("NotificationHeader");
		$notification_header->PartnerName = $options["username"];
		$notification_header->PartnerPassword = $options["password"];

		$notification_list = $xml->addChild("NotificationList");

		if($key == 0) $notification_list->addAttribute("BatchID", $eventid);

		$notification = $notification_list->addChild("Notification");
		$notification->addAttribute("SequenceNumber", 1);
		$notification->addAttribute("MessageType", "SMS");

		$notification->Message = $part;
		$notification->Profile = -1;
		if(count($parts) > 1) $notification->Udh = ":05:00:03:cc:" . str_pad(count($parts), 2, "0", STR_PAD_LEFT) . ":" . str_pad($key+1, 2, "0", STR_PAD_LEFT);
		$notification->SenderID = $from;

		if(isset($options["expiry"]) AND is_numeric($options["expiry"])) $notification->ExpireDate = gmdate("mdHi", time() + ($options["expiry"] * 60));

		if(is_numeric($from)) $type = "Numeric";
		else $type = "Alpha";

		$notification->SenderID->addAttribute("Type", $type);

		$subscriber = $notification->addChild("Subscriber");

		$subscriber->SubscriberNumber = $to;

		$xml_parts[] = $xml->asXML();
	}

	return $xml_parts;

}

// Receive SMS

function api_sms_receive($received, $supplieruid, $sms_account, $from, $contents, $e164 = null){

	if(!is_numeric($sms_account)) return false;
	if(empty($from)) return false;
	if($received < (time() - 604800)) return false;

	if($e164 == null) api_error_audit("SMS_NO_E164", "SupplierUID: " . $supplieruid, null);

	$received = array("received" => $received,
		"supplieruid" => $supplieruid,
		"from" => $from, // Senders Number
		"e164" => $e164, // Senders e164 Number
		"eventid" => 0,
		"sms_account" => $sms_account,
		"contents" => $contents);

	// Capture QOS SMS's 
	if(($received['from'] == "ReachTEL") AND (preg_match("/^RT-:/", $received['contents']))) {

		$message = api_misc_decrypt(base64_decode(substr($received['contents'], 4)));

		if(preg_match("/^RT-:([0-9]+):([A-Z]+):([0-9]+)$/", $message, $matches)){

			$delay = time() - $matches[1];

			if($delay > 120){

				$email["to"]          = "ReachTEL Support <support@reachtel.com.au>";
				$email["subject"]     = "[ReachTEL] Outage Report - " . date("Y-m-d H:i:s");
				$email["textcontent"] = "Delayed SMS finally received - " . $delay . " seconds";
				$email["htmlcontent"] = "Delayed SMS finally received - " . $delay . " seconds";
				api_email_template($email);
			}

			// This has to be done to avoid race condition where the the sms would be received before the setting QOSSMSSENT is even updated
			$i = 0;
			do {
				$i++;
				if (api_system_setting_getsingle("QOSSMSSENT") !== false) {
					return api_system_setting_delete_single("QOSSMSSENT");
				}
				sleep(1);
			} while ($i <= 2);

			return api_error_raise('The setting QOSSMSSENT not set by qos script.');
		}

		return true;
	}

	$last_sms_has_campaign = api_sms_last_sent_has_campaign($sms_account, $e164) ;
	$targetid = api_targets_findrecentsms($e164, $sms_account);

	if($targetid != false) $received["target"] = api_targets_getinfo($targetid);

	// SMS DID Campaign integration
	if($last_sms_has_campaign && isset($received["target"]["targetid"]) && api_sms_dids_setting_getsingle($sms_account, "linktocampaign") == "on") {

		// Add response to campaign
		api_data_responses_add($received["target"]["campaignid"], 0, $received["target"]["targetid"], $received["target"]["targetkey"], "RESPONSE", $contents);

		// Opt out handling
		if(preg_match("/^(stop|n0|opt out|optout|do not text|unsubscribe)/i", trim($contents))){

			api_data_responses_add($received["target"]["campaignid"], 0, $received["target"]["targetid"], $received["target"]["targetkey"], "OPTOUT", "YES");
			api_restrictions_donotcontact_add("phone", $received["e164"], api_campaigns_setting_getsingle($received["target"]["campaignid"], "donotcontactdestination"));

		} else {

			$email["to"] = api_campaigns_setting_getsingle($received["target"]["campaignid"], "smsreplyemail");

			// Reply email forwarding
			if(!empty($email["to"])){

				$received["target"]["campaign"] = api_campaigns_setting_getsingle($received["target"]["campaignid"], "name");

				$email["subject"] = "[ReachTEL] SMS Response from " . $received['from'];
				$email["textcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\nNumber: " . $received["from"] . "\n\nCampaign: " . $received["target"]["campaign"] . "\n\nMessage: " . $received["contents"] . "\n\n";
				$email["htmlcontent"] = "Hello,\n\nFollowing is a response on your dedicated SMS number:\n\n\n\n<table style=\"width: 400px;\"><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $received["from"] . "</span></td></tr><tr><td style=\"width: 100px;\">Campaign:</td><td><span style=\"color: red;\">" . $received["target"]["campaign"] . "</span></td></tr><tr><td style=\"width: 100px;\">Message:</td><td><span style=\"color: red;\">" . $received["contents"] . "</span></td></tr></table>";

				api_email_template($email);

			}
		}
	} elseif(
		api_sms_dids_setting_getsingle($sms_account, "sms2email") == "enabled" &&
		!api_sms_sm2email_message_has_exclusion_filters($sms_account, $contents)
	){
		api_sms_receive_sms2email($sms_account, $received);
	}

	// Make sure that the sender and destination address aren't the same. We don't want to create an infinite loop
	$smsloop = ($received["e164"] == api_sms_dids_setting_getsingle($sms_account, "name")) ? true : false;

	// Log the sms loop condition
	if ($smsloop) {

		api_misc_audit("SMS_LOOP", "SMS loop detected. Sender=" . $received["e164"]);
	}

	if (!$smsloop) {
		if (api_sms_dids_setting_getsingle($sms_account, "enablecallme") == "on") {
			require_once(__DIR__ . "/lib/smsscripts/" . 'handlers/generic_callme.php');
			if (!handle_generic_callme($received)) {
				return false;
			}
		}

		$optouttodnc = api_sms_dids_setting_getsingle($sms_account, "optouttodnc");
		if ($optouttodnc && is_numeric($optouttodnc)) {
			api_sms_handle_dnc_opt_in_out($optouttodnc, $contents, $e164);
		}

        $file = api_sms_dids_get_script_attached($sms_account);
	    if ($file) {
            include_once($file);

            $function = "inbound_" . $sms_account;

            if(!is_callable($function)) return false;

            if(!$function($received)) return false;
        }
    }

	$sql = "INSERT INTO `sms_received` (`timestamp`, `received`, `sms_account`, `from`, `contents`) VALUES (NOW(), ?, ?, ?, ?)";
	$rs = api_db_query_write($sql, array(date("Y-m-d H:i:s", $received["received"]), $sms_account, $e164, $contents));

	if(!$rs) return false;

	$id = api_db_lastid();

	// Inbound message post back handling
	$postbackurl = api_sms_dids_setting_getsingle($sms_account, "restpostback.smsreceive");

	if(!empty($postbackurl)) {

		$date = new DateTime();

		$details = array("url" => $postbackurl,
			"payload" => array("sms_messages" =>
				array(
					array("created" => $date->format(DateTime::RFC2822),
						"id" => api_misc_crypt_safe($id),
						"from" => $e164,
						"destination" => api_sms_dids_setting_getsingle($sms_account, "name"),
						"message" => $contents
					)
				)
			)
		);

		api_queue_add("restpostback", $details);
	}

	$tags = api_sms_dids_tags_get($sms_account);

	// SMS auto-reply handling
	if(!$smsloop && !empty($tags["autoreply-message"]) && !empty($tags["autoreply-apiuserid"])) {

		// If the tag "autoreply-optout" is true, we should opt out the user instead
		if(!empty($tags["autoreply-optout"]) && $tags["autoreply-optout"] && preg_match("/^(stop|n0|opt out|optout|do not text|unsubscribe)/i", trim($received["contents"]))) {

			if(!empty($received["target"])){

				api_data_responses_add($received["target"]["campaignid"], 0, $received["target"]["targetid"], $received["target"]["targetkey"], "OPTOUT", "YES");
				api_restrictions_donotcontact_add("phone", $received["e164"], api_campaigns_setting_getsingle($received["target"]["campaignid"], "donotcontactdestination"));

			}

		} else {
			$autoreplyTurnOff = api_system_tags_get('sms-autoreply-turn-off');

			if ($autoreplyTurnOff) {
				return true;
			}

			$timeTreshold = api_system_tags_get('sms-autoreply-time-treshold');
			$countTreshold = api_system_tags_get('sms-autoreply-count-treshold');

			if (!$timeTreshold || !$countTreshold) {
				api_sms_apisend($e164, $tags["autoreply-message"], $tags["autoreply-apiuserid"]);
				return true;
			}
			$recentMessages = api_sms_gethistory($sms_account, $from, 1, false);
			$messagesUntilTreshold = 0;
			$time = time();
			foreach($recentMessages as $timestamp => $messages) {
				if ($timestamp < ($time - ($timeTreshold * 1000))) {
					continue;
				}

				$messagesUntilTreshold += count($messages);
				if ($messagesUntilTreshold >= $countTreshold) {
					return true;
				}
			}
			api_sms_apisend($e164, $tags["autoreply-message"], $tags["autoreply-apiuserid"]);
		}
	}

	return true;
}

/**
 *
 * Attempts to determine if the last sms sent from the given account to the given destination has a campaign.
 *
 * @param $sms_account
 * @param $e164_original_destination
 * @return mixed (false|campaign id)
 */
function api_sms_last_sent_has_campaign($sms_account, $e164_original_destination)
{
	$destination = api_data_numberformat($e164_original_destination);
	$did = api_sms_dids_setting_getsingle($sms_account, "name");

	$last_target_event = api_targets_find_last_sent_event($e164_original_destination, $sms_account);

	// If there's no target_id there's no campaign, or if there is a target id and no campaign_id there's no campaign (old API sms's)
	if (!$last_target_event || (is_array($last_target_event) && !$last_target_event['campaignid'])) {
		return false;
	} else {
		// If there is a target and it has a campaign, check if the sms_out table has a more recent SMS in it
		$params = array($destination["fnn"], $destination["destination"], $did);
		$sql = "SELECT id FROM sms_out WHERE (destination = ? OR destination = ?) AND sms_out.`from` = ? ";
		if (is_array($last_target_event)) {
			$sql .= " AND sms_out.timestamp > ?";
			$params[] = $last_target_event['timestamp'];
		}
		$sql .= " ORDER BY sms_out.id DESC LIMIT 1;";

		$rs = api_db_query_read($sql, $params);

		if ($rs && $rs->RecordCount() > 0) {
			return false;
		}
		return true;
	}
}

// Receive SMS status
function api_sms_receive_dr($dr){

	if(empty($dr["supplieruid"])) return api_error_raise("SMS DR received without any supplier ID");
	if(!isset($dr["supplier"])) api_misc_audit("SMSDR_NOSUPPLIER", "No supplier for supplierid=" . $dr["supplieruid"]);

	$dr["supplieruid"] = trim($dr["supplieruid"]);

	$received = array("status" => $dr["status"],
		"supplieruid" => $dr["supplieruid"],
		"supplierdate" => $dr["supplierdate"],
		"code" => $dr["code"]);

	$sql = "SELECT * FROM `sms_out` WHERE `supplier` = ? AND `supplierid` LIKE ? ORDER BY `id` DESC LIMIT 1";
	$rs = api_db_query_read($sql, array($dr["supplier"], $dr["supplieruid"]));

	if($rs->RecordCount() > 0) return api_sms_out_status($dr["supplieruid"], $dr["status"], $dr["supplierdate"], $rs->Fields("id"));

	$sql = "SELECT * FROM `sms_sent` WHERE `supplier` = ? AND `supplieruid` LIKE ? ORDER BY `eventid` DESC LIMIT 1";
	$rs = api_db_query_read($sql, array($dr["supplier"], $dr["supplieruid"]));

	if($rs->RecordCount() > 0) {

		$received["sms_account"] = $rs->Fields("sms_account");
		$received["eventid"] = $rs->Fields("eventid");
		$received["sent"] = $rs->Fields("timestamp");
		$received["to"] = $rs->Fields("to");
		$received["contents"] = $rs->Fields("contents");

		if(preg_match("/^02[0-9]+/", $received["to"])) $baddata = "64" . substr($received["to"], 1);
		else $baddata = $received["to"];

		if($dr["status"] == "UNDELIVERED") api_restrictions_baddata_add("phone", $baddata);
		else api_restrictions_baddata_remove_single("phone", $baddata);

		$sql = "SELECT * FROM `call_results` WHERE `eventid` = ? LIMIT 1";
		$rs2 = api_db_query_read($sql, array($received["eventid"]));

		if($rs2->RecordCount() > 0){

			$received["target"] = api_targets_getinfo($rs2->Fields("targetid"));

			$deliveredDateTime = new DateTime();

			// Server timezone has to be configured because api.php sets the default timezone to be
			// Australia/Brisbane. This will set the timestamp for delivery time with a different timezone
			// as opposed to the rest of the timestamp in the data base.
			if (defined(SERVER_TIME_ZONE)) {
				$deliveredDateTime->setTimezone(new DateTimeZone(SERVER_TIME_ZONE));
			}

			$deliveredDateTime = $deliveredDateTime
				->setTimestamp($received['supplierdate'])
				->format('Y-m-d H:i:s');

			if(is_array($received["target"])) {
				api_data_responses_add(
					$received["target"]["campaignid"],
					$received["eventid"],
					$received["target"]["targetid"],
					$received["target"]["targetkey"],
					$received["status"],
					$deliveredDateTime
				);


			// SMS DID Campaign integration
				if(api_sms_dids_setting_getsingle($received["sms_account"], "linktocampaign", true) == "on"){

					$email["to"] = api_campaigns_setting_getsingle($received["target"]["campaignid"], "smsreceiptemail");

				// Receipt email forwarding
					if(!empty($email["to"])){

						$received["target"]["campaign"] = api_campaigns_setting_getsingle($received["target"]["campaignid"], "name");

						$email["subject"]     = "[ReachTEL] SMS delivery notification from " . $received["to"];
						$email["textcontent"] = "Hello,\n\nFollowing is a delivery notification on your dedicated SMS number:\n\n\n\nTime: " . date("H:i:s d M Y T", $received['supplierdate']) . "\n\nNumber: " . $received["to"] . "\n\nStatus: " . $received['status'] . "\n\nUnique ID " . $received["target"]["targetkey"] . "\n\nCampaign: " . $received["target"]["campaign"];
						$email["htmlcontent"] = "Hello,\n\nFollowing is a delivery notification on your dedicated SMS number:\n\n<table style=\"width: 650px;\"><tr><td style=\"width: 100px;\">Time:</td><td><span style=\"color: red;\">" . date("H:i:s d M Y T", $received['supplierdate']) . "</span></td></tr><tr><td style=\"width: 100px;\">Number:</td><td><span style=\"color: red;\">" . $received["to"] . "</span></td></tr><tr><td style=\"width: 100px;\">Status:</td><td><span style=\"color: red;\">" . $received['status'] . "</span></td></tr><tr><td style=\"width: 100px;\">Unique ID:</td><td><span style=\"color: red;\">" .  $received["target"]["targetkey"] . "</span></td></tr><tr><td style=\"width: 100px;\">Campaign:</td><td><span style=\"color: red;\">" . $received["target"]["campaign"] . "</span></td></tr></table>\n\n";
						api_email_template($email);

					}
				}

			}
		}
	} else {
		// Record SMS DR without a corresponding SMS_OUT/SMS_SENT to protect against race condition
		// See REACHTEL-203
		$sql = 'INSERT INTO `sms_raw_receipts` (`supplier`, `supplierid`, `status`, `code`, `supplierdate`) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))';
		$rs = api_db_query_write($sql, [
			$dr['supplier'],
			$dr['supplieruid'],
			$dr['status'],
			$dr['code'],
			$dr['supplierdate']
		]);
		if ($rs === false) {
			api_error_raise("SMS delivery receipt failed to find the outbound sms and wasn't saved to sms_raw_receipts. Supplier Id: " . $dr["supplier"] . ', SupplierUid: ' . $dr["supplieruid"]);
		}
	}

	if(!empty($received["sms_account"]) AND file_exists(__DIR__  . "/lib/smsscripts/inbound_status_" . $received["sms_account"] . ".php")){

		include_once(__DIR__ . "/lib/smsscripts/inbound_status_" . $received["sms_account"] . ".php");

		$function = "inbound_status_" . $received["sms_account"];

		if(!$function($received)) return false;

	}

	if(!empty($received["eventid"])){

		$sql = "INSERT INTO `sms_status` (`timestamp`, `status`, `code`, `supplierdate`, `eventid`) VALUES (NOW(), ?, ?, FROM_UNIXTIME(?), ?)";
		$rs = api_db_query_write($sql, array($received["status"], $received["code"], $received["supplierdate"], $received["eventid"]));

		if($rs !== FALSE) return true;
		else return false;
	}

	return true;

}

function api_sms_concat_receive($sms_account, $from, $destination, $identifier, $part, $parts, $message, $source) {

	// Firstly, check if we already have some parts for this from-to-identifier combination
	$sql = "SELECT * FROM `sms_concat` WHERE `source` = ? AND `destination` = ? AND `identifier` = ?";
	$rs = api_db_query_read($sql, array($source, $destination, $identifier));

	/*
		Have we received all the other message parts except this part we are currently processing?

		For example, if we already have 2 out of 3 parts saved in the database ($parts - 1), this current part would make it 3 out of 3 so merge and return.
	*/

	if($rs->RecordCount() == ($parts - 1)) {

		$pieces[$part] = $message; // The first piece of the message is the one we received right now

		while(!$rs->EOF) {
			$pieces[$rs->Fields("part")] = $rs->Fields("message");
			$rs->MoveNext();
		}

		ksort($pieces); // Arrange the pieces in the correct order

		$content = implode("", $pieces); // Glue it all together

		if (api_sms_receive(time(), null, $sms_account, $from, $content, $source)) { // We have the entire message so delete the parts

			$sql = "DELETE FROM `sms_concat` WHERE `source` = ? AND `destination` = ? AND `identifier` = ?";
			$rs = api_db_query_write($sql, array($source, $destination, $identifier));

			if($rs) return true;
			else return false;

		} else return false;

	} else { // We don't have all the parts yet so save this part and move on

		$sql = "INSERT INTO `sms_concat` (`source`, `destination`, `identifier`, `part`, `parts`, `message`) VALUES (?, ?, ?, ?, ?, ?)";
		$rs = api_db_query_write($sql, array($source, $destination, $identifier, $part, $parts, $message));

		if(!$rs) return false;

	}

	return true;

}

function api_sms_concat_process(){

	// This function looks to recover partial messages where we haven't received all the parts. After SMS_CONCAT_TIMEOUT minutes, we just return whatever we have

	$sql = "SELECT * FROM `sms_concat` WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
	$rs = api_db_query_read($sql, array(SMS_CONCAT_TIMEOUT));

	// If we have no parts to process, just return
	if(!$rs OR ($rs->RecordCount() == 0)) return true;

	$message = array();

	while ($parts = $rs->FetchRow()) $messages[$parts["source"] . $parts["destination"] . $parts["identifier"]][$parts["part"]] = $parts;

	foreach($messages as $identifier => $parts) {

		$pieces = array();

		foreach ($parts as $part => $message) {

			$pieces[$part] = $message["message"];

		}

		ksort($pieces); // Arrange the pieces in the correct order

		$content = implode("", $pieces); // Glue it all together

		// Determine the sms_account for this inbound number
		$formatted = api_data_numberformat($message["destination"]);

		$sms_account = api_sms_dids_checkexists($formatted["destination"]);

		if (!is_numeric($sms_account)) return false;

		if (api_sms_receive(strtotime($message["timestamp"]), null, $sms_account, $message["source"], $content, $message["source"])) { // We have the entire message so delete the parts

			$sql = "DELETE FROM `sms_concat` WHERE `source` = ? AND `destination` = ? AND `identifier` = ?";
			$rs = api_db_query_write($sql, array($message["source"], $message["destination"], $message["identifier"]));

		}
	}

	return true;

}

function api_sms_gethistory($didid, $destination, $interval = 7, $getReceivedMessages = true){

	if(!is_numeric($didid)) return api_error_raise("Sorry, that is not a valid SMS DID");
	if(!is_numeric($destination)) return api_error_raise("Sorry, that is not a valid destination number");
	if(!is_numeric($interval)) return api_error_raise("Sorry, that is not a valid interval");

	$messages = array();

	$didname = api_sms_dids_setting_getsingle($didid, "name");

	// Check if we have a dedicated number (i.e. 61400123987) or an alpha-numeric (i.e. rc3105)
	if(is_numeric($didname)) {
		$from = api_data_numberformat($didname);
	} else {
		// It's an alpha so fake the number formatting
		$from = ['destination' => $didname, 'fnn' => $didname];
	}

	$destination = api_data_numberformat($destination);

	if(!is_array($from)) return api_error_raise("Sorry, that is not a valid SMS DID");
	if(!is_array($destination)) return api_error_raise("Sorry, that is not a valid destination");

	$sql = "SELECT * FROM `sms_sent` WHERE `sms_account` = ? AND ((`to` = ?) OR (`to` = ?)) AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY `timestamp` DESC";
	$rs = api_db_query_read($sql, array($didid, $destination["fnn"], $destination["destination"], $interval));

	while(!$rs->EOF){

		// Check if this was sent by an API user. If so, set the userid
		$sql = "SELECT `userid` FROM `sms_api_mapping` WHERE `rid` = ?";
		$rs2 = api_db_query_read($sql, array($rs->Fields("eventid")));

		if($rs2 && ($rs2->RecordCount() > 0)) $userid = $rs2->Fields("userid");
		else $userid = null;

		$messages[strtotime($rs->Fields("timestamp"))][] = array("direction" => "sent", "contents" => $rs->Fields("contents"), "userid" => $userid);

		$rs->MoveNext();

	}

	if(! $getReceivedMessages) {
		return $messages;
	}

	$sql = "SELECT * FROM `sms_out` WHERE ((`from` = ?) OR (`from` = ?)) AND `destination` = ? AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY `timestamp` DESC";
	$rs = api_db_query_read($sql, array($from["destination"], $from["fnn"], $destination["destination"], $interval));

	while(!$rs->EOF){

		$messages[strtotime($rs->Fields("timestamp"))][] = array("direction" => "sent", "contents" => $rs->Fields("message"), "userid" => $rs->Fields("userid"));

		$rs->MoveNext();

	}

	$received_history = api_sms_get_received_sms_history([$didid], $destination, $interval);

	foreach ($received_history as $received) {
		$messages[strtotime($received['timestamp'])][] = ["direction" => "received", "contents" => $received['contents']];
	}

	return $messages;

}

/**
 * @param array $dids
 * @param DateTime $from
 * @param DateTime $to
 * @return an|array|bool
 */
function api_sms_get_received_sms(array $dids, DateTime $from, DateTime $to){
	if(empty($dids)){
		return api_error_raise('No dids passed for generating received sms history');
	}

	$sql = "SELECT * FROM `sms_received` WHERE `sms_account` 
					IN (" .implode(',', array_fill(0, count($dids), '?')) .") 
					AND `timestamp` >= ? AND `timestamp` <= ?";
	$rs = api_db_query_read($sql, array_merge($dids, [$from->format("Y-m-d H:i:s"), $to->format("Y-m-d H:i:s")]));
	if (!$rs) {
		return api_error_raise('Error in sql when generating sms received history');
	}

	return $rs->RecordCount() ? $rs->GetArray() : [];
}

function api_sms_get_received_sms_history(array $didids, array $destination = [], $interval = 7) {
	if (!$didids) {
		return api_error_raise('No dids passed for generating received sms history');
	}

	if ($destination) {
		// We do not want to proceed if destination is passed and is invalid
		if (!isset($destination['fnn']) || !isset($destination['destination'])) {
			return api_error_raise('Invalid array for destination received for generating sms received history');
		}
	}

	$parameters = $didids;

	$sql = "SELECT * FROM `sms_received` WHERE `sms_account` IN (" .
		implode(',', array_fill(0, count($didids), '?')) .
		") AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY)";

	$parameters[] = $interval;

	if ($destination) {
		$sql .= " AND `from` IN (?,?)";
		$parameters[] = $destination['fnn'];
		$parameters[] = $destination['destination'];
	}

	$rs = api_db_query_read($sql, $parameters);

	if (!$rs) {
		return api_error_raise('Error in sql when generating sms received history');
	}

	return $rs->RecordCount() ? $rs->GetArray() : [];
}

/**
 * Takes SMS content and returns an array that describes the length of the string
 *
 * @param string $content
 * @return array
 */
function api_sms_length($content) {

	$regex = "/\[%([^%]+)%\]/i";

	$result = array("content" => $content,
		"contentwithoutmergefields" => $content,
		"length" => 0,
		"messages" => 1,
		"hasmergefields" => false,
		"mergefields" => [],
		"textualdescription" => "0 characters");

	if(empty($content)) return $result;

	$content = preg_replace($regex, "", $content, -1, $count);

	$result["length"] = strlen($content);

	$result["textualdescription"] = $result["length"] . " characters";

	// These fields are automatically generated so aren't user supplied merge fields
	// We will add each found merge field to this array to de-dupe any future merge fields
	$skip = ["targetkey", "targetid", "destination", "campaignid", "enctargetid", "rt-date", "rt-time"];

	if($count) {
		$result["hasmergefields"] = true;
		$result["contentwithoutmergefields"] = $content;
		$result["textualdescription"] .= " plus merge fields";

		preg_match_all($regex, $result["content"], $matches);

		foreach($matches[1] as $key => $match) {
			if (preg_match("/^(.*)\|(.*)$/", $match, $fallbackMatches)) {
				if(in_array($fallbackMatches[1], $skip)) {
					continue;
				}
				$skip[] = $fallbackMatches[1];
				$result["mergefields"][] = [
					"field" => $fallbackMatches[1],
					"fallback" => $fallbackMatches[2],
				];
			} else {
				if(in_array($match, $skip)) {
					continue;
				}
				$skip[] = $match;
				$result["mergefields"][] = [
					"field" => $match,
					"fallback" => false,
				];
			}
		}

	}

	$result["textualdescription"] .= ($result["hasmergefields"]) ? " (at least " : " (";

	if(strlen($content) > 160) {
		$result["messages"] = ceil(strlen($content) / 153);
		$result["textualdescription"] .= $result["messages"] . " messages)";
	} else {
		$result["textualdescription"] .= $result["messages"] . " message)";
	}

	return $result;
}

// Add or Update SMS DID

function api_sms_dids_add($mvn){

	// Trim it to remove any junk
	$mvn = trim($mvn);

	// This regex checks that we don't have any invalid characters in the DID
	if(!preg_match("/^[0-9A-Za-z\.\+\_\-\!\?\s]{1,11}$/i", $mvn)) return api_error_raise("Sorry, that is not a valid SMS DID");

	// Convert the number to an e164 number if possible
	$formatted = api_data_numberformat($mvn);

	// If we now have a valid e164 number, use that instead of the supplied value
	if(is_array($formatted)) $mvn = $formatted["destination"];

	// If the number already exists, bail out
	if(api_sms_dids_checkexists($mvn)) return api_error_raise("Sorry, that SMS DID already exists");

	$id = api_keystore_increment("SMSDIDS", 0, "nextid");

	api_keystore_set("SMSDIDS", $id, "name", $mvn);
	api_sms_dids_setting_set($id, "lastsend", microtime(true), FALSE);

	return $id;

}

// Delete SMS DID

function api_sms_dids_delete_bydid($mvn){

	$didid = api_sms_dids_checkexists($mvn);

	if(!is_numeric($didid)) return api_error_raise("Sorry, that DID doesn't exist");

	return api_sms_dids_delete_bydidid($didid);

}

  // Delete by didid

function api_sms_dids_delete_bydidid($didid){

	if(!is_numeric($didid)) return false;

	if(api_keystore_checkkeyexists("CAMPAIGNS", "smsdid", $didid)) return api_error_raise("Sorry, cannot delete a DID that is assigned to a campaign");

	api_keystore_purge("SMSDIDS", $didid);

	$userid = (empty($_SESSION['userid']) ? null : $_SESSION['userid']);
	api_misc_audit("SMSDIDDELETE", $didid, $userid);

	return true;

}

// List all SMS DIDs

function api_sms_dids_listall($long = 0){

	$names = api_keystore_getids("SMSDIDS", "name", true);

	if(empty($names) OR !is_array($names)) return array();

	natcasesort($names);

	if(!$long) return $names;

	$use = api_keystore_getids("SMSDIDS", "use", true);

	$dids = array();

	foreach($names as $id => $name) {

		$dids[$id]["name"] = $name;

		if(isset($use[$id])) $dids[$id]["use"] = $use[$id];
		else $dids[$id]["use"] = "";

	}

	return api_misc_natcasesortbykey($dids, "name");

}

function api_sms_dids_setting_get_multi_byid(array $dids, $item) {
	return api_keystore_get_multi_byid('SMSDIDS', $dids, $item);
}

// Check if SMS DID exists

function api_sms_dids_checkexists($did){ return api_keystore_checkkeyexists("SMSDIDS", "name", $did); }

// Check if the current didid exists

function api_sms_dids_checkidexists($didid){

	if(!is_numeric($didid)) return false;

	if(api_keystore_get("SMSDIDS", $didid, "name") !== FALSE) return true;
	else return false;

}

function api_sms_dids_nametoid($name){

	$id = api_sms_dids_checkexists($name);

	if(is_numeric($id)) return $id;
	else return false;

}

function api_sms_dids_messagehistory($didid, $options = array()){

	if(!api_sms_dids_checkidexists($didid)) return api_error_raise("Sorry, that is not a valid SMS DID");

	if(empty($options["direction"]) OR !in_array($options["direction"], array("inbound", "outbound"))) return api_error_raise("Sorry, that is not a valid request");

	if(!empty($options["limit"]) AND !is_numeric($options["limit"])) return api_error_raise("Sorry, that is not a valid limit number");

	if(!empty($options["starttime"]) AND !strtotime($options["starttime"])) return api_error_raise("Sorry, that is not a valid start time");

	if(!empty($options["endtime"]) AND !strtotime($options["endtime"])) return api_error_raise("Sorry, that is not a valid end time");

	$sql = "SELECT * FROM `" . (($options["direction"] == "inbound") ? "sms_received" : "sms_sent") .  "` WHERE `sms_account` = ?";
	$parameters = array($didid);

	if(!empty($options["starttime"])) {

		$sql .= " AND `timestamp` > ?";
		array_push($parameters, date("Y-m-d 00:00:00", strtotime($options["starttime"])));

	}

	if(!empty($options["endtime"])) {

		$sql .= " AND `timestamp` < ?";
		array_push($parameters, date("Y-m-d 23:59:59", strtotime($options["endtime"])));

	}

	$sql .= " ORDER BY `" . (($options["direction"] == "inbound") ? "smsid" : "eventid") . "` DESC";

	if(!empty($options["limit"]) AND is_numeric($options["limit"])) $sql .= " LIMIT " . $options["limit"];

	$rs = api_db_query_read($sql, $parameters);

	$messages = array();

	if($rs->RecordCount() > 0)
	while($message = $rs->FetchRow()){

		$result = array("contents" => $message["contents"], "timestamp" => $message["timestamp"]);

		if($options["direction"] == "inbound") $result["number"] = $message["from"];
		else $result["number"] = $message["to"];

		$formatted = api_data_numberformat($result["number"]);

		if(is_array($formatted)) $result["number"] = $formatted["fnn"];

		$messages[] = $result;

	}

	return $messages;

}

// Did settings

// Add or update setting

function api_sms_dids_setting_set($didid, $setting, $value) { return api_keystore_set("SMSDIDS", $didid, $setting, $value); }

// Delete setting

  // Single
function api_sms_dids_setting_delete_single($didid, $setting) { return api_keystore_delete("SMSDIDS", $didid, $setting); }

  // All

function api_sms_dids_setting_delete_all($didid) { return api_keystore_purge("SMSDIDS", $didid); }

// Get

  // Single

function api_sms_dids_setting_getsingle($didid, $setting) { return api_keystore_get("SMSDIDS", $didid, $setting); }

  // All settings

function api_sms_dids_setting_getall($didid, $return_defaults = false) {
	$settings = api_keystore_getnamespace("SMSDIDS", $didid);

	if ($return_defaults) {
		foreach (api_sms_settings_defaults() as $setting => $value) {
			if (!isset($settings[$setting])) {
				$settings[$setting] = $value;
			}
		}
	}

	return $settings;
}


function api_sms_dids_tags_get($id, $tags = null){

	if(!api_sms_dids_checkidexists($id)) return api_error_raise("Sorry, that is not a valid SMS DID");

	return api_tags_get('SMSDIDS', $id, $tags);

}

function api_sms_dids_tags_set($id, array $tags = [], array $encrypt_tags = []){

	if(!api_sms_dids_checkidexists($id)) return api_error_raise("Sorry, that is not a valid SMS DID");

	return api_tags_set('SMSDIDS', $id, $tags, $encrypt_tags);

}

function api_sms_dids_tags_delete($id, array $tags = []){

	if(!api_sms_dids_checkidexists($id)) return api_error_raise("Sorry, that is not a valid SMS DID");

	return api_tags_delete('SMSDIDS', $id, $tags);

}

function api_sms_dids_tags_get_all_details($id) {
	if(!api_sms_dids_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid SMS DID");
	}

	return api_tags_get_existing_tag_details('SMSDIDS', $id, true);
}

function api_sms_dids_get_script_attached($sms_account) {
	$file = __DIR__ . "/lib/smsscripts/inbound_" . $sms_account . ".php";
	if (file_exists($file)) {
		return $file;
	}

	return null;
}

function api_sms_settings_defaults() {
	return [
		SMS_DID_SETTING_USE_ON_SHORE_PROVIDER => SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_NOT_REQUIRED
	];
}

function api_sms_find_sms_sent_by_targetid($targetid) {
	$sql = 'SELECT s.* from `sms_sent` s JOIN `call_results` c ON (s.`eventid` = c.`eventid`) WHERE c.targetid=?';
	$rs = api_db_query_read($sql, [$targetid]);

	if (!$rs || !$rs->RecordCount()) {
		return [];
	}

	return $rs->GetRowAssoc();
}

function api_sms_handle_dnc_opt_in_out($dnclistid, $contents, $e164) {
	if (is_null($e164)) {
		return true;
	}

	if(preg_match("/^(stop|opt out|optout|do not text|unsubscribe)/i", trim($contents))) { // Handle opt outs
		return api_restrictions_donotcontact_add("phone", $e164, $dnclistid);
	}

	if (preg_match("/^(subscribe|optin|opt in)/i", trim($contents))) {
		return api_restrictions_donotcontact_remove_single('phone', $e164, $dnclistid);
	}

	return true;
}

/**
 * @param DateTime|null $notafter
 * @param DateTime|null $notbefore
 * @param string | null $supplierId
 * @return array
 */
function api_sms_fetch_all_sms_id_without_receipts(
    DateTime $notafter = null,
    DateTime $notbefore = null,
    $supplierId = null
) {
    $sms = [];

    if (!$notbefore) {
        $notbefore = (new DateTime())->modify('-1 month');
    }

    $sms_sent_sql = 'select s.supplier as supplier_id, s.supplieruid as sms_id from sms_sent s left join sms_status ss on (s.eventid = ss.eventid) where s.timestamp >= ?';
    $sms_out_sql = 'select s.supplier as supplier_id, s.supplierid as sms_id from sms_out s left join sms_out_status ss on (s.id = ss.id) where s.timestamp >= ?';

    $parameters = [$notbefore->format('Y-m-d H:i:s')];
    if ($supplierId) {
        $sms_sent_sql .= ' AND s.supplier=?';
        $sms_out_sql .= ' AND s.supplier=?';
        $parameters[] = $supplierId;
    }

    if ($notafter) {
        $sms_sent_sql .= ' AND s.timestamp <= ?';
        $sms_out_sql .= ' AND s.timestamp <= ?';
        $parameters[] = $notafter->format('Y-m-d H:i:s');
    }

    $sms_sent_sql .= ' AND ss.eventid is null';
    $sms_out_sql .= ' AND ss.id is null';

    $rs = api_db_query_read($sms_sent_sql . ' UNION ' . $sms_out_sql, array_merge($parameters, $parameters));

    if($rs AND ($rs->RecordCount() > 0)) {
        $sms = $rs->GetAssoc();
    }

    return $sms;
}
