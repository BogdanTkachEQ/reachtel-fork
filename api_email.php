<?php

// Send an email

use Models\Email\Dkim\DkimKey;
use Services\Email\Dkim\DkimKeystore;
use Services\Email\Dkim\DkimKeyTypeEnum;
use Services\Email\Dkim\DkimSigner;

function api_email_send($email) {
	// Sends a message without all the preparation
	$details = api_email_prepare_for_queue($email);
	api_queue_add("email", $details);

	return true;
}

function api_email_prepare_for_queue($email) {

	if(!isset($email["from"])) {
		$email["from"] = EMAIL_DEFAULT_FROM;
	}

	$headers = array('From' => $email["from"], 'To' => $email["to"], 'Subject' => $email["subject"],
	                 'Date' => date("r"), 'X-Report-Abuse-To' => EMAIL_ABUSE);

	$recipients = $email["to"];

	if(isset($email["bulk"])) {
		$headers["Precedence"] = "bulk";
	}

	if(isset($email["sender"])) {
		$headers["Sender"] = $email["sender"];
	} else {
		$headers["Sender"] = EMAIL_SENDER;
	}

	if(isset($email["List-Unsubscribe"])) {
		$headers["List-Unsubscribe"] = $email["List-Unsubscribe"];
	}
	if(isset($email["X-Tracking-Id"])) {
		$headers["X-Tracking-Id"] = $email["X-Tracking-Id"];
	}

	if(!empty($email["Message-Id"])) {
		$headers["Message-Id"] = $email["Message-Id"];
	} else {
		$headers["Message-Id"] = "<" . microtime(true) . "-" . uniqid() . "@equifax.com>";
	}

	if(!empty($email["cc"])) {
		$headers["Cc"] = $email["cc"];
		$recipients .= ", " . $email["cc"];
	}

	if(isset($email["returnpath"])) {
		$headers["Return-Path"] = $email["returnpath"];
	} else {
		$headers["Return-Path"] = EMAIL_SENDER;
	}

	if(!empty($email["replyto"])) {
		$headers["Reply-To"] = $email["replyto"];
	}

	if(isset($email["bcc"])) {
		$recipients .= ", " . $email["bcc"];
	}

	$mime = new Mail_mime();

	$options['head_encoding'] = 'quoted-printable';
	$options['text_encoding'] = 'quoted-printable';

	if($email["textcontent"]) {

		$options['text_charset'] = 'utf-8';
		$mime->setTXTBody($email["textcontent"]);

	}

	if($email["htmlcontent"]) {

		$options['html_charset'] = 'utf-8';
		$mime->setHTMLBody($email["htmlcontent"]);
		if(!empty($email["images"]) AND is_array($email["images"])) {
			foreach ($email["images"] as $image)
				$mime->addHTMLImage(
					$image["content"], api_email_filetype($image["filename"]), $image["filename"], false
				);
		}

	}

	if(isset($email["attachments"]) AND is_array($email["attachments"])) {
		foreach ($email["attachments"] as $attachment)
			$mime->addAttachment(
				$attachment["content"], api_email_filetype($attachment["filename"]), $attachment["filename"], false,
				'base64', 'attachment', null, null, null, 'base64', 'base64'
			);
	}

	$body = $mime->get($options);

	$hdrs = $mime->headers($headers);

	$details = array("recipients" => $recipients, "headers" => $hdrs, "body" => $body);

	if(isset($email["smtpserver"])) {
		$details["smtpserver"] = $email["smtpserver"];
	}
	if(isset($email["target"])) {
		$details["target"] = $email["target"];
	}

	return $details;
}

// Prepare the body of an email

function api_email_generatemessage($message, $settings) {

	$message["eventid"] = api_misc_uniqueid();

	$email["target"] = $message;

	$email["to"] = $message["destination"];
	$email["returnpath"] = api_misc_crypt_safe($message["targetid"]) . "@" . EMAIL_SENDER_DOMAIN;
	$email["subject"] = api_data_merge_process($settings["subject"], $message["targetid"]);
	$email["from"] = api_data_merge_process($settings["from"], $message["targetid"]);
	$email["replyto"] = api_data_merge_process($settings["replyto"], $message["targetid"]);
	if(!empty($settings["smtpserver"])) {
		$email["smtpserver"] = $settings["smtpserver"];
	}

	if($email["subject"] === FALSE) {
		return false;
	}

	$hosttrack = api_hosts_gettrack();
	if($settings["removelistunsub"] != "on") {
		$email["List-Unsubscribe"] = "<{$hosttrack}/unsubscribe.php?tid=" . api_misc_crypt_safe(
				$message["targetid"]
			) . ">";
	}

	$email["X-Tracking-Id"] = api_misc_crypt_safe($message["targetid"]);

	$email["eventid"] = $message["eventid"];

	$content = $settings["template"];

	$cc = api_data_merge_get_single($message["campaignid"], $message["targetkey"], "rt-email-cc");
	if(!empty($cc)) {
		$email["cc"] = $cc;
	}

	$bcc = api_data_merge_get_single($message["campaignid"], $message["targetkey"], "rt-email-bcc");
	if(!empty($bcc)) {
		$email["bcc"] = $bcc;
	}

	$email["htmlcontent"] = api_email_merge(
		$message, file_get_contents(
			        READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . api_emailtemplates_setting_getsingle(
				        $settings["template"], "name"
			        ) . ".tpl"
		        )
	);
	$email["textcontent"] = api_email_merge(
		$message, file_get_contents(
			        READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-" . api_emailtemplates_setting_getsingle(
				        $settings["template"], "name"
			        ) . ".tpl"
		        )
	);

	$attachment = api_emailtemplates_setting_getsingle(
		api_campaigns_setting_getsingle($message["campaignid"], "template"), "attachment"
	);

	if(($attachment) AND (file_exists(READ_LOCATION . ASSET_LOCATION . "/" . $attachment))) {
		$email["attachments"][] = array("content" => file_get_contents(
			READ_LOCATION . ASSET_LOCATION . "/" . $attachment
		), "filename" => $attachment);
	}

	$remoteattachments = api_data_merge_get_single(
		$message["campaignid"], $message["targetkey"], "rt-remoteattachments"
	);
	if($remoteattachments !== FALSE) {

		$remoteattachments = unserialize($remoteattachments);

		if(is_array($remoteattachments)) {

			foreach ($remoteattachments as $remoteattachment) {

				if(file_exists(
					READ_LOCATION . REMOTEATTACHMENTS_LOCATION . "/" . $remoteattachment["storefilename"]
				)) {
					$email["attachments"][] = array("content" => file_get_contents(
						READ_LOCATION . REMOTEATTACHMENTS_LOCATION . "/" . $remoteattachment["storefilename"]
					), "filename" => $remoteattachment["filename"]);
				} else {
					return false;
				}
			}

		}

	}

	$images = api_email_getimages($email["htmlcontent"]);

	if($images) {
		foreach ($images as $image) {
			if($image) {
				if(file_exists(READ_LOCATION . ASSET_LOCATION . "/" . $image)) {
					$email["images"][] = array("content" => file_get_contents(
						READ_LOCATION . ASSET_LOCATION . "/" . $image
					), "filename" => $image);
				} else {

					api_campaigns_setting_set($message["campaignid"], "status", "DISABLED");

					$email = null;
					$email["to"] = "ReachTEL Support <support@reachtel.com.au>";
					$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

					$email["subject"] = "[ReachTEL] Campaign error - " . $settings["name"];
					$email["textcontent"] = "Hello,\n\nThe following campaign has permanent errors and has been disabled.\n\nThe image \"" . $image . "\" could not be found.";
					$email["htmlcontent"] = "Hello,\n\nThe following campaign has permanent errors and has been disabled.\n\nThe image \"" . $image . "\" could not be found.";

					api_email_template($email);

					return false;
				}
			}
		}
	}

	if(!empty($email["textcontent"]) AND !empty($email["htmlcontent"]) AND api_email_send($email)) {

		api_data_callresult_add($message["campaignid"], $message["eventid"], $message["targetid"], "GENERATED");
		api_data_responses_add(
			$message["campaignid"], $message["eventid"], $message["targetid"], $message["targetkey"], "SENT",
			date("Y-m-d H:i:s")
		);

		api_campaigns_update_lastsend($message['campaignid']);
		return true;

	} else {
		return false;
	}


}

function api_email_getimages($htmlcontent) {

	preg_match_all("/\< *[img][^\>]*src *= *[\"\']{0,1}([^\"\'\ >]*)/i", $htmlcontent, $matches);

	$images = array();

	$hosttrack = api_hosts_gettrack(false);
	if(count($matches[1]) > 0) {

		foreach ($matches[1] as $image)
			if(!preg_match("/{$hosttrack}/", $image) AND !preg_match("/bcast.reachtel.com.au/", $image)) {
				if(!in_array($image, $images)) {
					$images[] = $image;
				}
			}

		return $images;

	} else {
		return false;
	}

}

function api_email_merge($message, $content, $gracefulFail = false) {

	$content = api_data_merge_process($content, $message["targetid"], $gracefulFail);

	if(($message["targetid"]) AND (preg_match_all(
			"/\< *[a][^\>]*href *= *[\"\']{0,1}([^\"\'\ >]*)/i", $content, $matches
		))) {

		if(count($matches[1]) > 0) {

			if(isset($message["targetid"])) {
				$enctargetid = api_misc_crypt_safe($message["targetid"]);
			}

			$hosttrack = api_hosts_gettrack(false);
			foreach ($matches[1] as $key => $href) {
				if(!preg_match("/{$hosttrack}/", $href) AND !preg_match("/bcast.reachtel.com.au/", $href)) {
					$newLink = str_replace(
						$matches[1][$key],
						"https://{$hosttrack}/click.php?tid=" . $enctargetid . "&amp;ed=" . api_misc_crypt_safe(
							$href
						), $matches[0][$key]
					);
					$pattern = "/" . preg_quote($matches[0][$key], "/") . "/";
					$content = preg_replace($pattern, $newLink, $content, 1);

				}
			}

		}

	}

	return $content;


}

// Prepare the body of an email wrapped around the ReachTEL template

function api_email_template($email) {
	$htmlTemplate = file_get_contents(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-ReachTEL.tpl");
	$textTemplate = file_get_contents(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-text-ReachTEL.tpl");
	$logo = array("content" => file_get_contents(READ_LOCATION . ASSET_LOCATION . "/reachtel-150.png"),
	              "filename" => "reachtel-150.png");
	$recyclelogo = array("content" => file_get_contents(READ_LOCATION . ASSET_LOCATION . "/recycle-small.png"),
	                     "filename" => "recycle-small.png");

	if(!isset($email["from"])) {
		$email["from"] = "ReachTEL Consumer Risk <ANZ.Consumer.Risk.Tribe@equifax.com>";
	}

	$email["sender"] = "ANZ.Consumer.Risk.Tribe@equifax.com";
	$email["returnpath"] = "ANZ.Consumer.Risk.Tribe@equifax.com";

	if(isset($email["content"])) {

		$email["htmlcontent"] = $email["content"];
		$email["textcontent"] = $email["content"];

	}

	$htmlTemplate = str_replace("{date}", date("jS F Y"), $htmlTemplate);
	$textTemplate = str_replace("{date}", date("jS F Y"), $textTemplate);
	$email["htmlcontent"] = str_replace("{content}", nl2br($email["htmlcontent"]), $htmlTemplate);
	$email["textcontent"] = str_replace("{content}", $email["textcontent"], $textTemplate);
	$email["images"][] = $logo;
	$email["images"][] = $recyclelogo;

	return api_email_send($email);

}

function api_email_filetype($filename) {

	if(preg_match("/.png$/i", $filename)) {
		return "image/png";
	} elseif(preg_match("/.jpg$/i", $filename)) {
		return "image/jpeg";
	} elseif(preg_match("/.gif$/i", $filename)) {
		return "image/gif";
	} elseif(preg_match("/.csv$/i", $filename)) {
		return "application/octet-stream";
	} elseif(preg_match("/.xls$/i", $filename)) {
		return "application/vnd.ms-excel";
	} elseif(preg_match("/.ppt$/i", $filename)) {
		return "application/vnd.ms-powerpoint";
	} elseif(preg_match("/.doc$/i", $filename)) {
		return "application/msword";
	} elseif(preg_match("/.pdf$/i", $filename)) {
		return "application/pdf";
	} elseif(preg_match("/.zip$/i", $filename)) {
		return "application/zip";
	} else {
		return "application/octet-stream";
	}

}

function api_email_metrics_track() {

	if(!empty($_GET['tid'])) {
		$targetid = api_misc_decrypt_safe($_GET['tid']);
	}

	if(!empty($_GET['eid'])) {
		$eventid = api_misc_decrypt_safe($_GET['eid']);
	}

	if(empty($eventid) OR !preg_match("/^[0-9]+$/", $eventid)) {
		$eventid = 0;
	}

	if(!empty($targetid) AND preg_match("/^[0-9]+$/", $targetid)) {

		$target = api_targets_getinfo($targetid);

		if(!empty($target)) {
			api_data_responses_add(
				$target["campaignid"], $eventid, $targetid, $target["targetkey"], "TRACK", date("Y-m-d H:i:s")
			);
			if(!empty($_SERVER["HTTP_USER_AGENT"])) {
				api_data_responses_add(
					$target["campaignid"], $eventid, $targetid, $target["targetkey"], "TRACKCLIENT",
					$_SERVER["HTTP_USER_AGENT"]
				);
			}
		}

	}

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 1 Jan 2011 05:00:00 GMT"); // Date in the past
	header("Content-type: image/gif");

	print base64_decode(
		"R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
	); // This is a base64 encoded 1x1 pixel transparent GIF
	exit;

}

function api_email_metrics_click() {

	if(empty($_GET['tid'])) {
		exit;
	}

	$targetid = api_misc_decrypt_safe($_GET['tid']);

	if(!empty($_GET['ed'])) {
		$link = api_misc_decrypt_safe($_GET['ed']);
	}

	if(preg_match("/^[0-9]+$/", $targetid)) {

		$target = api_targets_getinfo($targetid);

		if($target != FALSE) {

			if(!empty($link)) {
				api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "CLICK", $link);
			}
			api_data_responses_add(
				$target["campaignid"], 0, $targetid, $target["targetkey"], "TRACK", date("Y-m-d H:i:s")
			);
			api_data_responses_add(
				$target["campaignid"], 0, $targetid, $target["targetkey"], "CLICKCLIENT", $_SERVER["HTTP_USER_AGENT"]
			);
		}

	}

	if(empty($link)) {
		$link = "https://www.reachtel.com.au";
	}

	header("Location: " . $link);
	exit;

}

function api_email_metrics_unsubscribe($tid) {

	if(empty($tid)) {
		return api_error_raise("Please click the unsubscribe link provided in your email.");
	}

	$targetid = api_misc_decrypt_safe($tid);

	if(empty($targetid)) {
		return api_error_raise("Please click the unsubscribe link provided in your email.");
	}

	$target = api_targets_getinfo($targetid);

	if(empty($target)) {
		return api_error_raise("Please click the unsubscribe link provided in your email.");
	}

	api_restrictions_donotcontact_addbytargetid($targetid);

	if(empty($_GET['notrack'])) {

		api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "TRACK", date("Y-m-d H:i:s"));
		api_data_responses_add(
			$target["campaignid"], 0, $targetid, $target["targetkey"], "UNSUBSCRIBECLIENT", $_SERVER["HTTP_USER_AGENT"]
		);
		api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "UNSUBSCRIBE", "UNSUBSCRIBE");

	}

	return true;

}

function api_email_metrics_webview($targetid = null, $templateid = null, $auth_required = false) {

	if(!empty($_GET['dv'])) { // Preview template from a campaign

		$campaignid = api_misc_decrypt_safe($_GET['dv']);

		$templateid = api_campaigns_setting_getsingle($campaignid, "template");

		$templatename = api_emailtemplates_setting_getsingle($templateid, "name");

		if(file_exists(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $templatename . ".tpl")) {

			$content = file_get_contents(
				READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $templatename . ".tpl"
			);

		}

	} elseif(!empty($_GET['tv'])) { // Preview template from an email template

		$templateid = api_misc_decrypt_safe($_GET['tv']);

		$templatename = api_emailtemplates_setting_getsingle($templateid, "name");

		if(file_exists(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $templatename . ".tpl")) {

			$content = file_get_contents(
				READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $templatename . ".tpl"
			);

		}

	} elseif(!empty($_GET['tid']) OR is_numeric($targetid)) {

		if(!empty($_GET['tid'])) {
			$targetid = api_misc_decrypt_safe($_GET['tid']);
		}

		$target = api_targets_getinfo($targetid);

		if(!empty($target)) {

			if ($auth_required) {
				_api_email_handle_webview_request($targetid);
			}

			// Record the view
			api_data_responses_add(
				$target["campaignid"], 0, $targetid, $target["targetkey"], "WEBVIEW", date("Y-m-d H:i:s")
			);

			if(empty($templateid)) {
				$templateid = api_campaigns_setting_getsingle($target["campaignid"], "template");
			}

			$templatename = api_emailtemplates_setting_getsingle($templateid, "name");

			if(file_exists(READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $templatename . ".tpl")) {

				// Get the HTML content for the email template
				$content = file_get_contents(
					READ_LOCATION . EMAILTEMPLATE_LOCATION . "/autodialer-html-" . $templatename . ".tpl"
				);

				// Merge in any merge fields, print and exit
				$content = api_email_merge($target, $content, true);

			}

		}

	}

	if(!empty($content)) {

		// Update the img tag location to point to our static asset store

		$doc = new DOMDocument();

		libxml_use_internal_errors(true);

		$doc->loadHTML($content);

		$tags = $doc->getElementsByTagName('img');

		foreach ($tags as $tag) {

			if(!preg_match("/\/\//", $tag->getAttribute('src'))) {

				$tag->setAttribute('src', '//bcast.reachtel.com.au/' . $tag->getAttribute('src'));

			}
		}

		print $doc->saveHTML();
		exit;

	} else {

		// We haven't received a valid request to just punt them to our website
		header("Location: https://www.ReachTEL.com.au/");
		exit;
	}

}

function _api_email_handle_webview_request($targetid) {
	$target = api_targets_getinfo($targetid);
	$campaignid = $target['campaignid'];
	$settings = api_campaigns_setting_get_multi_byitem($campaignid, [CAMPAIGN_SETTING_TYPE, CAMPAIGN_SETTING_REGION]);
	if (!isset($_POST['destination'])) {
		_api_email_display_webview_auth_form(api_misc_crypt_safe($targetid), $settings[CAMPAIGN_SETTING_TYPE]);
		exit;
	}

	$destination = api_data_format(
		$_POST['destination'],
		$settings[CAMPAIGN_SETTING_TYPE],
		$settings[CAMPAIGN_SETTING_REGION]
	);

	if (!$destination) {
		_api_email_display_webview_auth_form(api_misc_crypt_safe($targetid), $settings[CAMPAIGN_SETTING_TYPE], 'authfail');
		exit;
	}

	$actual_destination = api_data_format(
		$target['destination'],
		$settings[CAMPAIGN_SETTING_TYPE],
		$settings[CAMPAIGN_SETTING_REGION]
	);

	if ($actual_destination !== $destination) {
		_api_email_display_webview_auth_form(api_misc_crypt_safe($targetid), $settings[CAMPAIGN_SETTING_TYPE], 'authfail');
		exit;
	}

	return true;
}

function _api_email_display_webview_auth_form($enctargetid, $type, $action = 'auth') {
	api_templates_assign("title", "Secure document");
	api_templates_assign("action", $action);
	api_templates_assign("enctargetid", $enctargetid);
	api_templates_assign("type", $type);
	api_templates_assign("hideauth", true);
	api_templates_display("bootstrap-header.tpl");
	api_templates_display("secure-document.tpl");
	api_templates_display("bootstrap-footer.tpl");
	exit;
}

function api_email_metrics_forward($targetid, $to, $message) {

	$target = api_targets_getinfo($targetid);

	if(empty($target)) {
		return api_error_raise("Please click the 'Forward to a Friend' link in the email you received.");
	}

	if(api_targets_dataformat($to, $target["campaignid"]) AND !empty($message) AND (strlen($message) < 500)) {

		$targetkey = api_misc_uniqueid();

		api_targets_add_extradata_single($target["campaignid"], $targetkey, "rt-forward-from", $target["destination"]);
		api_targets_add_extradata_single(
			$target["campaignid"], $targetkey, "rt-forward-message",
			"This message was forwarded to you by " . $target["destination"] . ". " . strip_tags(
				$message
			)
		);

		if(!api_targets_add_single($target["campaignid"], $to, $targetkey)) {
			return api_error_raise("Invalid email address");
		} else {

			api_campaigns_setting_set($target["campaignid"], "status", "ACTIVE");
			return true;

		}

	} else {
		return api_error_raise("Please enter a valid email address and message");
	}

}

function api_email_metrics_securedocument($options = []) {

	api_templates_assign("title", "Secure document");

	// Check that we have an encrypted target id value and that it is valid target id
	if(empty($options["enctargetid"]) OR !($targetid = api_misc_decrypt_safe(
			$options["enctargetid"]
		)) OR !($target = api_targets_getinfo($targetid))) {
		api_templates_display("bootstrap-header.tpl");
		api_templates_assign("action", "invalid");
		api_templates_display("secure-document.tpl");
		api_templates_display("bootstrap-footer.tpl");
		exit;
	}

	$tags = api_campaigns_tags_get($target["campaignid"]);
	$settings = api_campaigns_setting_getall($target["campaignid"]);

	// MOR-1297: Bypass Authentication
	if(!empty($tags["secureview-template"]) && !empty($tags["skip_authentication"]) && $tags["skip_authentication"] == 1 && ($templateid = api_emailtemplates_checknameexists(
			$tags["secureview-template"]
		))) {
		return api_email_metrics_webview($targetid, $templateid, true);
	}

	// Check that the campaign it relates to has the relevant tags set
	if(empty($tags["secureview-template"]) OR empty($tags["secureview-authfield"])) {
		api_templates_display("bootstrap-header.tpl");
		api_templates_assign("action", "invalid");
		api_templates_display("secure-document.tpl");
		api_templates_display("bootstrap-footer.tpl");
		exit;
	}

	if(!empty($tags["secureview-logo"])) {
		api_templates_assign("logo", $tags["secureview-logo"]);
	}

	api_templates_assign("enctargetid", $options["enctargetid"]);
	api_templates_assign("type", $settings["type"]);

	if(!empty($options["auth"]) AND !empty($target["destination"])) {

		$auth = api_data_merge_get_single($target["campaignid"], $target["targetkey"], $tags["secureview-authfield"]);
		if(isset($tags["secureview-authfield-2"])) {
			$auth2 = api_data_merge_get_single(
				$target["campaignid"], $target["targetkey"], $tags["secureview-authfield-2"]
			);
		}
		$authattempts = api_data_merge_get_single(
			$target["campaignid"], $target["targetkey"], "secureview-authattempts"
		);

		$maxauthattempts = !empty($tags["secureview-maxauthattempts"]) ? $tags["secureview-maxauthattempts"] : 5;

		// Make sure they are both formatted correctly
		$suppliedDestination = api_data_format($options["destination"], $settings["type"], $settings["region"]);
		$targetDestination = api_data_format($target["destination"], $settings["type"], $settings["region"]);

		if(is_numeric($authattempts) AND ($authattempts >= 5)) {

			api_templates_display("bootstrap-header.tpl");
			api_templates_assign("action", "blocked");
			api_templates_display("secure-document.tpl");
			api_templates_display("bootstrap-footer.tpl");
			exit;

		} else {
			if(!empty($options["auth"]) AND (strtolower($options["auth"]) == strtolower(
						$auth
					)) AND (!isset($auth2) || (!empty($options["auth2"]) && strtolower($options["auth2"]) == strtolower(
							$auth2
						))) AND ($suppliedDestination == $targetDestination)) {

				// Authentication success
				$templateid = api_emailtemplates_checknameexists($tags["secureview-template"]);

				api_email_metrics_webview($targetid, $templateid);

			} else {

				// Authentication failure
				api_targets_add_extradata_single(
					$target["campaignid"], $target["targetkey"], "secureview-authattempts",
					!empty($authattempts) ? $authattempts + 1 : 1
				);

				api_templates_assign("action", "authfail");
			}
		}

	} else {
		api_templates_assign("action", "auth");
	}

	api_templates_display("bootstrap-header.tpl");
	api_templates_assign("authmessage", $tags["secureview-authmessage"]);
	api_templates_assign(
		"authmessageexample",
		!empty($tags["secureview-authmessage-example"]) ? $tags["secureview-authmessage-example"] : ""
	);
	if(isset($tags["secureview-authfield-2"])) {
		api_templates_assign(
			"authmessage2", isset($tags["secureview-authmessage-2"]) ? $tags["secureview-authmessage-2"] : ""
		);
	}
	if(isset($tags["secureview-authmessage-example-2"])) {
		api_templates_assign("authmessageexample2", $tags["secureview-authmessage-example-2"]);
	}
	api_templates_display("secure-document.tpl");
	api_templates_display("bootstrap-footer.tpl");
	exit;

}

function api_email_metrics_save($options = []) {

	api_templates_assign("title", "Secure document");

	// Check that we have an encrypted target id value and that it is valid target id
	if(empty($options["enctargetid"]) OR !($targetid = api_misc_decrypt_safe(
			$options["enctargetid"]
		)) OR !($target = api_targets_getinfo($targetid))) {
		api_templates_display("bootstrap-header.tpl");
		api_templates_assign("action", "invalid");
		api_templates_display("secure-document.tpl");
		api_templates_display("bootstrap-footer.tpl");
		exit;
	}

	// Remove the targetid from the form data we are about to save
	unset($options["enctargetid"]);

	// Save the form data
	foreach ($options as $action => $value) {
		api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "rt-form-" . $action, $value);
	}

	if(!empty($tags["secureview-logo"])) {
		api_templates_assign("logo", $tags["secureview-logo"]);
	}

	api_templates_display("bootstrap-header.tpl");
	api_templates_assign("action", "saved");
	api_templates_display("secure-document.tpl");
	api_templates_display("bootstrap-footer.tpl");
	exit;

}

/**
 * Return email deliverability data
 *
 * @param integer $days
 * @param array   $responses
 * @param string  $date_group_format
 */
function api_email_deliverability_data($days, array $responses, $date_group_format = '%Y-%m-%d') {
	if(!$responses) {
		return api_error_raise("No responses to extract email deliverability data");
	}

	$parameters = [$date_group_format];
	$counts = [];
	foreach ($responses as $response) {
		$parameters = array_merge($parameters, [$response, $response]);
		$counts[] = "GROUP_CONCAT(CASE `action` WHEN ? THEN `targetid` ELSE NULL END) AS ? ";
	}
	$parameters[] = date("Y-m-d 00:00:00", strtotime((int)$days . ' days ago'));

	// GROUP_CONCAT default max length is 1024, not enough for this query
	api_db_query_write("SET SESSION group_concat_max_len = 1000000;");
	$sql = "SELECT DATE_FORMAT(`timestamp`, ?) as group_date, " . implode(
			', ', $counts
		) . "FROM `response_data` WHERE `timestamp` > ? " . "GROUP BY group_date " . "ORDER BY `timestamp` DESC;";
	$rs = api_db_query_read($sql, $parameters);

	if(!$rs) {
		return api_error_raise("Unable to extract email deliverability data");
	}

	return $rs->GetAssoc();
}

/**
 * @param integer $id
 * @param null    $selector dkim selector (name of this key)
 * @param null    $keytype 'public' or 'private'
 * @return array|bool
 */
function api_email_get_dkim_keys($id, $type, $selector = null, DkimKeyTypeEnum $keytype = null) {
	if(!is_numeric($id)) {
		return api_error_raise("Invalid group id {$id}");
	}

	$sql = "SELECT *, 
       				SUBSTRING_INDEX(item,'-',-1) as key_type, 
				    SUBSTRING_INDEX(SUBSTRING_INDEX(item,'-',-2), '-', 1) as selector
				FROM key_store 				    
				WHERE type = ? 
				  AND id = ? 
				  AND item LIKE (?)			
				  ";

	$keyname = 'dkim-key%';
	if($selector) {
		$keyname = DkimKeystore::getItemName($selector) . "%";
	}

	if($keytype) {
		$sql .= " AND SUBSTRING_INDEX(item, '-', -1) = ?";
		$sql .= " ORDER BY selector";
		$rs = api_db_query_read($sql, [$type, $id, $keyname, $keytype->getValue()]);
	} else {
		$sql .= " ORDER BY selector";
		$rs = api_db_query_read($sql, [$type, $id, $keyname]);
	}
	return $rs->GetArray();
}


/**
 * @param $group_id
 * @param $selector
 * @param $from
 * @param $headers
 * @param $body
 * @return bool|string
 */
function api_email_sign_email($selector, DkimKey $dkim_private_key, $from, $headers, $body) {

    $from_domain = api_email_extract_domain($from);
	if(!$from_domain) {
		return api_error_raise(
			"Could not determine from domain for selector '{$selector}', {$from}"
		);
	}

	$signer = new DkimSigner($dkim_private_key, null, $from_domain, $selector);
	$dkimHeaders = $signer->getSignedHeaders(api_email_normalize_convert_line_breaks($body, "\r\n"), $headers);
	return $dkimHeaders;
}

/**
 * @param      $text
 * @param null $breaktype
 * @return mixed
 */
function api_email_normalize_convert_line_breaks($text, $breaktype) {
	// Normalise to \n
	$text = str_replace(["\r\n", "\r"], "\n", $text);
	// convert LE as needed
	if("\n" !== $breaktype) {
		$text = str_replace("\n", $breaktype, $text);
	}
	return $text;
}

/**
 * @param $email
 * @return string|string[]|null
 */
function api_email_extract_domain($email) {
    return strtolower(preg_replace("/[<>@]/", "", strstr($email, "@")));
}

/**
 * Retrieve the number of emails sent throug the smtp-api that a groupid has sent in
 * the given period
 *
 * Output:
 * $email['testuser'] = 3
 * $email['testuser1'] = 99
 *
 * @param DateTime $start
 * @param DateTime $end
 * @param $groupid
 * @return array
 */
function api_email_smtp_api_sendrate(DateTime $start, DateTime $end, $groupid) {

    $start = $start->format('Y-m-d H:i:s');
    $finish = $end->format('Y-m-d H:i:s');
    $email = [];

    $sql = "SELECT `userid` as `user_id`,					
				COUNT(*) AS `count`
				FROM `smtp_events` INNER JOIN `key_store` ON (`key_store`.`id` = `smtp_events`.`userid`)
				WHERE `smtp_events`.`timestamp` >= ?
					AND `smtp_events`.`timestamp` <= ?
					AND `smtp_events`.`event_type` = 'delivered'
					AND `key_store`.`type` = 'USERS'
					AND `key_store`.`item` = 'groupowner'
					AND `key_store`.`value` = ?					
				GROUP BY `user_id`";

    $rs = api_db_query_read($sql, [$start, $finish, $groupid]);

    $users = [];
    while ($array = $rs->FetchRow()) {
        if (!isset($users[$array['user_id']])) {
            $users[$array['user_id']] = api_users_idtoname($array['user_id']) ?: 0;
        }
        $email[$users[$array['user_id']]] = $array["count"];
    }

    return $email;
}

/**
 * @param $userid
 * @param $guid
 * @param $event_type
 * @param $event_data
 * @return bool
 */
function api_email_insert_smtp_event($userid, $guid, $event_type, $event_data) {
	if(!api_users_checkidexists($userid)) {
		return api_error_raise("User id '{$userid}' does not exist");
	}
	$sql = "INSERT INTO `smtp_events` (`guid`, `userid`, `event`, `event_type`) VALUES (?, ?, ?, ?)";
	return api_db_query_write($sql, [$guid, $userid, serialize($event_data), strtolower($event_type)]);
}

/**
 * @param $guid
 * @return ADORecordSet_mysqli|false
 */
function api_email_retrieve_last_smtp_event_for_guid($guid) {
	$sql = "SELECT * FROM `smtp_events` WHERE `guid` = ? ORDER BY timestamp DESC, id desc LIMIT 1";
	return api_db_query_read($sql, [$guid]);
}

/**
 * @param $
 * @return bool
 */
function api_email_remove_email_file($body_filename) {
    return unlink(SAVE_LOCATION . EMAILBODY_LOCATION . "/" . $body_filename);
}
