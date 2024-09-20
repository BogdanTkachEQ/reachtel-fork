<?php
/**
 * Conferences Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

/**
 * Add a conferences
 *
 * @param array $options
 * @return false|array
 */
function api_conferences_add(array $options = []) {

	if (!isset($options["userid"]) || !is_numeric($options["userid"])) {
		return api_error_raise("Sorry, that is not a valid user ID");
	}

	$servers = array_keys(api_voice_servers_listall_active());

	if (empty($servers)) {
		return api_error_raise("Sorry, there are no servers to create a conference on");
	}

	if (isset($options["serverpreference"]) && in_array($options["serverpreference"], $servers)) {
		$serverid = $options["serverpreference"];
	} else {
		shuffle($servers);

		$serverid = array_pop($servers);
	}

	if (empty($serverid)) {
		return api_error_raise("Sorry, there are no servers to create a conference on");
	}

	if (empty($options["expiry"])) {
		$options["expiry"] = 10;
	} elseif (!is_numeric($options["expiry"])) {
		return api_error_raise("Sorry, that is not a valid expiry");
	}

	if (empty($options["accesscodelength"])) {
		$options["accesscodelength"] = 6;
	} elseif (!empty($options["accesscodelength"]) && !is_numeric($options["accesscodelength"])) {
		return api_error_raise("Sorry, that is not a valid access code length");
	} elseif ($options["accesscodelength"] < 3 || $options["accesscodelength"] > 10) {
		return api_error_raise("Sorry, that is not a valid access code length");
	}

	do { // Find a access code that hasn't been used in the last 7 days

		$accesscode = str_pad(mt_rand(0, pow(10, $options["accesscodelength"]) - 1), $options["accesscodelength"], "0");

		$sql = "SELECT * FROM `conferences` WHERE `accesscode` = ? AND ((`timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY)) OR (`accesscodeexpiry` IS NULL))";
		$rs = api_db_query_read($sql, array($accesscode, 7));
	} while ($rs->RecordCount() != 0);

	$sql = "INSERT INTO `conferences` (`userid`, `serverid`, `accesscode`, `accesscodeexpiry`) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))";
	$rs = api_db_query_write($sql, array($options["userid"], $serverid, $accesscode, $options["expiry"]));

	return array("conferenceid" => api_db_lastid(), "accesscode" => $accesscode);
}

/**
 * Check if a conference exists
 *
 * @param integer $conferenceid
 * @param array   $options
 * @return boolean
 */
function api_conferences_exists($conferenceid, array $options = []) {

	if (!is_numeric($conferenceid)) {
		return api_error_raise("Sorry, that is not a valid conference ID");
	}

	if (isset($options["connectedonly"]) && $options["connectedonly"]) { // This option is for conference guests (conference has at least one connected user)

		$sql = "SELECT * FROM `conferences`, `conferences_status` WHERE `conferences`.`id` = `conferences_status`.`conferenceid` AND `conferences`.`id` = ? AND `conferences_status`.`status` = ?";
		$parameters = array($conferenceid, "CONNECTED");
	} elseif (isset($options["awaitinghost"]) && $options["awaitinghost"] && isset($options["accesscode"])) { // This option is for conference hosts (access code valid, not redeemed or expired)

		$sql = "SELECT * FROM `conferences` WHERE `conferences`.`id` = ? AND `conferences`.`accesscoderedeemed` = ? AND `conferences`.`accesscodeexpiry` >= NOW() AND `conferences`.`accesscode` = ?";
		$parameters = array($conferenceid, "0", $options["accesscode"]);
	} else {
		return api_error_raise("Sorry, invalid conferences options");
	}

	if (isset($options["userid"]) && is_numeric($options["userid"])) {
		$sql .= " AND `conferences`.`userid` = ?";
		$parameters[] = $options["userid"];
	}

	$rs = api_db_query_read($sql, $parameters);

	if ($rs && $rs->RecordCount() > 0) {
		return true;
	} else {
		return false;
	}
}

/**
 * Get a conference
 *
 * @param integer $conferenceid
 * @return false|array
 */
function api_conferences_get($conferenceid) {

	if (!is_numeric($conferenceid)) {
		return api_error_raise("Sorry, that is not a valid conference ID");
	}

	$sql = "SELECT * FROM `conferences` WHERE `id` = ?";
	$rs = api_db_query_read($sql, array($conferenceid));

	if ($rs && $rs->RecordCount()) {
		return $rs->FetchRow();
	} else {
		return api_error_raise("Sorry, that is not a valid conference ID");
	}
}

/**
 * Get conference's participants
 *
 * @param integer $conferenceid
 * @param array   $options
 * @return false|array
 */
function api_conferences_participants_get($conferenceid, array $options = []) {

	if (!is_numeric($conferenceid)) {
		return api_error_raise("Sorry, that is not a valid conference ID");
	}

	$sql = "SELECT * FROM `conferences_status` WHERE `conferenceid` = ?";

	$parameters = array($conferenceid);

	if (isset($options["connectedonly"]) && $options["connectedonly"]) {
		$sql .= " AND `status` = ?";
		$parameters[] = "CONNECTED";
	}

	if (isset($options["participantid"]) && is_numeric($options["participantid"])) {
		$sql .= " AND `participantid` = ?";
		$parameters[] = $options["participantid"];
	}

	$rs = api_db_query_read($sql, $parameters);

	$participants = array();

	if ($rs) {
		while ($array = $rs->FetchRow()) {
			$participants[$array["participantid"]] = array("timestamp" => $array["timestamp"], "status" => $array["status"], "channel" => $array["channel"], "callerid" => $array["callerid"]);
		}
	}

	return $participants;
}

/**
 * Kick a conference with participants
 *
 * @param integer $conferenceid
 * @param mixed   $participants
 * @return boolean
 */
function api_conferences_participants_kick($conferenceid, $participants = []) {

	$conference = api_conferences_get($conferenceid);

	if (empty($conference)) {
		return api_error_raise("Sorry, that is not a valid conference ID");
	}

	$kick = array();

	if (empty($participants)) {
		$kick = api_conferences_participants_get($conferenceid, array("connectedonly" => true)); // Kick everyone
	} elseif (is_array($participants)) {
		foreach ($participants as $participantid) {
			$kick = $kick + api_conferences_participants_get($conferenceid, array("participantid" => $participantid));
		}
	} elseif (!is_array($participants)) {
		$kick = api_conferences_participants_get($conferenceid, array("connectedonly" => true, "participantid" => $participants)); // Kick a specific user
	}
	if (!is_array($kick) || empty($kick)) {
		return api_error_raise("Sorry, that is not a valid list of participants to kick");
	}

	$kicked = false;
	foreach ($kick as $participantid => $details) {
		if ($details["status"] == "CONNECTED") {
			$event_queue_id = api_queue_add(
				"pbxcomms",
				array(
				"action" => "confbridgekick",
				"conference" => "cb-" . $conference["id"],
				"serverid" => $conference["serverid"],
				"channel" => $details["channel"])
			);
			if ($event_queue_id) {
				$kicked = true;
			}
		}
	}

	return $kicked;
}

/**
 * Conference rate
 *
 * @codeCoverageIgnore
 * @param string $month
 * @param mixed  $groupid
 * @param mixed  $returnarray
 * @return array|false
 */
function api_conferences_rate($month, $groupid = null, $returnarray = false) {

	if (!preg_match("/^201[0-9]\-[0-9]?[0-9]{1}$/", $month)) {
		return api_error_raise("Sorry, that is not a valid start date");
	}

	$start = $month . "-01 00:00:00";
	$finish = date("Y-m-t", strtotime($start)) . " 23:59:59";

	if (is_numeric($groupid)) {
		$sql = "SELECT `conferences`.`userid`, `conferences`.`accesscode`, `conferences_status`.`timestamp`, `conferences_status`.`callerid`, TIMESTAMPDIFF(SECOND, `conferences_status`.`timestamp`, `conferences_status`.`left`) as `duration` FROM `conferences`, `conferences_status` WHERE `conferences`.`id` = `conferences_status`.`conferenceid` AND `conferences_status`.`left` IS NOT NULL AND `conferences_status`.`timestamp` >= ? AND `conferences_status`.`timestamp` <= ? AND `conferences`.`userid` IN (SELECT  `id` FROM  `key_store` WHERE  `type` =  ? AND  `item` =  ? AND  `value` =?)";
		$rs = api_db_query_read($sql, array($start, $finish, "USERS", "groupowner", $groupid));
	} else {
		$sql = "SELECT `conferences`.`userid`, `conferences`.`accesscode`, `conferences_status`.`timestamp`, `conferences_status`.`callerid`, TIMESTAMPDIFF(SECOND, `conferences_status`.`timestamp`, `conferences_status`.`left`) as `duration` FROM `conferences`, `conferences_status` WHERE `conferences`.`id` = `conferences_status`.`conferenceid` AND `conferences_status`.`left` IS NOT NULL AND `conferences_status`.`timestamp` >= ? AND `conferences_status`.`timestamp` <= ?";
		$rs = api_db_query_read($sql, array($start, $finish));
	}

	if (!$returnarray) {
		header("Content-type: application/text-csv");
		header("Content-disposition: attachment; filename=\"ConferenceRateReport-" . date("Ymd-His") . ".csv\"");

		print "username,accesscode,callerid,timestamp,duration\n";
	}

	$conferences = array();

	while ($row = $rs->FetchRow()) {
		if (!is_numeric($groupid) || (api_users_setting_getsingle($row["userid"], "groupowner") == $groupid)) {
			if (empty($username[$row["userid"]])) {
				$username[$row["userid"]] = api_users_setting_getsingle($row["userid"], "username");
			}

			if ($returnarray) {
				if (empty($conferences[$row["accesscode"]])) {
					$conferences[$row["accesscode"]] = array("description" => "Conference - User: " . $username[$row["userid"]] . "; Code: " . $row["accesscode"], "messageunits" => 1);
				} else {
					$conferences[$row["accesscode"]]["messageunits"]++;
				}
			} else {
				print $username[$row["userid"]] . "," . $row["accesscode"] . "," . $row["callerid"] . "," . $row["timestamp"] . "," . $row["duration"] . "\n";
			}
		}
	}

	if ($returnarray) {
		return $conferences;
	} else {
		exit;
	}
}
