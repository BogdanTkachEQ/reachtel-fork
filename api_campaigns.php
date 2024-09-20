<?php
/**
 * Campaigns Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

require_once("api_db.php");

use \Services\ActivityLogger;
use Services\Campaign\Archiver\ArchiverEnum;
use Services\Campaign\Classification\CampaignClassificationEnum;
use \Services\Utils\ActivityLoggerActions;
use Services\Utils\OperatorsEnum;
use Services\Validators\CampaignNameValidator;

/**
 * Add or update campaign setting
 *
 * @param mixed  $campaignid
 * @param string $setting
 * @param mixed  $value
 * @return boolean
 */
function api_campaigns_setting_set($campaignid, $setting, $value) {

	return api_keystore_set("CAMPAIGNS", $campaignid, $setting, $value);
}

/**
 * Add or update campaign setting
 *
 * @param mixed  $campaignid
 * @param string $setting
 * @param mixed  $check
 * @param mixed  $value
 * @return boolean
 */
function api_campaigns_setting_cas($campaignid, $setting, $check, $value) {

	return api_keystore_cas("CAMPAIGNS", $campaignid, $setting, $check, $value);
}

/**
 * Delete single campaign setting
 *
 * @param mixed  $campaignid
 * @param string $setting
 * @return boolean
 */
function api_campaigns_setting_delete_single($campaignid, $setting) {

	return api_keystore_delete("CAMPAIGNS", $campaignid, $setting);
}

/**
 * Get a single campaign setting
 *
 * @param mixed  $campaignid
 * @param string $setting
 * @return mixed
 */
function api_campaigns_setting_getsingle($campaignid, $setting) {

	return api_keystore_get("CAMPAIGNS", $campaignid, $setting);
}

/**
 * Get multiple campaign settings by item
 *
 * @param mixed $campaignid
 * @param array $items
 * @return array
 */
function api_campaigns_setting_get_multi_byitem($campaignid, array $items) {

	return api_keystore_get_multi_byitem("CAMPAIGNS", $campaignid, $items);
}

/**
 * Get multiple campaign settings by id
 *
 * @param array $ids
 * @param mixed $item
 * @return array
 */
function api_campaigns_setting_get_multi_byid(array $ids, $item) {

	return api_keystore_get_multi_byid("CAMPAIGNS", $ids, $item);
}

/**
 * Get all campaign settings
 *
 * @param mixed $campaignid
 * @return array|false
 */
function api_campaigns_setting_getall($campaignid) {

	return api_keystore_getnamespace("CAMPAIGNS", $campaignid);
}

/**
 * Increment a campaign setting
 *
 * @param integer $campaignid
 * @param string  $setting
 * @return boolean
 */
function api_campaigns_setting_increment($campaignid, $setting) {

	return api_keystore_increment("CAMPAIGNS", $campaignid, $setting);
}

/**
 * Get a campaign tag values
 *
 * @param integer $campaignid
 * @param mixed   $tags
 * @return array|false
 */
function api_campaigns_tags_get($campaignid, $tags = null) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id.");
	}

	return api_tags_get('CAMPAIGNS', $campaignid, $tags);
}

/**
 * Set campaign tag values
 *
 * @param integer $campaignid
 * @param array   $tags
 * @param array   $encrypt_tags
 * @return boolean
 */
function api_campaigns_tags_set($campaignid, array $tags, array $encrypt_tags = []) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id.");
	}

	return api_tags_set('CAMPAIGNS', $campaignid, $tags, $encrypt_tags);
}

/**
 * Delete campaign tags
 *
 * @param integer $campaignid
 * @param array   $tags
 * @return boolean
 */
function api_campaigns_tags_delete($campaignid, array $tags = []) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id.");
	}

	return api_tags_delete('CAMPAIGNS', $campaignid, $tags);
}

/**
 * @param integer $campaignid
 * @return array|boolean
 */
function api_campaigns_tags_get_all_details($campaignid) {
	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id.");
	}

	return api_tags_get_existing_tag_details('CAMPAIGNS', $campaignid, true);
}

/**
 * Create a new campaign
 *
 * @param string  $name
 * @param string  $type
 * @param integer $duplicate
 * @param integer $owner
 * @return boolean
 */
function api_campaigns_add($name, $type = null, $duplicate = null, $owner = null) {

	$validator = new CampaignNameValidator();
	if (!$validator->setName($name)->isValid()) {
		return api_error_raise("Sorry, that is an invalid campaign name. 5-75 characters, numbers, hyphen and space only");
	}

	// Strip any spaces or funny characters from the beginning or end of the name
	$name = trim($name);

	if (api_campaigns_checknameexists($name)) {
		return api_error_raise("Sorry, a campaign with that name already exists.");
	}

	if (($duplicate != null) && !api_campaigns_checkidexists($duplicate)) {
		return api_error_raise("Cannot duplicate that campaign.");
	}

	if (($type == null) && ($duplicate == null)) {
		return api_error_raise("You must select either a campaign type or duplicate an existing campaign.");
	}

	$lastid = api_keystore_increment("CAMPAIGNS", 0, "nextid");

	if ($duplicate == null && !in_array($type, ['phone', 'sms', 'email', 'wash'])) {
		return api_error_raise("Invalid campaign type '{$type}'.");
	}

	if (!is_numeric($owner) && !empty($_SESSION['userid'])) {
		$owner = (int) $_SESSION['userid'];
	} elseif (!is_numeric($owner)) {
		// Default to user id 2 if nothing is set
		$owner = 2;
	}

	if (!api_users_checkidexists($owner)) {
		return api_error_raise("Campaign owner {$owner} does not exists.");
	}

	api_campaigns_setting_set($lastid, "type", $type);
	api_campaigns_setting_set($lastid, "name", $name);
	api_campaigns_setting_set($lastid, "created", time());
	api_campaigns_setting_set($lastid, "owner", $owner);
	api_campaigns_setting_set($lastid, "groupowner", api_users_setting_getsingle($owner, "groupowner"));
	api_campaigns_setting_set($lastid, "status", "DISABLED");
	api_campaigns_setting_set($lastid, "donotcontact", serialize(array()));
	api_campaigns_setting_set($lastid, "donotcontactdestination", 6);
	api_campaigns_setting_set($lastid, "noreport", "on");
	api_campaigns_setting_set($lastid, "ordered", "off");

	// RATEPLANS are deprecated. Leaving this here for backwards compatibility
	// Check if duplicate, so that we need not make query to get user's rate plan, since it will be overridden anyways
	if ($duplicate > 0 || is_null($owner)) {
		$rate_plan_id = 4;
	} else {
		$rate_plan_id = api_users_setting_getsingle($owner, USER_SETTINGS_API_RATE_PLAN) ?: 4;
	}

	api_campaigns_setting_set($lastid, "rateplanid", $rate_plan_id);
	api_campaigns_setting_set($lastid, "region", "AU");
	api_campaigns_setting_set($lastid, "sendrate", "");
	api_campaigns_setting_set($lastid, "billingmonth", date("Y-m"));
	api_campaigns_setting_set($lastid, "proofversion", 0);
	api_campaigns_setting_set($lastid, "sftpreport", "off");
	api_campaigns_setting_set($lastid, "filedelimiter", 0);
	api_campaigns_setting_set($lastid, "timezone", DEFAULT_TIMEZONE);
	api_campaigns_setting_set(
		$lastid,
		CAMPAIGN_SETTING_CLASSIFICATION,
		api_campaigns_getclassification($lastid)
	);

	if ($type == "phone") {
		api_campaigns_setting_set($lastid, "maxchannels", 5);
		api_campaigns_setting_set($lastid, "voicedid", 101);
		api_campaigns_setting_set($lastid, "redialtimeout", 60);
		api_campaigns_setting_set($lastid, "ringtime", 25);
		api_campaigns_setting_set($lastid, "ringoutlimit", 1);
		api_campaigns_setting_set($lastid, "retrylimit", 1);
		api_campaigns_setting_set($lastid, "voicesupplier", 0);
	} elseif ($type == "sms") {
		api_campaigns_setting_set($lastid, "smsdid", 13);
	} elseif ($type == "email") {
		// make PHPCS Happy
		$make_phpcs_happy = null;
	} elseif ($type == "wash") {
		api_campaigns_setting_set($lastid, "maxchannels", 150);
		api_campaigns_setting_set($lastid, "sendrate", "");
		api_campaigns_setting_set($lastid, "donotcontact", serialize(array(152)));
		api_campaigns_setting_set($lastid, "donotcontactdestination", 152);
		api_campaigns_setting_set($lastid, "voicesupplier", 0);
	}

	if ($duplicate) {
		$dontdupe = array("billingmonth", "spoollist", "status", "name", "owner", "startwhendone", "finishtime", "delayedreport1tosend", "delayedreport2tosend", "heartbeattimestamp", "created", "proofapproved", "proofsent", "proofversion", "duplicatecheck", "lastsend", "lastupload", CAMPAIGN_SETTING_BOOST_SPOOLER, CAMPAIGN_SETTING_DISABLE_DOWNLOAD);
		foreach (api_campaigns_setting_getall($duplicate) as $key => $value) {
			if (!in_array($key, $dontdupe)) {
				api_campaigns_setting_set($lastid, $key, $value);
			}
		}
		api_campaigns_setting_set($lastid, CAMPAIGN_SETTING_DUPLICATED_FROM, $duplicate);
	}

	return $lastid;
}

/**
 * Rename a campaign
 *
 * @param integer $campaignid
 * @param string  $name
 * @return boolean
 */
function api_campaigns_rename($campaignid, $name) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id.");
	}

	if (!preg_match("/^[0-9a-z\- ]{5,60}$/i", $name)) {
		return api_error_raise("Sorry, that is an invalid campaign name. 5-60 characters, numbers, hyphen and space only.");
	}

	if (api_campaigns_checknameexists($name)) {
		return api_error_raise("Sorry, a campaign with that name already exists.");
	}

	return api_campaigns_setting_set($campaignid, "name", $name);
}

/**
 * Check if a campaign name exists
 *
 * @param string $name
 * @return boolean
 */
function api_campaigns_checknameexists($name) {

	$id = api_keystore_checkkeyexists("CAMPAIGNS", "name", $name);

	return $id;
}

/**
 * Check if a campaign id exists
 *
 * @param integer $campaignid
 * @return boolean
 */
function api_campaigns_checkidexists($campaignid) {

	if (!is_numeric($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	return api_campaigns_setting_getsingle($campaignid, "name") !== false;
}

/**
 * Check if a campaign name exists, if not create it.
 *
 * @param string  $name
 * @param integer $duplicatecampaignid
 * @return integer|false
 */
function api_campaigns_checkorcreate($name, $duplicatecampaignid) {

	// Takes a campaign name ($name) and see if a campaign already exists with that name. If not, it duplicates an existing campaign ($duplicatecampaignid)

	if (empty($name)) {
		return false;
	}
	if (!is_numeric($duplicatecampaignid)) {
		return false;
	}

	$campaignid = api_campaigns_checknameexists($name);

	if (is_numeric($campaignid)) {
		return $campaignid;
	} else {
		return api_campaigns_add($name, null, $duplicatecampaignid);
	}
}

/**
 * Delete a campaign
 *
 * @param integer $campaignid
 * @return boolean
 */
function api_campaigns_delete($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	// Delete response data
	api_data_responses_delete($campaignid);

	// Delete call results data
	api_data_callresult_delete_all($campaignid);

	// Delete targets
	api_targets_delete_all($campaignid);

	// Delete merge data
	api_data_merge_delete_all($campaignid);

	// Delete settings
	api_keystore_purge("CAMPAIGNS", $campaignid);

	ActivityLogger::getInstance()->addLog(
		KEYSTORE_TYPE_CAMPAIGNS,
		ActivityLoggerActions::ACTION_CAMPAIGN_DELETE,
		'Deleted campaign ' . $campaignid,
		$campaignid
	);

	return true;
}

/**
 * Empty a campaign
 * @param integer $campaignid
 * @param array   $options
 * @return boolean
 */
function api_campaigns_empty($campaignid, array $options = []) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	$disable_campaign_commenced_check = isset($options['disable_campaign_commenced_check']) && $options['disable_campaign_commenced_check'];

	if (!$disable_campaign_commenced_check && api_campaigns_hascommenced($campaignid)) {
		return api_error_raise("Sorry, that campaign can't be emptied as it has already commenced");
	}

	//archive data
	api_campaigns_archive_data($campaignid, true);

	// empty data
	api_data_responses_delete($campaignid);
	api_data_callresult_delete_all($campaignid);
	api_data_merge_delete_all($campaignid);
	api_targets_delete_all($campaignid);

	$settings = [
		CAMPAIGN_SETTING_DUPLICATE_CHECK,
		CAMPAIGN_SETTING_PROOF_SENT,
		CAMPAIGN_SETTING_PROOF_APPROVED,
		CAMPAIGN_SETTING_LAST_SEND,
		CAMPAIGN_SETTING_UNSUB_WASHED,
		CAMPAIGN_SETTING_FINISH_TIME
	];

	// @codingStandardsIgnoreStart
	$retain_settings = isset($options['retain_settings']) ?
	(
		!is_array($options['retain_settings']) ?
			[$options['retain_settings']] :
			$options['retain_settings']
	) :
	[];
	// @codingStandardsIgnoreEnd

	foreach ($settings as $setting) {
		if (in_array($setting, $retain_settings)) {
			continue;
		}
		api_campaigns_setting_delete_single($campaignid, $setting);
	}

	ActivityLogger::getInstance()->addLog(
		KEYSTORE_TYPE_CAMPAIGNS,
		ActivityLoggerActions::ACTION_CAMPAIGN_EMPTY,
		'Emptied campaign ' . $campaignid,
		$campaignid
	);

	return true;
}

/**
 * @param boolean $campaignid
 * @param boolean $override_campaignid_check
 * @return boolean
 */
function api_campaigns_archive_data($campaignid, $override_campaignid_check = false) {
	if (!api_data_responses_archive($campaignid, $override_campaignid_check)) {
		return api_error_raise('Failed archiving the responses for campaign ' . $campaignid);
	}

	if (!api_data_callresult_archive($campaignid, $override_campaignid_check)) {
		return api_error_raise('Failed archiving the call results for campaign ' . $campaignid);
	}

	if (!api_data_merge_archive($campaignid, $override_campaignid_check)) {
		return api_error_raise('Failed archiving the merge data for campaign ' . $campaignid);
	}

	if (!api_targets_archive($campaignid, $override_campaignid_check, ArchiverEnum::MANUAL())) {
		return api_error_raise('Failed archiving the targets for campaign ' . $campaignid);
	}

	return true;
}

/**
 * List all campaigns
 *
 * @param mixed $long
 * @param mixed $userid
 * @param mixed $limit
 * @param array $options
 * @return array
 */
function api_campaigns_list_all($long = null, $userid = false, $limit = false, array $options = []) {

	if (isset($options["countonly"]) && $options["countonly"]) {
		$sql = "SELECT COUNT(`id`) as `count` FROM `key_store` WHERE `type` = ? AND `item` = ?";
	} else {
		$sql = "SELECT `id`, `value` FROM `key_store` WHERE `type` = ? AND `item` = ?";
	}

	$parameters = array("CAMPAIGNS", "name");

	if (isset($options["activeonly"]) && $options["activeonly"]) {
		$sql .= " AND `id` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` = ?)";
		array_push($parameters, "CAMPAIGNS", "status", "ACTIVE");
	}

	if (is_numeric($userid) && api_users_checkidexists($userid)) {
		$groups = api_security_groupaccess($userid);

		if (isset($groups["isadmin"]) && (!$groups["isadmin"]) && count($groups["groups"])) {
			$sql .= " AND `id` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` IN (";
			array_push($parameters, "CAMPAIGNS", "groupowner");
			foreach ($groups["groups"] as $group) {
				$sql .= "?,";
				array_push($parameters, $group);
			}
			$sql = substr($sql, 0, -1) . "))";
		}
	} elseif (isset($options['groupid']) && is_numeric($options['groupid'])) {
		$sql .= " AND `id` IN (SELECT `id` FROM `key_store` " .
				"WHERE `type` = 'CAMPAIGNS' AND `item` = 'groupowner' AND `value` = ?)";
		array_push($parameters, $options['groupid']);
	}

	if (isset($options['campaigntypes'])) {
		$sql .= " AND `id` IN (SELECT `id` FROM `key_store` " .
			"WHERE `type` = 'CAMPAIGNS' AND `item` = 'type' AND `value` IN(";
		foreach ($options['campaigntypes'] as $type) {
			$sql .= "?,";
			array_push($parameters, $type);
		}
		$sql = substr($sql, 0, -1) . "))";
	}

	if (isset($options["search"]) && !empty($options["search"])) {
		if (strpos($options["search"], "*") !== false) {
			$options["search"] = str_replace("\\*", ".+", preg_quote($options["search"], "/"));
		} else {
			$options["search"] = preg_quote($options["search"], "/");
		}

		$sql .= " AND `value` REGEXP ?";
		array_push($parameters, $options["search"]);
	}

	if (isset($options["regex"]) && !empty($options["regex"])) {
		$sql .= " AND `value` REGEXP ?";
		array_push($parameters, $options["regex"]);
	}

	$sql .= " ORDER BY `id` DESC";

	if (is_numeric($limit) && (!isset($options["countonly"]) || (!$options["countonly"]))) {
		$sql .= " LIMIT ?";
		array_push($parameters, (int)$limit);

		if (isset($options["offset"]) && is_numeric($options["offset"])) {
			$sql .= " OFFSET ?";
			array_push($parameters, (int)$options["offset"]);
		}
	}

	$rs = api_db_query_read($sql, $parameters);

	if (isset($options["countonly"]) && $options["countonly"]) {
		return $rs->Fields("count");
	}

	if ($rs && ($rs->RecordCount() > 0)) {
		$results = $rs->GetAssoc();

		if ($long) {
			return $results;
		} else {
			return array_keys($results);
		}
	} else {
		return array();
	}
}

/**
 * List all active campaigns
 *
 * @return array|false
 */
function api_campaigns_list_active() {

	return api_keystore_getidswithvalue("CAMPAIGNS", "status", "ACTIVE");
}

/**
 * Convert a campaign name to id
 *
 * @param mixed $name
 * @return integer|false
 */
function api_campaigns_nametoid($name) {

	$id = api_campaigns_checknameexists($name);

	if (is_numeric($id)) {
		return $id;
	} else {
		return false;
	}
}

/**
 * Check if a campaign has data loaded
 *
 * @param integer $campaignid
 * @return integer|false
 */
function api_campaigns_hasdata($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	$sql = "SELECT * FROM `targets` WHERE `campaignid` = ? LIMIT 1";
	$rs = api_db_query_read($sql, array($campaignid));

	if (!$rs || !$rs->RecordCount()) {
		return false;
	} else {
		return true;
	}
}

/**
 * Check if a campaign has commenced - either it is active or it has call_results
 *
 * @param integer $campaignid
 * @return integer|false
 */
function api_campaigns_hascommenced($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (api_campaigns_setting_getsingle($campaignid, "status") == "ACTIVE") {
		return true;
	}

	$sql = "SELECT * FROM `call_results` WHERE `campaignid` = ? LIMIT 1";
	$rs = api_db_query_read($sql, array($campaignid));

	if (!$rs || !$rs->RecordCount()) {
		return false;
	} else {
		return true;
	}
}

/**
 * Convert a campaign name to id
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @return boolean
 */
function api_campaigns_summaryemail($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	$targets_status = api_data_target_status($campaignid);

	$settings = api_campaigns_setting_getall($campaignid);

	if (!empty($settings["emailreport"])) {
		$email["to"] = $settings["emailreport"];
	} elseif (empty($settings["sftpreport"]) || ($settings["sftpreport"] != "on")) {
		return true; // Skip generating the report as there are no recipients
	}

	if ($settings["type"] == "phone") {
		$contentTXT = "Voice campaign";
		$contentHTML = "Voice campaign";
	} elseif ($settings["type"] == "sms") {
		$contentTXT = $settings["content"];
		$contentHTML = $settings["content"];
	} elseif ($settings["type"] == "wash") {
		$contentTXT = "Number validation";
		$contentHTML = "Number validation";

		$targets_status["COMPLETE"] = $targets_status["COMPLETE"] + $targets_status["ABANDONED"];
		$targets_status["ABANDONED"] = 0;
	} elseif ($settings["type"] == "email") {
		$hosttrack = api_hosts_gettrack();

		$contentTXT = "{$hosttrack}/view.php?dv=" . api_misc_crypt_safe($campaignid);
		$contentHTML = "<a href=\"{$hosttrack}/view.php?dv=" . api_misc_crypt_safe($campaignid) . "\">Email Template</a>";
	}

	if (!empty($settings["finishtime"])) {
		$date = new DateTime("@" . $settings["finishtime"]);

		$date->setTimezone(api_campaigns_gettimezone($campaignid));

		$finishTime = $date->format("l, j F Y, H:i:s T");
	} elseif ($settings["status"] == "DISABLED") {
		$finishTime = "Incomplete";
	} else {
		$finishTime = "Still in progress";
	}

	$attachmentMessageTXT = "The campaign report is available for download from the ReachTEL Monitor portal 'https://monitor.reachtel.com.au/campaign/" . api_misc_crypt_safe($campaignid) . "/external/report/download'.";
	$attachmentMessageHTML = "The campaign report is available for download from the <a href=\"https://monitor.reachtel.com.au/campaign/" . api_misc_crypt_safe($campaignid) . "/external/report/download\">ReachTEL Monitor portal</a>.";

	if (!empty($settings["sftpreport"]) && ($settings["sftpreport"] == "on")) {
		switch ($settings["type"]) {
			case "phone":
				$report = api_campaigns_report_summary_phone($campaignid);
				break;
			case "sms":
				$report = api_campaigns_report_summary_sms($campaignid);
				break;
			case "email":
				$report = api_campaigns_report_summary_email($campaignid);
				break;
			case "wash":
				$report = api_campaigns_report_summary_wash($campaignid);
				break;
		}

		// Check report is not empty
		if (isset($report['content']) && $report['content']) {
			if (!empty($settings["pgpemail"])) {
				$email["attachments"][] = api_misc_pgp_encrypt($report, $settings["pgpemail"]);
			} else {
				$email["attachments"][] = $report;
			}

			$options = array("hostname" => api_campaigns_tags_get($campaignid, "sftp-hostname"),
				"username" => api_campaigns_tags_get($campaignid, "sftp-username"),
				"password" => api_campaigns_tags_get($campaignid, "sftp-password"),
				"port" => api_campaigns_tags_get($campaignid, "sftp-port"));

			if (!empty($options["hostname"])) {
				foreach ($email["attachments"] as $attachment) {
					$temp = tempnam("/tmp", "morpheus-sftp");

					file_put_contents($temp, $attachment["content"]);

					$options["localfile"] = $temp;
					$options["remotefile"] = api_campaigns_tags_get($campaignid, "sftp-path") . $attachment["filename"];

					$result = api_misc_sftp_put($options);

					unlink($temp);

					if (!$result) {
						$attachmentMessageTXT = $attachmentMessageHTML = "We were unable to upload a campaign report to the specified SFTP server. Please contact support for more information.";
						api_error_raise("Sorry, we were unable to SFTP the report for campaign - unable to upload");
					} else {
						$attachmentMessageTXT = $attachmentMessageHTML = "A campaign report has been uploaded to the specified SFTP server.";
					}
				}
			} else {
				$attachmentMessageTXT = $attachmentMessageHTML = "We were unable to upload a campaign report to the specified SFTP server. Please contact support for more information.";
				api_error_raise("Sorry, we were unable to SFTP the report for this campaign - no credentials");
			}

			unset($email["attachments"]);
		} else {
			$attachmentMessageTXT = $attachmentMessageHTML = "The campaign report has been generated for this campaign, but does not contain any data.";
		}
	}

	if (empty($email["to"])) {
		return true;
	}

	$email["subject"] = "[ReachTEL] Campaign report - " . $settings["name"];
	$email["textcontent"] = "Hello,\n\nA campaign report has been generated for this campaign. Please find the breakdown of targets below:\n\nCampaign:\t\t" . $settings["name"] . "\n\nTargets loaded:\t\t" . number_format($targets_status["TOTAL"]) . "\nCompleted successfully:\t" . number_format($targets_status["COMPLETE"]) . "\nAbandoned:\t\t" . number_format($targets_status["ABANDONED"]) . "\nCompletion time:\t\t" . $finishTime . "\nContent: " . $contentTXT . "\n\n" . $attachmentMessageTXT;
	$email["htmlcontent"] = "Hello,\n\nA campaign report has been generated for this campaign. Please find the breakdown of targets below:\n\n<table><tr><td style=\"width: 175px; text-align: right;\">Campaign:</td><td><span style=\"color: red;\">" . $settings["name"] . "</span></tr><tr><td style=\"width: 175px; text-align: right;\">Targets loaded:</td><td><span style=\"color: red;\">" . number_format($targets_status["TOTAL"]) . "</span></td></tr><tr><td style=\"width: 175px; text-align: right;\">Completed successfully:</td><td><span style=\"color: red;\">" . number_format($targets_status["COMPLETE"]) . "</span></td></tr><tr><td style=\"width: 175px; text-align: right;\">Abandoned:</td><td><span style=\"color: red;\">" . number_format($targets_status["ABANDONED"]) . "</span></td></tr><tr><td style=\"width: 175px; text-align: right;\">Completion time:</td><td><span style=\"color: red;\">" . $finishTime . "</span></td></tr><tr><td style=\"width: 175px; text-align: right;\">Content:</td><td><span style=\"color: red;\">" . $contentHTML . "</span></td></tr></table>\n\n" . $attachmentMessageHTML;
	$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

	return api_email_template($email);
}

/**
 * Generate cumulative report as string
 *
 * @param integer $campaign_ids
 * @param string  $type
 * @param array   $general_options
 *
 * @return false|string
 */
function api_campaigns_report_cumulative($campaign_ids, $type, array $general_options = []) {
	$data = api_campaigns_report_cumulative_array($campaign_ids, $type, $general_options);

	return $data ? api_csv_string($data) : false;
}

/**
 * Generate cumulative report as array
 *
 * @param integer $campaign_ids
 * @param string  $type
 * @param array   $general_options
 *
 * @return false|array
 */
function api_campaigns_report_cumulative_array($campaign_ids, $type, array $general_options = []) {
	$data = [];
	$contentHeaders = [];

	foreach ((array) $campaign_ids as $campaign_id) {
		$name = api_campaigns_setting_getsingle($campaign_id, 'name');
		$options = array_merge(
			['extra_columns' => [['header' => 'campaign', 'value' => $name]]],
			$general_options
		);

		switch ($type) {
			case 'sms':
				$summary = api_campaigns_report_summary_sms_array($campaign_id, $options);
				break;

			case 'email':
				$summary = api_campaigns_report_summary_email_array($campaign_id, $options);
				break;

			case 'phone':
				$summary = api_campaigns_report_summary_phone_array($campaign_id, $options);
				break;

			default:
				return api_error_raise("Sorry, $type is not a valid type");
		}

		if ($summary) {
			$contentHeaders = array_unique(array_merge($contentHeaders, array_keys($summary[0])));
			$data[] = $summary;
		}
	}

	if (!$contentHeaders) {
		return api_error_raise("No data to generate report");
	}

	$merged = [$contentHeaders];
	foreach ($data as $csv) {
		foreach ($csv as $r) {
			$row = [];
			foreach ($contentHeaders as $header) {
				$row[$header] = isset($r[$header]) ? $r[$header] : null;
			}
			$merged[] = $row;
		}
	}

	return $merged;
}

/**
 * Send report summary sms
 *
 * @codeCoverageIgnore
 * @param string $campaignid
 * @param mixed  $options
 * @return array | boolean
 */
function api_campaigns_report_summary_sms($campaignid, $options = array()) {
	$contentArray = api_campaigns_report_summary_sms_array($campaignid, $options);

	if (DB_USE == "gui") {
		set_time_limit((count($contentArray) > 2000) ? ceil(count($contentArray) / 100) : 20);
	}

	$delimiter = api_campaigns_setting_getsingle($campaignid, "filedelimiter");

	if ((int)$delimiter !== 0) {
		$extension = ".txt";
	} else {
		$extension = ".csv";
	}

	$headers = $contentArray ? array_keys(current($contentArray)) : [];
	array_unshift($contentArray, $headers);

	$contents = api_csv_string($contentArray, api_data_get_delimiter($delimiter));

	if (count($contentArray) > 10000) {
		return api_misc_zipfile(array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension));
	}

	return array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension);
}

/**
 * Returns array of sms summary report data
 * @param integer $campaignid
 * @param array   $options
 * @return array|boolean
 */
function api_campaigns_report_summary_sms_array($campaignid, array $options = array()) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (empty($options["start"])) {
		$options["start"] = null;
	}
	if (empty($options["end"])) {
		$options["end"] = null;
	}

	$results = api_data_responses_sms_report(
		$campaignid,
		$options["start"],
		$options["end"],
		false,
		(isset($options['return_sms_content']) && $options['return_sms_content'])
	);

	$questions = api_data_responses_getquestions($campaignid);
	$mergefields = api_data_merge_stats($campaignid);

	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ['reportformatcompleteoverride', 'rateplanid']);

	if (isset($settings['reportformatcompleteoverride'])) {
		$override = $settings['reportformatcompleteoverride'];
	} elseif (isset($options['reportformatcompleteoverride'])) {
		$override = $options['reportformatcompleteoverride'];
	} else {
		$override = null;
	}

	$rateplanid = isset($settings['rateplanid']) ? (int) $settings['rateplanid'] : null;

	if (!empty($override)) {
		$format = array_unique(explode(",", $override));
	}

	$contentArray = [];
	$row = 0;

	foreach ($results as $targetid => $result) {
		if (isset($format) && is_array($format)) {
			foreach ($format as $column) {
				if (in_array($column, ['targetkey', 'uniqueid'])) {
					$contentArray[$row][$column] = $result["targetkey"];
				} elseif ($column == "destination") {
					$contentArray[$row][$column] = $result["destination"];
				} elseif ($column == "status") {
					$contentArray[$row][$column] = $result["status"];
				} elseif ($column == "cost") {
					if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
						$contentArray[$row][$column] = $result["cost"];
					}
				} elseif (isset($result["response_data"]) && is_array($result["response_data"]) && isset($result["response_data"][$column])) {
					$contentArray[$row][$column] = $result["response_data"][$column];
				} elseif (isset($result["merge_data"]) && is_array($result["merge_data"]) && isset($result["merge_data"][$column])) {
					$contentArray[$row][$column] = $result["merge_data"][$column];
				} else {
					$contentArray[$row][$column] = '';
				}
			}
		} else {
			foreach (['targetkey' => 'UNIQUEID', 'destination' => 'DESTINATION', 'status' => 'STATUS'] as $item => $header) {
				$contentArray[$row][$header] = $result[$item];
			}

			if (isset($questions) && is_array($questions)) {
				foreach ($questions as $value) {
					if (isset($result["response_data"][$value])) {
						$contentArray[$row][$value] = $result['response_data'][$value];
					} else {
						$contentArray[$row][$value] = '';
					}
				}
			}

			if (isset($mergefields) && is_array($mergefields)) {
				foreach ($mergefields as $field => $value) {
					if (isset($result["merge_data"][$value["element"]])) {
						$contentArray[$row][$value['element']] = $result["merge_data"][$value["element"]];
					} else {
						$contentArray[$row][$value['element']] = '';
					}
				}
			}

			if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
				if (isset($result["cost"])) {
					$contentArray[$row]['COST'] = $result["cost"];
				} else {
					$contentArray[$row]['COST'] = '';
				}
			}
		}

		if (isset($options['extra_columns'])) {
			foreach ($options['extra_columns'] as $extraColumn) {
				if (is_callable($extraColumn['value'])) {
					$value = $extraColumn['value']($campaignid);
					$contentArray[$row][$extraColumn['header']] = $value;
				} else {
					$contentArray[$row][$extraColumn['header']] = $extraColumn['value'];
				}
			}
		}

		// Add target id to response
		if (isset($options['return_target_id']) && $options['return_target_id'] === true) {
			$contentArray[$row]['TARGETID'] = $targetid;
		}

		if (isset($result['content'])) {
			$contentArray[$row]['sms_content'] = $result['content'];
		}

		$row++;
	}

	return $contentArray;
}

/**
 * Send report summary wash
 *
 * @codeCoverageIgnore
 * @param string $campaignid
 * @param mixed  $options
 * @return boolean
 */
function api_campaigns_report_summary_wash($campaignid, $options = array()) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (empty($options["start"])) {
		$options["start"] = null;
	}
	if (empty($options["end"])) {
		$options["end"] = null;
	}

	$results = api_data_responses_wash_report($campaignid, $options["start"], $options["end"]);

	if (count($results) > 2000) {
		$timeLimit = ceil(count($results) / 100);
	} else {
		$timeLimit = 20;
	}

	if (DB_USE == "gui") {
		set_time_limit($timeLimit);
	}

	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ['filedelimiter', 'reportformatcompleteoverride', 'rateplanid']);
	$override = isset($settings['reportformatcompleteoverride']) ? $settings['reportformatcompleteoverride'] : null;
	$rateplanid = isset($settings['rateplanid']) ? (int) $settings['rateplanid'] : null;
	$delimiter = isset($settings['filedelimiter']) ? $settings['filedelimiter'] : null;

	if ((int)$delimiter !== 0) {
		$extension = ".txt";
	} else {
		$extension = ".csv";
	}

	$questions = api_data_responses_getquestions($campaignid);
	$mergefields = api_data_merge_stats($campaignid);

	if (!empty($override)) {
		$format = array_unique(explode(",", $override));

		$contents = "";

		if (is_array($format)) {
			foreach ($format as $column) {
				$contents .= api_data_delimit($column, $delimiter);
			}
		}

		$contents .= "\r\n";
	} else {
		$contents = api_data_delimit("targetkey", $delimiter) . api_data_delimit("destination", $delimiter);

		if ($questions) {
			foreach ($questions as $value) {
				$contents .= api_data_delimit($value, $delimiter);
			}
		}

		if ($mergefields) {
			foreach ($mergefields as $field => $value) {
				$contents .= api_data_delimit($value["element"], $delimiter);
			}
		}

		if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
			$contents .= api_data_delimit("cost", $delimiter);
		}

		$contents .= "\r\n";
	}

	foreach ($results as $targetid => $result) {
		$line = '';
		if (isset($format) && is_array($format)) {
			foreach ($format as $column) {
				if ($column == "targetkey") {
					$line .= api_data_delimit($result["targetkey"], $delimiter);
				} elseif ($column == "uniqueid") {
					$line .= api_data_delimit($result["targetkey"], $delimiter);
				} elseif ($column == "destination") {
					$line .= api_data_delimit($result["destination"], $delimiter);
				} elseif ($column == "status") {
					if (isset($result["response_data"][$column])) {
						$line .= api_data_delimit($result["response_data"][$column], $delimiter);
					} else {
						$line .= api_data_delimit(null, $delimiter);
					}
				} elseif ($column == "cost") {
					if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
						$line .= api_data_delimit($result["cost"], $delimiter);
					}
				} elseif (isset($result["response_data"]) && is_array($result["response_data"]) && isset($result["response_data"][$column])) {
					$line .= api_data_delimit($result["response_data"][$column], $delimiter);
				} elseif (isset($result["merge_data"]) && is_array($result["merge_data"]) && isset($result["merge_data"][$column])) {
					$line .= api_data_delimit($result["merge_data"][$column], $delimiter);
				} else {
					$line .= api_data_delimit(null, $delimiter);
				}
			}
		} else {
			$line .= api_data_delimit($result["targetkey"], $delimiter) . api_data_delimit($result["destination"], $delimiter);

			if (isset($questions) && is_array($questions)) {
				foreach ($questions as $value) {
					if (isset($result["response_data"][$value])) {
						$line .= api_data_delimit($result["response_data"][$value], $delimiter);
					} else {
						$line .= api_data_delimit(null, $delimiter);
					}
				}
			}

			if (isset($mergefields) && is_array($mergefields)) {
				foreach ($mergefields as $field => $value) {
					if (isset($result["merge_data"][$value["element"]])) {
						$line .= api_data_delimit($result["merge_data"][$value["element"]], $delimiter);
					} else {
						$line .= api_data_delimit(null, $delimiter);
					}
				}
			}

			if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
				if (isset($result["cost"])) {
					$line .= api_data_delimit($result["cost"], $delimiter);
				} else {
					$line .= api_data_delimit("", $delimiter);
				}
			}
		}

		$contents .= str_ireplace("\r", '', $line) . "\r\n";
	}

	if (count($results) > 10000) {
		return api_misc_zipfile(array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension));
	} else {
		return array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension);
	}
}

/**
 * Send report summary email
 *
 * @codeCoverageIgnore
 * @param string $campaignid
 * @param mixed  $options
 * @return boolean
 */
function api_campaigns_report_summary_email($campaignid, $options = array()) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (empty($options["start"])) {
		$options["start"] = null;
	}
	if (empty($options["end"])) {
		$options["end"] = null;
	}

	$results = api_data_responses_email_report($campaignid, $options["start"], $options["end"]);

	if (count($results) > 2000) {
		$timeLimit = ceil(count($results) / 100);
	} else {
		$timeLimit = 20;
	}

	if (DB_USE == "gui") {
		set_time_limit($timeLimit);
	}

	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ['filedelimiter', 'reportformatcompleteoverride', 'rateplanid']);
	$override = isset($settings['reportformatcompleteoverride']) ? $settings['reportformatcompleteoverride'] : null;
	$rateplanid = isset($settings['rateplanid']) ? (int) $settings['rateplanid'] : null;
	$delimiter = isset($settings['filedelimiter']) ? $settings['filedelimiter'] : null;

	if ((int)$delimiter !== 0) {
		$extension = ".txt";
	} else {
		$extension = ".csv";
	}

	$questions = api_data_responses_getquestions($campaignid);
	$mergefields = api_data_merge_stats($campaignid);

	if (!empty($override)) {
		$format = array_unique(explode(",", $override));

		$contents = "";

		if (is_array($format)) {
			foreach ($format as $column) {
				$contents .= api_data_delimit($column, $delimiter);
			}
		}

		$contents .= "\r\n";
	} else {
		$contents = api_data_delimit("UNIQUEID", $delimiter) . api_data_delimit("DESTINATION", $delimiter) . api_data_delimit("STATUS", $delimiter);

		if ($questions) {
			foreach ($questions as $value) {
				$contents .= api_data_delimit($value, $delimiter);
			}
		}

		if ($mergefields) {
			foreach ($mergefields as $field => $value) {
				$contents .= api_data_delimit($value["element"], $delimiter);
			}
		}

		if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
			$contents .= api_data_delimit("COST", $delimiter);
		}
		$contents .= "\r\n";
	}

	if ($results) {
		foreach ($results as $targetid => $result) {
			if (isset($format) && is_array($format)) {
				foreach ($format as $column) {
					if ($column == "targetkey") {
						$contents .= api_data_delimit($result["targetkey"], $delimiter);
					} elseif ($column == "uniqueid") {
						$contents .= api_data_delimit($result["targetkey"], $delimiter);
					} elseif ($column == "destination") {
						$contents .= api_data_delimit($result["destination"], $delimiter);
					} elseif ($column == "status") {
						$contents .= api_data_delimit($result["status"], $delimiter);
					} elseif ($column == "cost") {
						if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
							$contents .= api_data_delimit($result["cost"], $delimiter);
						}
					} elseif (isset($result["response_data"]) && is_array($result["response_data"]) && isset($result["response_data"][$column])) {
						$contents .= api_data_delimit($result["response_data"][$column], $delimiter);
					} elseif (isset($result["merge_data"]) && is_array($result["merge_data"]) && isset($result["merge_data"][$column])) {
						$contents .= api_data_delimit($result["merge_data"][$column], $delimiter);
					} else {
						$contents .= api_data_delimit(null, $delimiter);
					}
				}
			} else {
				$contents .= api_data_delimit($result["targetkey"], $delimiter) . api_data_delimit($result["destination"], $delimiter) . api_data_delimit($result["status"], $delimiter);

				if (is_array($questions)) {
					foreach ($questions as $value) {
						if (isset($result["response_data"][$value])) {
							$contents .= api_data_delimit($result["response_data"][$value], $delimiter);
						} else {
							$contents .= api_data_delimit(null, $delimiter);
						}
					}
				}

				if (is_array($mergefields)) {
					foreach ($mergefields as $field => $value) {
						if (isset($result["merge_data"][$value["element"]])) {
							$contents .= api_data_delimit($result["merge_data"][$value["element"]], $delimiter);
						} else {
							$contents .= api_data_delimit(null, $delimiter);
						}
					}
				}

				if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
					$contents .= api_data_delimit($result["cost"], $delimiter);
				}
			}

			$contents .= "\r\n";
		}
	}

	if (count($results) > 10000) {
		return api_misc_zipfile(array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension));
	} else {
		return array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension);
	}
}

/**
 * Returns array of email summary report data
 * @param integer $campaignid
 * @param array   $options
 * @return array|boolean
 * @todo TECHNICAL DEBT: This function is almost duplicate of api_campaigns_report_summary_email and has been added
 *       because of tight deadline.api_campaigns_report_summary_email() needs to be refactored to use this function
 */
function api_campaigns_report_summary_email_array($campaignid, array $options = array()) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (empty($options["start"])) {
		$options["start"] = null;
	}
	if (empty($options["end"])) {
		$options["end"] = null;
	}

	$results = api_data_responses_email_report($campaignid, $options["start"], $options["end"]);

	$questions = api_data_responses_getquestions($campaignid);
	$mergefields = api_data_merge_stats($campaignid);

	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ['reportformatcompleteoverride', 'rateplanid']);
	$override = isset($settings['reportformatcompleteoverride']) ? $settings['reportformatcompleteoverride'] : null;
	$rateplanid = isset($settings['rateplanid']) ? (int) $settings['rateplanid'] : null;

	if (!empty($override)) {
		$format = array_unique(explode(",", $override));
	}

	$contentArray = [];
	$row = 0;

	if ($results) {
		foreach ($results as $targetid => $result) {
			if (isset($format) && is_array($format)) {
				foreach ($format as $column) {
					if (in_array($column, ['targetkey', 'uniqueid'])) {
						$contentArray[$row][$column] = $result["targetkey"];
					} elseif ($column == "destination") {
						$contentArray[$row][$column] = $result["destination"];
					} elseif ($column == "status") {
						$contentArray[$row][$column] = $result["status"];
					} elseif ($column == "cost") {
						if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
							$contentArray[$row][$column] = $result["cost"];
						}
					} elseif (isset($result["response_data"]) && is_array($result["response_data"]) && isset($result["response_data"][$column])) {
						$contentArray[$row][$column] = $result["response_data"][$column];
					} elseif (isset($result["merge_data"]) && is_array($result["merge_data"]) && isset($result["merge_data"][$column])) {
						$contentArray[$row][$column] = $result["merge_data"][$column];
					} else {
						$contentArray[$row][$column] = '';
					}
				}
			} else {
				foreach (['targetkey', 'destination', 'status'] as $item) {
					$contentArray[$row][$item] = $result[$item];
				}

				if (is_array($questions)) {
					foreach ($questions as $value) {
						if (isset($result["response_data"][$value])) {
							$contentArray[$row][$value] = $result['response_data'][$value];
						} else {
							$contentArray[$row][$value] = '';
						}
					}
				}

				if (is_array($mergefields)) {
					foreach ($mergefields as $field => $value) {
						if (isset($result["merge_data"][$value["element"]])) {
							$contentArray[$row][$value["element"]] = $result["merge_data"][$value["element"]];
						} else {
							$contentArray[$row][$value["element"]] = '';
						}
					}
				}

				if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
					$contentArray[$row]['COST'] = $result["cost"];
				}
			}

			if (isset($options['extra_columns'])) {
				foreach ($options['extra_columns'] as $extraColumn) {
					if (is_callable($extraColumn['value'])) {
						$value = $extraColumn['value']($campaignid);
						$contentArray[$row][$extraColumn['header']] = $value;
					} else {
						$contentArray[$row][$extraColumn['header']] = $extraColumn['value'];
					}
				}
			}

			$row++;
		}
	}

	return $contentArray;
}

/**
 * Send report summary phone
 *
 * @codeCoverageIgnore
 * @param string $campaignid
 * @param mixed  $options
 * @return boolean
 */
function api_campaigns_report_summary_phone($campaignid, $options = array()) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (empty($options["start"])) {
		$options["start"] = null;
	}
	if (empty($options["end"])) {
		$options["end"] = null;
	}

	$results = api_data_responses_phone_report($campaignid, $options["start"], $options["end"]);

	if (count($results) > 2000) {
		$timeLimit = ceil(count($results) / 100);
	} else {
		$timeLimit = 20;
	}

	if (DB_USE == "gui") {
		set_time_limit($timeLimit);
	}

	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ['filedelimiter', 'reportformatcompleteoverride', 'rateplanid']);
	$override = isset($settings['reportformatcompleteoverride']) ? $settings['reportformatcompleteoverride'] : null;
	$rateplanid = isset($settings['rateplanid']) ? (int) $settings['rateplanid'] : null;
	$delimiter = isset($settings['filedelimiter']) ? $settings['filedelimiter'] : null;

	if ((int)$delimiter !== 0) {
		$extension = ".txt";
	} else {
		$extension = ".csv";
	}

	$questions = api_data_responses_getquestions($campaignid);
	$mergefields = api_data_merge_stats($campaignid);

	if (!empty($override)) {
		$format = array_unique(explode(",", $override));

		$contents = "";

		if (is_array($format)) {
			foreach ($format as $column) {
				$contents .= api_data_delimit($column, $delimiter);
			}
		}

		$contents .= "\r\n";
	} else {
		$contents = api_data_delimit("UNIQUEID", $delimiter) . api_data_delimit("DESTINATION", $delimiter) . api_data_delimit("STATUS", $delimiter) . api_data_delimit("DISCONNECTED", $delimiter);

		if ($questions) {
			foreach ($questions as $value) {
				$contents .= api_data_delimit($value, $delimiter);
			}
		}

		if ($mergefields) {
			foreach ($mergefields as $field => $value) {
				$contents .= api_data_delimit($value["element"], $delimiter);
			}
		}

		if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
			$contents .= api_data_delimit("COST", $delimiter);
		}
		$contents .= api_data_delimit("DURATIONS ->", $delimiter) . "\r\n";
	}

	$sql = "SELECT `targetid` FROM `call_results` WHERE `campaignid` = ? AND `value` = ?";
	$rs = api_db_query_read($sql, array($campaignid, "DISCONNECTED"));

	while ($array = $rs->FetchRow()) {
		$disconnected[$array["targetid"]] = true;
	}

	if ($results) {
		foreach ($results as $targetid => $result) {
			if (isset($disconnected) && is_array($disconnected) && isset($disconnected[$targetid])) {
				$dc = "YES";
			} else {
				$dc = "";
			}

			if (isset($format) && is_array($format)) {
				foreach ($format as $column) {
					if ($column == "targetkey") {
						$contents .= api_data_delimit($result["targetkey"], $delimiter);
					} elseif ($column == "uniqueid") {
						$contents .= api_data_delimit($result["targetkey"], $delimiter);
					} elseif ($column == "destination") {
						$contents .= api_data_delimit($result["destination"], $delimiter);
					} elseif ($column == "status") {
						$contents .= api_data_delimit($result["status"], $delimiter);
					} elseif ($column == "disconnected") {
						$contents .= api_data_delimit($dc, $delimiter);
					} elseif ($column == "cost") {
						if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
							$contents .= api_data_delimit($result["cost"], $delimiter);
						}
					} elseif ($column == "durations ->") {
						if (isset($result["events"])) {
							foreach ($result["events"] as $eventid => $event) {
								if (isset($event["billsec"]) && ($event["billsec"] > 0)) {
									$contents .= api_data_delimit($event["billsec"], $delimiter);
								} else {
									$contents .= api_data_delimit(null, $delimiter);
								}
							}
						}
					} elseif (isset($result["response_data"]) && is_array($result["response_data"]) && isset($result["response_data"][$column])) {
						$contents .= api_data_delimit($result["response_data"][$column], $delimiter);
					} elseif (isset($result["merge_data"]) && is_array($result["merge_data"]) && isset($result["merge_data"][$column])) {
						$contents .= api_data_delimit($result["merge_data"][$column], $delimiter);
					} else {
						$contents .= api_data_delimit(null, $delimiter);
					}
				}
			} else {
				if (empty($result["targetkey"])) {
					continue;
				}

						$contents .= api_data_delimit($result["targetkey"], $delimiter) . api_data_delimit($result["destination"], $delimiter) . api_data_delimit($result["status"], $delimiter) . api_data_delimit($dc, $delimiter);

				if (isset($questions)) {
					foreach ($questions as $value) {
						if (isset($result["response_data"]) && (isset($result["response_data"][$value]))) {
							$contents .= api_data_delimit($result["response_data"][$value], $delimiter);
						} else {
							$contents .= api_data_delimit(null, $delimiter);
						}
					}
				};

				if (isset($mergefields) && is_array($mergefields)) {
					foreach ($mergefields as $field => $value) {
						if ((isset($result["merge_data"][$value["element"]])) && ($result["merge_data"][$value["element"]] != null)) {
							$contents .= api_data_delimit($result["merge_data"][$value["element"]], $delimiter);
						} else {
							$contents .= api_data_delimit(null, $delimiter);
						}
					}
				}

				if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
						$contents .= api_data_delimit($result["cost"], $delimiter);
				}

				if (isset($result["events"])) {
					foreach ($result["events"] as $eventid => $event) {
						if (isset($event["billsec"]) && ($event["billsec"] > 0)) {
							$contents .= api_data_delimit($event["billsec"], $delimiter);
						} else {
							$contents .= api_data_delimit(null, $delimiter);
						}
					}
				}
			}

					$contents .= "\r\n";
		}
	}

	if (count($results) > 10000) {
		return api_misc_zipfile(array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension));
	} else {
		return array("content" => $contents, "filename" => api_campaigns_setting_getsingle($campaignid, "name") . $extension);
	}
}

/**
 * Returns array of phone summary report data
 *
 * @param integer $campaignid
 * @param array   $options    Options ['start','end','max_events','all_durations','reportformatcompleteoverride'].
 * @return array|boolean
 * @todo TECHNICAL DEBT: This function is almost duplicate of api_campaigns_report_summary_phone and has been added
 *       because of tight deadline.api_campaigns_report_summary_phone() needs to be refactored to use this function
 */
function api_campaigns_report_summary_phone_array($campaignid, array $options = array()) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that is not a valid campaign id");
	}

	if (empty($options["start"])) {
		$options["start"] = null;
	}
	if (empty($options["end"])) {
		$options["end"] = null;
	}

	$results = api_data_responses_phone_report($campaignid, $options["start"], $options["end"]);

	$questions = api_data_responses_getquestions($campaignid);
	$mergefields = api_data_merge_stats($campaignid);

	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ['reportformatcompleteoverride', 'rateplanid']);

	if (isset($settings['reportformatcompleteoverride'])) {
		$override = $settings['reportformatcompleteoverride'];
	} elseif (isset($options['reportformatcompleteoverride'])) {
		$override = $options['reportformatcompleteoverride'];
	} else {
		$override = null;
	}

	$rateplanid = isset($settings['rateplanid']) ? (int) $settings['rateplanid'] : null;

	if (!empty($override)) {
		$format = array_unique(explode(",", $override));
	}

	$sql = "SELECT `targetid` FROM `call_results` WHERE `campaignid` = ? AND `value` = ?";
	$rs = api_db_query_read($sql, array($campaignid, "DISCONNECTED"));

	while ($array = $rs->FetchRow()) {
		$disconnected[$array["targetid"]] = true;
	}

	$contentArray = [];
	$row = 0;

	if ($results) {
		foreach ($results as $targetid => $result) {
			if (isset($disconnected) && is_array($disconnected) && isset($disconnected[$targetid])) {
				$dc = "YES";
			} else {
				$dc = "";
			}

			if (isset($format) && is_array($format)) {
				foreach ($format as $column) {
					if (in_array($column, ['targetkey', 'uniqueid'])) {
						$contentArray[$row][$column] = $result["targetkey"];
					} elseif ($column == "destination") {
						$contentArray[$row][$column] = $result["destination"];
					} elseif ($column == "status") {
						$contentArray[$row][$column] = $result["status"];
					} elseif ($column == "disconnected") {
						$contentArray[$row][$column] = $dc;
					} elseif ($column == "cost") {
						if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
							$contentArray[$row][$column] = $result["cost"];
						}
					} elseif ($column == "durations ->" || preg_match("/^DURATIONS_/", $column) > 0) {
						if (isset($result["events"])) {
							$i = 0;
							foreach ($result["events"] as $eventid => $event) {
								if (isset($options['max_events']) && $options['max_events'] <= $i) {
									break;
								}

								if (isset($options['all_durations'])) {
									$durations_key = "DURATIONS_" . ($i + 1);
								} else {
									$durations_key = $column; // ;legacy
								}

								if (isset($event["billsec"]) && ($event["billsec"] > 0)) {
									$contentArray[$row][$durations_key] = $event["billsec"];
								} else {
									$contentArray[$row][$durations_key] = '';
								}
								$i++;
							}
						}
					} elseif (isset($result["response_data"]) && is_array($result["response_data"]) && isset($result["response_data"][$column])) {
						$contentArray[$row][$column] = $result["response_data"][$column];
					} elseif (isset($result["merge_data"]) && is_array($result["merge_data"]) && isset($result["merge_data"][$column])) {
						$contentArray[$row][$column] = $result["merge_data"][$column];
					} else {
						$contentArray[$row][$column] = '';
					}
				}
			} else {
				if (empty($result["targetkey"])) {
					continue;
				}

				$items = ['targetkey', 'destination', 'status'];

				foreach ($items as $item) {
					$contentArray[$row][$item] = array_key_exists($item, $result) ? $result[$item] : null;
				}

				// handle disconnected
				// $dc == 'YES' if disconnected
				if ($dc !== '') {
					$contentArray[$row]['disconnected'] = $dc;
				}

				if (isset($questions)) {
					foreach ($questions as $value) {
						if (isset($result["response_data"]) && (isset($result["response_data"][$value]))) {
							$contentArray[$row][$value] = $result['response_data'][$value];
						} else {
							$contentArray[$row][$value] = '';
						}
					}
				};

				if (isset($mergefields) && is_array($mergefields)) {
					foreach ($mergefields as $field => $value) {
						if ((isset($result["merge_data"][$value["element"]])) && ($result["merge_data"][$value["element"]] != null)) {
							$contentArray[$row][$value['element']] = $result["merge_data"][$value["element"]];
						} else {
							$contentArray[$row][$value['element']] = '';
						}
					}
				}

				if ($rateplanid !== RATE_PLAN_ID_COST_PRICE) {
					$contentArray[$row]['COST'] = $result["cost"];
				}

				$duration_start = [];
				if (isset($result["events"])) {
					$i = 0;
					foreach ($result["events"] as $eventid => $event) {
						if (isset($options['max_events']) && $options['max_events'] <= $i) {
							break;
						}

						if (isset($options['all_durations'])) {
							$durations_key = "DURATIONS_" . ($i + 1);
						} else {
							$durations_key = "DURATIONS ->"; // legacy
						}

						if (isset($event["billsec"]) && ($event["billsec"] > 0)) {
							$contentArray[$row][$durations_key] = $event['billsec'];
						} else {
							$contentArray[$row][$durations_key] = '';
						}
						$i++;

						if (isset($options['get_last_attempted_time']) && $options['get_last_attempted_time']) {
							foreach ($event as $item) {
								if (isset($item['value']) && $item['value'] === 'GENERATED') {
									$duration_start[] = $item['unixtimestamp'];
								}
							}
						}
					}
				}

				if ($duration_start) {
					$contentArray[$row]['last_attempt_time'] = (new DateTime())
						->setTimestamp(max($duration_start))
						->format('Y-m-d H:i:s');
				} elseif (isset($options['get_last_attempted_time']) && $options['get_last_attempted_time']) {
					$contentArray[$row]['last_attempt_time'] = '';
				}
			}

			if (isset($options['extra_columns'])) {
				foreach ($options['extra_columns'] as $extraColumn) {
					if (is_callable($extraColumn['value'])) {
						$value = $extraColumn['value']($campaignid);
						$contentArray[$row][$extraColumn['header']] = $value;
					} else {
						$contentArray[$row][$extraColumn['header']] = $extraColumn['value'];
					}
				}
			}

			$row++;
		}
	}

	return $contentArray;
}

/**
 * Campaign api rate
 *
 * @codeCoverageIgnore
 * @param DateTime $start
 * @param DateTime $end
 * @param integer  $groupid
 * @return array
 */
function api_campaigns_apirate(DateTime $start, DateTime $end, $groupid) {

	$start = $start->format('Y-m-d H:i:s');
	$finish = $end->format('Y-m-d H:i:s');

	$userids = api_users_list_all_by_groupowner($groupid);

	if (!$userids) {
		return [];
	}

	$userids_sql_string = implode(',', array_fill(0, count($userids), '?'));

	$sms = [];

	$sql = "SELECT `userid` as `user_id`,
					`billing_products_region_id` AS `region_id`,
					SUM(`messageunits`) AS `messageunits`
				FROM `sms_api_mapping` USE INDEX (`userid`)
				WHERE `timestamp` >= ?
					AND `timestamp` <= ?
					AND `userid` IN (
						" . $userids_sql_string . "
					)
				GROUP BY `region_id`, `user_id`";
	$rs = api_db_query_read($sql, array_merge([$start, $finish], $userids));
	$users = [];
	while ($array = $rs->FetchRow()) {
		if (!isset($users[$array['user_id']])) {
			$users[$array['user_id']] = api_users_idtoname($array['user_id']) ?: 0;
		}
		$sms[$array["region_id"]][$users[$array['user_id']]] = $array["messageunits"];
	}

	$sql = "SELECT `userid` as `user_id`,
					`billing_products_region_id` AS `region_id`,
					SUM(IF(LENGTH(`message`)<=160,1,CEILING(LENGTH(`message`)/153))) AS `messageunits`
				FROM `sms_out`
				WHERE `sms_out`.`timestamp` >= ?
					AND `sms_out`.`timestamp` <= ?
					AND `sms_out`.`userid` IN (" . $userids_sql_string . ")
				GROUP BY `region_id`, `user_id`";
	$rs = api_db_query_read($sql, array_merge([$start, $finish], $userids));

	while ($array = $rs->FetchRow()) {
		if (!isset($users[$array['user_id']])) {
			$users[$array['user_id']] = api_users_idtoname($array['user_id']) ?: 0;
		}

		if (!isset($sms[$array["region_id"]][$users[$array['user_id']]])) {
			$sms[$array["region_id"]][$users[$array['user_id']]] = 0;
		}
		$sms[$array["region_id"]][$users[$array['user_id']]] += $array["messageunits"];
	}

	return $sms;
}

/**
 * Campaign wash rate
 *
 * @codeCoverageIgnore
 * @param DateTime $start
 * @param DateTime $end
 * @param integer  $groupid
 * @return array
 */
function api_campaigns_washrate(DateTime $start, DateTime $end, $groupid) {

	$start = $start->format('Y-m-d H:i:s');
	$finish = $end->format('Y-m-d H:i:s');
	$wash = [];

	$sql = "SELECT `userid` as `user_id`,
					`billing_products_region_id` AS region_id,
					`billing_products_destination_type_id` AS destination_type,
					COUNT(*) AS `count`
				FROM `wash_out` INNER JOIN `key_store` ON (`key_store`.`id` = `wash_out`.`userid`)
				WHERE `wash_out`.`timestamp` >= ?
					AND `wash_out`.`timestamp` <= ?
					AND `wash_out`.`status` NOT IN ('INDETERMINATE', 'QUEUED')
					AND `key_store`.`type` = 'USERS'
					AND `key_store`.`item` = 'groupowner'
					AND `key_store`.`value` = ?
					AND `wash_out`.`userid` != ?
				GROUP BY `region_id`, `destination_type`, `user_id`";
	// user id 49 are campaign wash @see api_queue_process_wash()
	$rs = api_db_query_read($sql, [$start, $finish, $groupid, 49]);

	$users = [];
	while ($array = $rs->FetchRow()) {
		if (!isset($users[$array['user_id']])) {
			$users[$array['user_id']] = api_users_idtoname($array['user_id']) ?: 0;
		}
		$wash[$array["region_id"]][$array["destination_type"]][$users[$array['user_id']]] = $array["count"];
	}

	return $wash;
}

/**
 *
 * Search for campaigns that have last sent data $searchDirection (<=, etc) the given date
 *
 * @param DateTime      $dateTime
 * @param OperatorsEnum $searchDirection
 * @param integer       $groupid
 * @return array
 * @throws InvalidArgumentException Search direction.
 */
function api_campaigns_get_campaigns_sent_cutoff_by_period(DateTime $dateTime, OperatorsEnum $searchDirection, $groupid = null) {
	$start = $dateTime->getTimestamp();

	if (!is_null($groupid)) {
		$sql = "SELECT k1.`id` FROM key_store k1 join key_store k2  ON
			(k1.type=k2.type AND k1.id=k2.id AND k1.item='groupowner' AND k2.item= ?) 
			WHERE k1.type='CAMPAIGNS' AND k1.`value` = ? AND k2.`value` {$searchDirection->getValue()} ?";
		$params = [CAMPAIGN_SETTING_LAST_SEND, $groupid, $start];
	} else {
		$sql = "SELECT `id` FROM `key_store` WHERE `type` = 'CAMPAIGNS' AND `item`= ? AND `value` {$searchDirection->getValue()} ?";
		$params = [CAMPAIGN_SETTING_LAST_SEND, $start];
	}

	$rs = api_db_query_read($sql, $params);

	if (!$rs || !$rs->RecordCount()) {
		return [];
	}

	return array_map(
		function(array $campaign) {
			return $campaign['id'];
		},
		$rs->GetArray()
	);
}

/**
 * Fetches all campaign ids for the campaigns that have processed targets after the time parameter.
 *
 * @param DateTime $dateTime
 * @param integer  $groupid
 * @return array
 */
function api_campaigns_get_campaigns_sent_after_period(DateTime $dateTime, $groupid = null) {
	return api_campaigns_get_campaigns_sent_cutoff_by_period($dateTime, OperatorsEnum::GTE(), $groupid);
}

/**
 * Fetches all campaign ids for the campaigns that have processed targets before the time parameter.
 *
 * @param DateTime $dateTime
 * @param integer  $groupid
 * @return array
 */
function api_campaigns_get_campaigns_sent_before_period(DateTime $dateTime, $groupid = null) {
	return api_campaigns_get_campaigns_sent_cutoff_by_period($dateTime, OperatorsEnum::LTE(), $groupid);
}

/**
 * Fetches all campaign ids for the campaigns that have processed targets during the time parameters.
 *
 * @param DateTime $startDateTime
 * @param DateTime $endDateTime
 * @param integer  $groupid
 * @return array
 */
function api_campaigns_get_campaigns_lastsend_during_period(DateTime $startDateTime, DateTime $endDateTime, $groupid = null) {
	$start = $startDateTime->getTimestamp();
	$end = $endDateTime->getTimestamp();

	if (!is_null($groupid)) {
		$sql = "SELECT k1.`id` FROM key_store k1 join key_store k2  ON
			(k1.type=k2.type AND k1.id=k2.id AND k1.item='groupowner' AND k2.item= ?) 
			WHERE k1.type='CAMPAIGNS' AND k1.`value` = ? 
			  AND CAST(k2.`value` as UNSIGNED) >= ? 
			  AND CAST(k2.`value` as UNSIGNED)  <= ?";
		$params = [CAMPAIGN_SETTING_LAST_SEND, $groupid, $start, $end];
	} else {
		$sql = "SELECT `id` FROM `key_store` WHERE `type` = 'CAMPAIGNS' 
                               AND `item`= ? 
                               AND CAST(`value` as UNSIGNED) >= ? 
                               AND CAST(`value` as UNSIGNED) <= ?";
		$params = [CAMPAIGN_SETTING_LAST_SEND, $start, $end];
	}

	$rs = api_db_query_read($sql, $params);

	if (!$rs || !$rs->RecordCount()) {
		return [];
	}

	return array_map(
		function(array $campaign) {
			return $campaign['id'];
		},
		$rs->GetArray()
	);
}

/**
 * Campaign month rate
 *
 * @codeCoverageIgnore
 * @param DateTime $start
 * @param DateTime $end
 * @param integer  $groupid
 * @return array|false
 */
function api_campaigns_rate(DateTime $start, DateTime $end, $groupid) {
	$campaigns = api_campaigns_get_campaigns_sent_after_period($start, $groupid);

	if (!$campaigns) {
		return [];
	}

	$start = $start->format('Y-m-d H:i:s');
	$finish = $end->format('Y-m-d H:i:s');
	$array = [];

	foreach ($campaigns as $campaignid) {
		$settings = api_campaigns_setting_get_multi_byitem(
			$campaignid,
			['type', 'name']
		);
		$settings['id'] = $campaignid;

		switch ($settings["type"]) {
			case CAMPAIGN_TYPE_VOICE:
				$results = api_data_responses_phone_report($campaignid, $start, $finish, true);
				break;
			case CAMPAIGN_TYPE_SMS:
				$results = api_data_responses_sms_report($campaignid, $start, $finish, true);
				break;
			case CAMPAIGN_TYPE_EMAIL:
				$results = api_data_responses_email_report($campaignid, $start, $finish, true);
				break;
			case CAMPAIGN_TYPE_WASH:
				$results = api_data_responses_wash_report($campaignid, $start, $finish, true);
				break;
		}

		if (!$results) {
			continue;
		}

		$billinginfo = [];
		foreach ($results as $result) {
			if (isset($result['billinginfo'])) {
				$billinginfo[] = $result['billinginfo'];
			}
		}

		$array[$campaignid] = ['billinginfo' => $billinginfo, 'type' => $settings['type'], 'name' => $settings['name']];
	}

	return $array;
}

/**
 * Campaign names suggester
 *
 * @param string $name
 * @return string|false
 */
function api_campaigns_namesuggester($name) {

	if (empty($name)) {
		return false;
	}

	if (preg_match("/^(.+)\-(\d+)(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|Sept|October|Oct|November|Nov|December|Dec)([0-9]{2,4})\-([0-9]+)$/i", $name, $matches)) {
		// Matches "IgniteTravel-SMS-DocRemind-13February14-1"

		$date = "F";

		if (strlen($matches[4]) == 2) {
			$year = "y";
		} else {
			$year = "Y";
		}

		if (($matches[2] . $matches[3] . $matches[4]) == date("j" . $date . $year)) {
			$id = $matches[5] + 1;
		} else {
			$id = 1;
		}

		do {
			$name = $matches[1] . "-" . date("j" . $date . $year) . "-" . $id;

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}
		} while ($campaignid);

		return $name;
	} elseif (preg_match("/^(.+)\-(\d+)(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|Sept|October|Oct|November|Nov|December|Dec)([0-9]{2,4})\-(.+)\-([0-9]+)$/i", $name, $matches)) {
		// Matches "NCML-13February14-SMS-NCMLSCOM-1"

		$date = "F";

		if (strlen($matches[4]) == 2) {
			$year = "y";
		} else {
			$year = "Y";
		}

		if (($matches[2] . $matches[3] . $matches[4]) == date("j" . $date . $year)) {
			$id = $matches[6] + 1;
		} else {
			$id = 1;
		}

		do {
			$name = $matches[1] . "-" . date("j" . $date . $year) . "-" . $matches[5] . "-" . $id;

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}
		} while ($campaignid);

		return $name;
	} elseif (preg_match("/^(.+)\-(\d+)(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|Sept|October|Oct|November|Nov|December|Dec)([0-9]{2,4})\-(.+)$/i", $name, $matches)) {
		// Matches "NewsLtd-13February14-Franklin"

		$date = "F";

		if (strlen($matches[4]) == 2) {
			$year = "y";
		} else {
			$year = "Y";
		}

		$id = 1;

		$i = 0;

		do {
			if ($i == 0) {
				$name = $matches[1] . "-" . date("j" . $date . $year) . "-" . $matches[5];
			} else {
				$name = $matches[1] . "-" . date("j" . $date . $year) . "-" . $matches[5] . "-" . $id;
			}

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}

			$i++;
		} while ($campaignid);

		return $name;
	} elseif (preg_match("/^(.+)\-(\d+)(January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|Sept|October|Oct|November|Nov|December|Dec)([0-9]{2,4})$/i", $name, $matches)) {
		// Matches "LoanRanger-Overdue-14Feb14"

		$date = "F";

		if (strlen($matches[4]) == 2) {
			$year = "y";
		} else {
			$year = "Y";
		}

		$id = 1;

		$i = 0;

		do {
			if ($i == 0) {
				$name = $matches[1] . "-" . date("j" . $date . $year);
			} else {
				$name = $matches[1] . "-" . date("j" . $date . $year) . "-" . $id;
			}

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}

			$i++;
		} while ($campaignid);

		return $name;
	} elseif (preg_match("/^(.+)\-(20[12][0-9])([0-1][0-9])([0-3][0-9])$/i", $name, $matches)) {
		// Matches "ToyotaFS-Hardship-20160705"

		$id = 1;

		$i = 0;

		do {
			if ($i == 0) {
				$name = $matches[1] . "-" . date("Ymd");
			} else {
				$name = $matches[1] . "-" . date("Ymd") . "-" . $id;
			}

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}

			$i++;
		} while ($campaignid);

		return $name;
	} elseif (preg_match("/^(.+)\-([0-3][0-9])([0-1][0-9])(20[12][0-9])$/i", $name, $matches)) {
		// Matches "ToyotaFS-Hardship-05072016"

		$id = 1;

		$i = 0;

		do {
			if ($i == 0) {
				$name = $matches[1] . "-" . date("dmY");
			} else {
				$name = $matches[1] . "-" . date("dmY") . "-" . $id;
			}

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}

			$i++;
		} while ($campaignid);

		return $name;
	} elseif (preg_match("/^(.+)\-([0-9])$/i", $name, $matches)) {
		// Matches "Some-UnsupportedFormat-WithANumber-OnTheEnd-6"

		$id = $matches[2];

		$i = 0;

		do {
			$name = $matches[1] . "-" . $id;

			$campaignid = api_campaigns_checknameexists($name);

			if ($campaignid) {
				$id++;
			}

			$i++;
		} while ($campaignid);

		return $name;
	} else {
		$id = 1;
		$i = 0;

		do {
			if ($i == 0) {
				$newname = $name;
			} else {
				$newname = $name . "-" . $id;
			}

			$campaignid = api_campaigns_checknameexists($newname);

			if ($campaignid) {
				$id++;
			}

			$i++;
		} while ($campaignid);

		return $newname;
	}
}

/**
 * Campaign average duration
 *
 * @param integer $campaignid
 * @param string  $action
 * @param mixed   $value
 * @return float|false
 */
function api_campaigns_averageduration($campaignid, $action, $value) {

	if (empty($campaignid) || !is_numeric($campaignid) || (api_campaigns_setting_getsingle($campaignid, "type") != "phone")) {
		return api_error_raise("Sorry, that is not a valid campaign");
	}

	if (empty($action)) {
		return api_error_raise("Sorry, that is not a valid action to search for");
	}

	if (empty($value)) {
		return api_error_raise("Sorry, that is not a valid value to search for");
	}

	$sql = "SELECT ROUND(AVG(TIMESTAMPDIFF(SECOND,`answer`.`answer`, `hangup`.`hangup`)), 1) as `average` FROM (SELECT `eventid`, `timestamp` as `answer` FROM `call_results` WHERE `campaignid` = ? AND `value` = ? AND `eventid` IN (SELECT `eventid` FROM `response_data` WHERE `campaignid` = ? AND `action` = ? AND `value` = ?)) `answer` JOIN (SELECT `eventid`, `timestamp` as `hangup` FROM `call_results` WHERE `campaignid` = ? AND `value` = ? AND `eventid` IN (SELECT `eventid` FROM `response_data` WHERE `campaignid` = ? AND `action` = ? AND `value` = ?)) `hangup` ON `answer`.`eventid` = `hangup`.`eventid`";
	$rs = api_db_query_read($sql, array($campaignid, "ANSWER", $campaignid, $action, $value, $campaignid, "HANGUP", $campaignid, $action, $value));

	if ($rs && ($rs->RecordCount() > 0) && $rs->Fields("average")) {
		return (float)$rs->Fields("average");
	} else {
		return api_error_raise("Sorry, there are no calls of that type");
	}
}

/**
 * Campaign health check
 * Return false is there is some issues
 *
 * @param integer $campaign_id
 * @return false
 */
function api_campaigns_healthcheck($campaign_id) {
	if (!api_campaigns_checkidexists($campaign_id)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	$time_alert = strtotime('2 minutes ago');

	$is_active = api_campaigns_setting_getsingle($campaign_id, 'status') == 'ACTIVE';
	$heartbeat = api_campaigns_setting_getsingle($campaign_id, 'heartbeattimestamp');
	$created = api_campaigns_setting_getsingle($campaign_id, 'created');

	// Must be active AND (heartbeat is older than time allowed OR No heartbeat and created time is older than time allowed)
	if ($is_active && (($heartbeat && $heartbeat < $time_alert) || (!$heartbeat && $created < $time_alert))) {
		return false;
	}

	return true;
}

/**
 * Returns a DateTimeZone object for the specified campaignid and reverts to a default timezone if none is specified
 * Return false is there is some issues
 *
 * @param integer $campaignid
 * @return DateTimeZone
 */
function api_campaigns_gettimezone($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	$timezone = api_campaigns_setting_getsingle($campaignid, "timezone");

	if (empty($timezone)) {
		$timezone = DEFAULT_TIMEZONE;
	}

	try {
		return new DateTimeZone($timezone);
	} catch (Exception $e) {
		api_misc_audit("INVALID_TIMEZONE", "timezone=" . $timezone . "; campaignid=" . $campaignid);

		return new DateTimeZone(DEFAULT_TIMEZONE);
	}
}

/**
 * Get campaign classification.
 * If not set, return the default classification per type
 *
 * @param integer $campaignid
 * @return false|string
 */
function api_campaigns_getclassification($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	$key = CAMPAIGN_SETTING_CLASSIFICATION;
	$settings = api_campaigns_setting_get_multi_byitem($campaignid, ["type", $key]);

	if (isset($settings[$key]) && $settings[$key]) {
		return $settings[$key];
	}

	if (in_array($settings['type'], ['phone', 'wash'])) {
		$classificationEnum = CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING();
	} else {
		$classificationEnum = CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT();
	}
	return $classificationEnum->getValue();
}

/**
 * @return boolean
 */
function api_campaigns_has_boost_spooler_permission() {
	return !empty($_SESSION['userid']) && api_users_is_technical_admin($_SESSION['userid']);
}

/**
 * @param integer $campaignid
 * @return boolean
 */
function api_campaigns_update_lastsend($campaignid) {
	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}
	return api_campaigns_setting_set($campaignid, CAMPAIGN_SETTING_LAST_SEND, microtime(true));
}

/**
 * @param integer $campaignid
 * @return array
 */
function api_campaigns_get_all_targets($campaignid) {
	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	$sql = 'SELECT * from `targets` WHERE `campaignid` = ?';
	$rs = api_db_query_read($sql, [$campaignid]);
	if (!$rs || !$rs->RowCount()) {
		return [];
	}

	return $rs->GetArray();
}

/**
 * @param integer $campaignid
 * @return boolean
 */
function api_campaigns_disable_download($campaignid) {
	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	return api_campaigns_setting_set($campaignid, CAMPAIGN_SETTING_DISABLE_DOWNLOAD, 1);
}

/**
 * @param integer $campaignid
 * @param integer $userid
 * @return boolean
 */
function api_campaigns_is_download_disabled($campaignid, $userid = null) {
	if (!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("Sorry, that campaign id doesn't exist");
	}

	if (!is_null($userid) && api_users_is_admin_user($userid)) {
		return false;
	}

	return (api_campaigns_setting_getsingle($campaignid, CAMPAIGN_SETTING_DISABLE_DOWNLOAD) == 1);
}

/**
 * @param integer $campaignid
 * @param integer $userid
 * @param string  $type
 * @return boolean
 */
function api_campaigns_downloadreport($campaignid, $userid, $type) {
	if (!api_campaigns_is_download_disabled($campaignid, $userid)) {
		if ($type == "phone") {
			$data = api_campaigns_report_summary_phone($campaignid);
		} elseif ($type == "sms") {
			$data = api_campaigns_report_summary_sms($campaignid);
		} elseif ($type == "email") {
			$data = api_campaigns_report_summary_email($campaignid);
		} elseif ($type == "wash") {
			$data = api_campaigns_report_summary_wash($campaignid);
		}

		ActivityLogger::getInstance()->addLog(
			KEYSTORE_TYPE_CAMPAIGNS,
			ActivityLoggerActions::ACTION_DOWNLOAD_CAMPAIGN_REPORT,
			'Downloaded report for campaign ' . $campaignid,
			$campaignid
		);

		api_misc_audit("CAMPAIGNDOWNLOADREPORT", $campaignid, $userid);

		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=\"" . $data["filename"] . "\"");

		print $data["content"];
		api_misc_profiling_save();

		exit;
	} else {
		ActivityLogger::getInstance()->addLog(
			KEYSTORE_TYPE_CAMPAIGNS,
			ActivityLoggerActions::ACTION_FAILED_DOWNLOAD_CAMPAIGN_REPORT,
			'Unsuccessful report download attempt for campaign ' . $campaignid,
			$campaignid
		);
		return false;
	}
}

/**
 * @param integer $campaignid
 * @param string  $targetkey
 * @return boolean
 */
function api_campaigns_delete_target($campaignid, $targetkey) {
	$target = api_targets_get_target_by_campaign_target_key($campaignid, $targetkey);
	if (!$target) {
		return null;
	}

	$targetid = $target['targetid'];

	api_db_starttrans();
	if (!api_data_responses_delete_by_targetid($targetid)) {
		api_db_failtrans();
		api_db_endtrans();
		return api_error_raise(
			'Failed to remove target [' . $targetid . '] because of failure while removing response data'
		);
	}

	if (!api_data_callresult_delete_by_targetid($targetid)) {
		api_db_failtrans();
		api_db_endtrans();
		return api_error_raise(
			'Failed to remove target [' . $targetid . '] because of failure while removing call results'
		);
	}

	if (!api_data_merge_delete_by_targetkey($campaignid, $targetkey)) {
		api_db_failtrans();
		api_db_endtrans();
		return api_error_raise(
			'Failed to remove target [' . $targetid . '] because of failure while removing merge data'
		);
	}

	if (!api_targets_delete_single_bytargetid($targetid)) {
		api_db_failtrans();
		api_db_endtrans();
		return api_error_raise(
			'Failed to remove target [' . $targetid . ']'
		);
	}

	api_db_endtrans();

	ActivityLogger::getInstance()->addLog(
		KEYSTORE_TYPE_CAMPAIGNS,
		ActivityLoggerActions::ACTION_CAMPAIGN_TARGET_REMOVE,
		'Removed target ' . $targetid,
		$campaignid
	);

	return true;
}
