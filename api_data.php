<?php
/**
 * Data Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

use Services\Utils\Billing\DestinationType;
use Services\Utils\Billing\Region;

require_once("api_db.php");

/**
 * Get campaign target status
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_target_status($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$targets = array();
	$targets["READY"] = 0;
	$targets["INPROGRESS"] = 0;
	$targets["REATTEMPT"] = 0;
	$targets["ABANDONED"] = 0;
	$targets["COMPLETE"] = 0;

	$sql = "SELECT `status`, COUNT(`status`) as `count` FROM `targets` WHERE `campaignid` = ? GROUP BY `status`";
	$rs = api_db_query_read($sql, array($campaignid));

	if ($rs->RecordCount() > 0) {
		$result = $rs->GetAssoc();

		foreach ($result as $status => $count) {
			$targets[$status] = $count;
		}
	}

	$targets["TOTAL"] = array_sum($targets);

	return $targets;
}

/**
 * Get email campaign target status
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_target_status_email($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "SELECT `action`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? GROUP BY `action`";
	$rs = api_db_query_read($sql, array($campaignid));

	$targets = $rs->GetAssoc();

	$sql = "SELECT `value`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? AND `action` = ? GROUP BY `value`";
	$rs = api_db_query_read($sql, array($campaignid, "REMOVED"));

	while ($array = $rs->FetchRow()) {
		$targets[$array["value"]] = $array["count"];
	}

	if (!isset($targets["HARDBOUNCE"])) {
		$targets["HARDBOUNCE"] = 0;
	}
	if (!isset($targets["OPEN"])) {
		$targets["OPEN"] = 0;
	}
	if (!isset($targets["SOFTBOUNCE"])) {
		$targets["SOFTBOUNCE"] = 0;
	}
	if (!isset($targets["BOUNCED"])) {
		$targets["BOUNCED"] = 0;
	}
	if (!isset($targets["DUPLICATE"])) {
		$targets["DUPLICATE"] = 0;
	}
	if (!isset($targets["UNSUBSCRIBED"])) {
		$targets["UNSUBSCRIBED"] = 0;
	}
	if (!isset($targets["UNSUBSCRIBE"])) {
		$targets["UNSUBSCRIBE"] = 0;
	}
	if (!isset($targets["DNC"])) {
		$targets["DNC"] = 0;
	}
	if (!isset($targets["CLICK"])) {
		$targets["CLICK"] = 0;
	}
	if (!isset($targets["TRACK"])) {
		$targets["TRACK"] = 0;
	}
	if (!isset($targets["WEBVIEW"])) {
		$targets["WEBVIEW"] = 0;
	}
	if (!isset($targets["REMOVED"])) {
		$targets["REMOVED"] = 0;
	}
	if (!isset($targets["SENT"])) {
		$targets["SENT"] = 0;
	}

	return $targets;
}

/**
 * Get sms campaign target status
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_target_status_sms($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "SELECT `action`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? GROUP BY `action`";
	$rs = api_db_query_read($sql, array($campaignid));

	$targets = $rs->GetAssoc();

	$sql = "SELECT `value`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? AND `action` = ? GROUP BY `value`";
	$rs = api_db_query_read($sql, array($campaignid, "REMOVED"));

	while ($array = $rs->FetchRow()) {
		$targets[$array["value"]] = $array["count"];
	}

	if (!isset($targets["EXPIRED"])) {
		$targets["EXPIRED"] = 0;
	}
	if (!isset($targets["UNDELIVERED"])) {
		$targets["UNDELIVERED"] = 0;
	}
	if (!isset($targets["DELIVERED"])) {
		$targets["DELIVERED"] = 0;
	}
	if (!isset($targets["DNC"])) {
		$targets["DNC"] = 0;
	}
	if (!isset($targets["DUPLICATE"])) {
		$targets["DUPLICATE"] = 0;
	}
	if (!isset($targets["UNKNOWN"])) {
		$targets["UNKNOWN"] = 0;
	}
	if (!isset($targets["REMOVED"])) {
		$targets["REMOVED"] = 0;
	}
	if (!isset($targets["SENT"])) {
		$targets["SENT"] = 0;
	}

	return $targets;
}

/**
 * Get phone campaign target status in json format
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @param boolean $charts
 * @param boolean $archive
 * @return false|array
 */
function api_data_target_status_phone_json($campaignid, $charts = true, $archive = false) {

	if (!is_numeric($campaignid)) {
		return false;
	}
	$archive_suffix = (true === $archive ? '_archive' : '');

	$a = array();
	$a["status"] = array("targets" => 0, "ready" => 0, "inprogress" => 0, "reattempt" => 0, "complete" => 0, "abandoned" => 0, "calls" => 0, "answered" => 0, "busy" => 0, "ringout" => 0, "disconnected" => 0, "chanunavail" => 0);

		// Get the total number of targets
	$sql = "SELECT `status`, COUNT(`status`) as `count` FROM `targets{$archive_suffix}` WHERE `campaignid` = ? GROUP BY `status`";
	$rs = api_db_query_read($sql, array($campaignid));

	if ($rs->RecordCount() > 0) {
		$targets = $rs->GetAssoc();

		if (is_array($targets)) {
			foreach ($targets as $status => $count) {
				$a["status"][strtolower($status)] = $count;
			}
		}

		$a["status"]["targets"] = array_sum($a["status"]);
	}

		// Get call data
	$sql = "SELECT `value`, COUNT(`value`) as `count` FROM `call_results{$archive_suffix}` WHERE `campaignid` = ? GROUP BY `value`";
	$rs = api_db_query_read($sql, array($campaignid));

	$calls = $rs->GetAssoc();

	if (isset($calls["GENERATED"])) {
		$a["status"]["calls"] = $calls["GENERATED"];
	}
	if (isset($calls["ANSWER"])) {
		$a["status"]["answered"] = $calls["ANSWER"];
	}
	if (isset($calls["NOANSWER"])) {
		$a["status"]["ringout"] += $calls["NOANSWER"];
	}
	if (isset($calls["CANCEL"])) {
		$a["status"]["ringout"] += $calls["CANCEL"];
	}
	if (isset($calls["DISCONNECTED"])) {
		$a["status"]["disconnected"] += $calls["DISCONNECTED"];
	}
	if (isset($calls["CONGESTION"])) {
		$a["status"]["busy"] += $calls["CONGESTION"];
	}
	if (isset($calls["BUSY"])) {
		$a["status"]["busy"] += $calls["BUSY"];
	}
	if (isset($calls["CHANUNAVAIL"])) {
		$a["status"]["chanunavail"] += $calls["CHANUNAVAIL"];
	}

	if (!$charts) {
		return $a;
	}

	$sql = "SELECT `action`, `value`, COUNT(`targetkey`) as `count` FROM (SELECT DISTINCT  `targetkey` ,  `action` ,  `value` FROM  `response_data{$archive_suffix}` WHERE  `campaignid` = ? AND `action` NOT IN (?, ?, ?, ?, ?)) AS  `counted` GROUP BY  `action` ,  `value`";
	$rs = api_db_query_read($sql, array($campaignid, "DELIVERED", "EXPIRED", "SENT", "UNDELIVERED", "UNKNOWN"));

	if ($rs) {
		while ($array = $rs->FetchRow()) {
			$a["responses"][$array["action"]]["answers"][$array["value"]] = $array["count"];
		}
	}

	$a["response_data"] = array();

	if (isset($a["responses"])) {
		foreach ($a["responses"] as $questions => $array) {
			$a["responses"][$questions]["total"] = 0;
			$a["responses"][$questions]["answercount"] = 0;

			foreach ($array["answers"] as $value => $count) {
				$a["responses"][$questions]["total"] += $count;
				$a["responses"][$questions]["answercount"]++;
			}
			$a["response_data"][] = array("question" => $questions, "answers" => $a["responses"][$questions]["answers"], "answercount" => $a["responses"][$questions]["answercount"], "total" => $a["responses"][$questions]["total"]);
		}
	}

	$chart = array();

	$skip = array("1_TRANSDEST", "2_TRANSDEST", "CALLBACK_TRANSDEST", "1_TRANSCALLTIME", "2_TRANSCALLTIME", "CALLBACK_TRANSCALLTIME");

	$i = 0;

	$a["response_charts"] = array();

	foreach ($a["response_data"] as $key => $question) {
		if (!in_array($question["question"], $skip)) {
			$a["response_charts"][$i] = array("chartnumber" => $i, "title" => $question["question"], "total" => $question["total"], "datatable" => array("cols" => array(array("id" => "", "label" => "Answer", "pattern" => "", "type" => "string"), array("id" => "", "label" => "Number of responses", "pattern" => "", "type" => "number")), "rows" => array(), "p" => null));

			if (count($question["answers"]) <= 20) {
				foreach ($question["answers"] as $q => $ans) {
					$a["response_charts"][$i]["datatable"]["rows"][] = array("c" => array(array("v" => $q), array("v" => (integer)$ans)));
				}
			}

			$i++;
		}
	}

	unset($a["response_data"]);
	unset($a["responses"]);

	return $a;
}

/**
 * Get sms campaign target status in json format
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @param boolean $charts
 * @return false|array
 */
function api_data_target_status_sms_json($campaignid, $charts = true) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$a["status"] = array("targets" => 0, "duplicates" => 0, "sent" => 0, "delivered" => 0, "intheair" => 0, "disconnected" => 0, "unknown" => 0, "removed" => 0, "expired" => 0, "optout" => 0, "responses" => 0, "removed-percent" => 0, "sent-percent" => 0, "delivered-percent" => 0, "intheair-percent" => 0, "disconnected-percent" => 0, "unknown-percent" => 0, "expired-percent" => 0);

	// Get the total number of targets
	$sql = "SELECT COUNT(`targetid`) as `count` FROM `targets` WHERE `campaignid` = ?";
	$rs = api_db_query_read($sql, array($campaignid));

	if ($rs->RecordCount() > 0) {
		$a["status"]["targets"] = $rs->Fields("count");
	}

	// Get response data
	$sql = "SELECT `action`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? GROUP BY `action`";
	$rs = api_db_query_read($sql, array($campaignid));

	$targets = $rs->GetAssoc();

	if (isset($targets["SENT"])) {
		$a["status"]["sent"] = $targets["SENT"];
	}
	if (isset($targets["DELIVERED"])) {
		$a["status"]["delivered"] = $targets["DELIVERED"];
	}
	if (isset($targets["DUPLICATE"])) {
		$a["status"]["duplicates"] = $targets["DUPLICATE"];
	}
	if (isset($targets["REMOVED"])) {
		$a["status"]["removed"] = $targets["REMOVED"];
	}
	if (isset($targets["UNDELIVERED"])) {
		$a["status"]["disconnected"] = $targets["UNDELIVERED"];
	}
	if (isset($targets["UNKNOWN"])) {
		$a["status"]["unknown"] = $targets["UNKNOWN"];
	}
	if (isset($targets["EXPIRED"])) {
		$a["status"]["expired"] = $targets["EXPIRED"];
	}
	if (isset($targets["OPTOUT"])) {
		$a["status"]["optout"] = $targets["OPTOUT"];
	}
	if (isset($targets["RESPONSE"])) {
		$a["status"]["responses"] = $targets["RESPONSE"];
	}
	$a["status"]["intheair"] = $a["status"]["sent"] - ($a["status"]["delivered"] + $a["status"]["disconnected"] + $a["status"]["unknown"] + $a["status"]["expired"]);
	$a["status"]["notyetsent"] = $a["status"]["targets"] - $a["status"]["sent"] - $a["status"]["removed"] - $a["status"]["duplicates"];

	$a["target_chart"] = array("cols" => array(array("id" => "", "label" => "Category", "pattern" => "", "type" => "string"), array("id" => "", "label" => "Count", "pattern" => "", "type" => "number")), "rows" => array(), "p" => null);

	$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Not yet sent"), array("v" => (integer)$a["status"]["notyetsent"])));

	if ($a["status"]["targets"] > 0) {
		$a["status"]["removed-percent"] = sprintf("%01.1f", $a["status"]["removed"] / $a["status"]["targets"] * 100);
		$a["status"]["duplicates-percent"] = sprintf("%01.1f", $a["status"]["duplicates"] / $a["status"]["targets"] * 100);

		if (($a["status"]["targets"] - $a["status"]["removed"] - $a["status"]["duplicates"]) == 0) {
			$a["status"]["notyetsent-percent"] = 0;
			$a["status"]["sent-percent"] = 0;
		} else {
			$a["status"]["notyetsent-percent"] = sprintf("%01.1f", $a["status"]["notyetsent"] / ($a["status"]["targets"] - $a["status"]["removed"] - $a["status"]["duplicates"]) * 100);
			$a["status"]["sent-percent"] = sprintf("%01.1f", $a["status"]["sent"] / ($a["status"]["targets"] - $a["status"]["removed"] - $a["status"]["duplicates"]) * 100);
		}

		$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Duplicates"), array("v" => (integer)$a["status"]["duplicates"])));
		$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Removed"), array("v" => (integer)$a["status"]["removed"])));

		if ($a["status"]["sent"] > 0) {
			$a["status"]["delivered-percent"] = sprintf("%01.1f", $a["status"]["delivered"] / $a["status"]["sent"] * 100);
			$a["status"]["intheair-percent"] = sprintf("%01.1f", $a["status"]["intheair"] / $a["status"]["sent"] * 100);
			$a["status"]["disconnected-percent"] = sprintf("%01.1f", $a["status"]["disconnected"] / $a["status"]["sent"] * 100);
			$a["status"]["unknown-percent"] = sprintf("%01.1f", $a["status"]["unknown"] / $a["status"]["sent"] * 100);
			$a["status"]["expired-percent"] = sprintf("%01.1f", $a["status"]["expired"] / $a["status"]["sent"] * 100);
			$a["status"]["optout-percent"] = sprintf("%01.1f", $a["status"]["optout"] / $a["status"]["sent"] * 100);
			$a["status"]["responses-percent"] = sprintf("%01.1f", $a["status"]["responses"] / $a["status"]["sent"] * 100);

			$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Delivered"), array("v" => (integer)$a["status"]["delivered"])));
			$a["target_chart"]["rows"][] = array("c" => array(array("v" => "In the air"), array("v" => (integer)$a["status"]["intheair"])));
			$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Disconnected"), array("v" => (integer)$a["status"]["disconnected"])));
			$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Undeliverable"), array("v" => (integer)$a["status"]["unknown"])));
			$a["target_chart"]["rows"][] = array("c" => array(array("v" => "Expired"), array("v" => (integer)$a["status"]["expired"])));
		}
	}

	if (!$charts) {
		return $a;
	}

	// Get response data
	$sql = "SELECT `value`, COUNT(*) as `count` FROM `response_data` WHERE `campaignid` = ? AND `action` = ? GROUP BY `value`";
	$rs = api_db_query_read($sql, array($campaignid, "RESPONSE"));

	$a["responses"] = $rs->GetAssoc();

	$a["response_chart"][0] = array("chartnumber" => 0, "title" => "SMS Responses", "total" => $a["status"]["responses"], "datatable" => array("cols" => array(array("id" => "", "label" => "Response", "pattern" => "", "type" => "string"), array("id" => "", "label" => "Number of responses", "pattern" => "", "type" => "number")), "rows" => array(), "p" => null));

	if (is_array($a["responses"]) && (count($a["responses"]) < 20)) {
		foreach ($a["responses"] as $value => $count) {
			$a["response_chart"][0]["datatable"]["rows"][] = array("c" => array(array("v" => (string)$value), array("v" => (integer)$count)));
		}
	}

	unset($a["responses"]);

	return $a;
}

/**
 * Add call result line
 *
 * @param integer $campaignid
 * @param integer $eventid
 * @param integer $targetid
 * @param mixed   $value
 * @return false|integer
 */
function api_data_callresult_add($campaignid, $eventid, $targetid, $value) {

	if (!is_numeric($eventid)) {
		return false;
	}
	if (!is_numeric($targetid)) {
		return false;
	}
	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "INSERT INTO `call_results` (`campaignid`, `eventid`, `targetid`, `timestamp`, `value`) VALUES (?, ?, ?, NOW(), ?)";
	$rs = api_db_query_write($sql, array($campaignid, $eventid, $targetid, $value));

	if ($rs !== false) {
		return api_db_lastid();
	} else {
		return false;
	}
}

/**
 * Delete all call results
 *
 * @param integer $campaignid
 * @return true
 */
function api_data_callresult_delete_all($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = "DELETE FROM `call_results` WHERE `campaignid` = ?";
	$rs = api_db_query_write($sql, array($campaignid));

	return true;
}

/**
 * @param integer $campaignid
 * @param boolean $override_campaignid_check
 * @return boolean
 */
function api_data_callresult_archive($campaignid, $override_campaignid_check = false) {
	if (!$override_campaignid_check && !api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = 'INSERT INTO `call_results_archive` (`resultid`, `eventid`, `campaignid`, `targetid`, `timestamp`, `value`)
			SELECT `resultid`, `eventid`, `campaignid`, `targetid`, `timestamp`, `value` FROM `call_results`
			WHERE `campaignid`=?';
	return api_db_query_write($sql, $campaignid) !== false;
}

/**
 * Get call results data by target id
 *
 * @param integer $targetid
 * @param string  $orderby
 * @return false|array
 */
function api_data_callresult_get_all_bytargetid($targetid, $orderby = null) {
	if (!is_numeric($targetid)) {
		return false;
	}

	$sql = "SELECT `value`, `timestamp` FROM `call_results` WHERE `targetid` = ?";

	if (!is_null($orderby)) {
		$sql .= ' ORDER BY ' . $orderby . ' ASC';
	}

	$rs = api_db_query_write($sql, array($targetid));

	if ($rs) {
		return $rs->GetAssoc();
	} else {
		return array();
	}
}

/**
 * Get all call results by targetid keyed by the eventid
 *
 * @param integer $targetid
 * @return false|array
 */
function api_data_callresult_get_all_bytargetid_witheventid($targetid) {

	if (!is_numeric($targetid)) {
		return false;
	}

	$sql = "SELECT `eventid`, `value`, `timestamp` FROM `call_results` WHERE `targetid` = ?";
	$rs = api_db_query_write($sql, array($targetid));

	$records = [];

	if (!$rs || !$rs->RecordCount()) {
		return $records;
	}

	while ($row = $rs->FetchRow()) {
		$records[$row["eventid"]][$row["value"]] = $row["timestamp"];
	}

	return $records;
}

/**
 * Get all call results data
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_callresult_get_all($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "SELECT `targetid`, `value`, `timestamp` FROM `call_results` WHERE `campaignid` = ?";
	$rs = api_db_query_read($sql, array($campaignid));

	if ($rs && $rs->RecordCount() > 0) {
		return $rs->GetAssoc();
	} else {
		return array();
	}
}

/**
 * Campaign call results summary
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_target_results($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = "SELECT `value`, COUNT(`value`) as `count` FROM `call_results` WHERE `campaignid` = ? GROUP BY `value`";
	$rs = api_db_query_read($sql, array($campaignid));

	$targetResults = $rs->GetAssoc();

	$type = api_campaigns_setting_getsingle($campaignid, "type");

	if (!isset($targetResults["GENERATED"])) {
		$targetResults["GENERATED"] = 0;
	}

	if (in_array($type, array("phone", "wash"))) {
		if (!isset($targetResults["ANSWER"])) {
			$targetResults["ANSWER"] = 0;
		}
		if (!isset($targetResults["BUSY"])) {
			$targetResults["BUSY"] = 0;
		}
		if (!isset($targetResults["CONGESTION"])) {
			$targetResults["CONGESTION"] = 0;
		}
		if (!isset($targetResults["CANCEL"])) {
			$targetResults["CANCEL"] = 0;
		}
		if (!isset($targetResults["NOANSWER"])) {
			$targetResults["NOANSWER"] = 0;
		}
		if (!isset($targetResults["DISCONNECTED"])) {
			$targetResults["DISCONNECTED"] = 0;
		}
	}

	return $targetResults;
}

/**
 * Add response data line
 *
 * @param integer $campaignid
 * @param integer $eventid
 * @param integer $targetid
 * @param string  $targetkey
 * @param string  $action
 * @param mixed   $value
 * @return false|integer
 */
function api_data_responses_add($campaignid, $eventid, $targetid, $targetkey, $action, $value) {
	if (!is_numeric($campaignid)) {
		return false;
	}
	if (!is_numeric($eventid)) {
		return false;
	}
	if (!is_numeric($targetid)) {
		return false;
	}

	$sql = "INSERT INTO `response_data` (`campaignid`, `eventid`, `targetkey`, `targetid`, `timestamp`, `action`, `value`) VALUES (?, ?, ?, ?, NOW(), ?, ?)";
	$rs = api_db_query_write($sql, array($campaignid, $eventid, $targetkey, $targetid, $action, $value));

	if ($rs !== false) {
		return api_db_lastid();
	}

	return false;
}

/**
 *  Get all response data
 *
 * @param integer $targetid
 * @param integer $eventid
 * @param boolean $orderByTimestamp
 * @return false|array
 */
function api_data_responses_getall($targetid, $eventid = null, $orderByTimestamp = null) {

	if (!is_numeric($targetid)) {
		return false;
	}

	$sql = "SELECT `action`, `value` FROM `response_data` WHERE `targetid` = ?";
	$parameters = array($targetid);

	// Optionally restrict results to a specific eventid
	if (is_numeric($eventid)) {
		$sql .= " AND `eventid` = ?";
		$parameters[] = $eventid;
	}

	if ($orderByTimestamp) {
		$sql .= " ORDER BY timestamp ";
	}

	$rs = api_db_query_read($sql, $parameters);

	if ($rs && $rs->RecordCount() > 0) {
		return $rs->GetAssoc();
	}

	return [];
}

/**
 * Get all response data by campaignid
 *
 * @param integer $campaignid
 * @param boolean $targetkeys
 * @param boolean $returnmergedata
 * @return false|array
 */
function api_data_responses_getall_bycampaignid($campaignid, $targetkeys = false, $returnmergedata = false) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "SELECT `targetid`, ";
	if ($targetkeys) {
		$sql .= " `targetkey`, ";
	}
	$sql .= "`action`, `value` FROM `response_data` WHERE `campaignid` = ?";
	$rs = api_db_query_read($sql, array($campaignid));

	$results = [];

	if (!$rs || $rs->RecordCount() == 0) {
		return $results;
	}

	if ($returnmergedata) {
		$mergedata = api_data_merge_get_alldata($campaignid);
	}

	while ($row = $rs->FetchRow()) {
		$results[$row["targetid"]][$row["action"]] = $row["value"];

		if ($targetkeys) {
			$results[$row["targetid"]]["targetkey"] = $row["targetkey"];
		}

		if ($returnmergedata && isset($mergedata[$row['targetkey']])) {
			$results[$row['targetid']]['mergedata'] = $mergedata[$row["targetkey"]];
		}
	}

	return $results;
}

/**
 * @param integer  $campaignId
 * @param DateTime $startDate
 * @param DateTime $endDate
 * @return boolean|integer
 */
function api_data_responses_campaign_get_response_count($campaignId, DateTime $startDate = null, DateTime $endDate = null) {

	if (!is_numeric($campaignId)) {
		return false;
	}

	$sql = "SELECT count(*) as responses FROM `response_data` WHERE `campaignid` = ?";

	$params = [$campaignId];

	if ($startDate) {
		$sql .= " AND timestamp >= ? ";
		$params[] = $startDate->format("Y-m-d H:i:s");
	}

	if ($endDate) {
		$sql .= " and timestamp <= ? ";
		$params[] = $endDate->format("Y-m-d H:i:s");
	}

	$rs = api_db_query_read($sql, $params);

	$results = [];

	if (!$rs || $rs->RecordCount() == 0) {
		return 0;
	}

	$result = $rs->GetRowAssoc();
	return $result['responses'];
}

/**
 * Campaign response data summary
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_responses_summary($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sorted = array();

	$sql = "SELECT COUNT(`targetkey`) as `count`, `action`, `value` FROM (SELECT DISTINCT  `targetkey` ,  `action` ,  `value` FROM  `response_data` WHERE  `campaignid` = ? AND `action` NOT IN (?, ?, ?, ?, ?)) AS  `counted` GROUP BY  `action` ,  `value`";
	$rs = api_db_query_read($sql, array($campaignid, "DELIVERED", "EXPIRED", "SENT", "UNDELIVERED", "UNKNOWN"));

	while ($array = $rs->FetchRow()) {
		$targetResults[$array["action"]]["answers"][$array["value"]] = $array["count"];
	}

	$sql = "SELECT `action`, `value`, COUNT(DISTINCT `targetkey`) as `count` FROM `response_data` WHERE `campaignid` = ? AND `action` IN (?, ?, ?, ?, ?) GROUP BY `action` ORDER BY `action`";
	$rs = api_db_query_read($sql, array($campaignid, "DELIVERED", "EXPIRED", "SENT", "UNDELIVERED", "UNKNOWN"));

	while ($array = $rs->FetchRow()) {
		$targetResults[$array["action"]]["answers"][$array["value"]] = $array["count"];
	}

	if (isset($targetResults)) {
		foreach ($targetResults as $questions => $array) {
			$targetResults[$questions]["total"] = 0;
			$targetResults[$questions]["answercount"] = 0;

			foreach ($array["answers"] as $value => $count) {
				$targetResults[$questions]["total"] += $count;
				$targetResults[$questions]["answercount"]++;
			}
			$sorted[] = array("question" => $questions, "answers" => $targetResults[$questions]["answers"], "answercount" => $targetResults[$questions]["answercount"], "total" => $targetResults[$questions]["total"]);
		}
	}

	return $sorted;
}

/**
 * Export SMS status report
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @param mixed   $starttime
 * @param mixed   $endtime
 * @param boolean $returnbillinginfo
 * @param boolean $returnSmsContent
 * @return false|array
 */
function api_data_responses_sms_report($campaignid, $starttime = null, $endtime = null, $returnbillinginfo = false, $returnSmsContent = false) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$results = array();

	if (($starttime != null) || ($endtime != null)) {
		$st = strtotime($starttime);
		$et = strtotime($endtime);

		if (!$st || !$et) {
			return api_error_raise("Sorry, they is not a valid period to report on");
		}
	}

	$sql = "SELECT c.`eventid`, c.`targetid`, UNIX_TIMESTAMP(c.`timestamp`) as `timestamp`, c.`value` " .
		($returnSmsContent ? ',s.`contents`' : '')
		. " FROM `call_results` c" .
		($returnSmsContent ? ' LEFT JOIN `sms_sent` s ON (c.eventid=s.eventid)' : '')
		. " WHERE c.`campaignid` = ?";

	if (isset($st)) {
		$sql .= " AND c.`timestamp` >= FROM_UNIXTIME(?) AND c.`timestamp` <= FROM_UNIXTIME(?) ORDER BY c.`timestamp` ASC";
		$rs = api_db_query_read($sql, array($campaignid, $st, $et));
	} else {
		$sql .= " ORDER BY c.`timestamp` ASC";
		$rs = api_db_query_read($sql, array($campaignid));
	}

	if (!api_misc_is_cli() && $rs->RecordCount() > 5000) {
		set_time_limit(ceil($rs->RecordCount() / 100));
	}

	$timezone = api_campaigns_gettimezone($campaignid);

	while ($array = $rs->FetchRow()) {
		$date = new DateTime('@' . $array["timestamp"]);

		$results[$array["targetid"] ]["events"][(integer)$array["eventid"] ][] = [
			"timestamp" => $date->setTimezone($timezone)->format("Y-m-d H:i:s"),
			"unixtimestamp" => $array["timestamp"],
			"value" => $array["value"],
		];

		if ($returnSmsContent) {
			$results[$array['targetid']]['content'] = $array['contents'];
		}
	}

	if (isset($st)) {
		$sql = "SELECT `targetid`, `eventid`, `action`, `value`  FROM `response_data` WHERE `campaignid` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?)";
		$rs = api_db_query_read($sql, array($campaignid, $st, $et));
	} else {
		$sql = "SELECT `targetid`, `eventid`, `action`, `value`  FROM `response_data` WHERE `campaignid` = ?";
		$rs = api_db_query_read($sql, array($campaignid));
	}

	while ($array = $rs->FetchRow()) {
		if (($array["action"] != "TRACKCLIENT") && ($array["action"] != "CLICKCLIENT") && ($array["action"] != "UNSUBSCRIBECLIENT")) {
			$results[$array["targetid"]]["response_data"][$array["action"] ] = $array["value"];

			if ($returnbillinginfo) {
				$events[$array['targetid']] = $array['eventid'];
			}
		}
	}

	$sql = "SELECT `targetid`, `targetkey`, `destination`, `status`, `priority` FROM `targets` WHERE `campaignid` = ?";
	$param = [$campaignid];

	if (isset($st)) {
		if (!$results) {
			return [];
		}
		$sql .= ' AND targetid IN (' . implode(',', array_fill(0, count($results), '?')) . ')';
		$param = array_merge($param, array_keys($results));
	}

	$rs = api_db_query_read($sql, $param);

	if (empty($st)) {
		$mergedata = api_data_merge_get_alldata($campaignid);
	}

	$settings = api_campaigns_setting_getall($campaignid);

	while ($array = $rs->FetchRow()) {
		if ($returnbillinginfo) {
			$billinginfo = [];
			if (isset($results[$array['targetid']]['response_data']['SENT'])
				&& $results[$array['targetid']]['response_data']['SENT']
				&& isset($events[$array['targetid']])
			) {
				$billinginfo = api_data_rate($campaignid, 0, $array["destination"], $events[$array['targetid']], $settings);
			}
			$results[$array["targetid"]]['billinginfo'] = $billinginfo;
		}
		$results[$array["targetid"]]["targetkey"] = $array["targetkey"];
		$results[$array["targetid"]]["destination"] = $array["destination"];
		$results[$array["targetid"]]["status"] = $array["status"];
		$results[$array["targetid"]]["campaignid"] = $campaignid;
		$results[$array["targetid"]]["billableunits"] = 0;
		// This field is not valid anymore in reports. But leaving it there for backwards compatibility.
		// And also not to break any reports that do not have tests.
		$results[$array["targetid"]]["cost"] = '';
		$results[$array["targetid"]]["priority"] = $array["priority"];

		if (!empty($st)) {
			$results[$array["targetid"]]["merge_data"] = api_data_merge_get_all($campaignid, $array["targetkey"]);
		} elseif (isset($mergedata[$array["targetkey"]])) {
			$results[$array["targetid"]]["merge_data"] = $mergedata[$array["targetkey"]];
		} else {
			$results[$array["targetid"]]["merge_data"] = array();
		}
	}

	if (is_array($results)) {
		ksort($results);
	}
	return $results;
}

/**
 * Export SMS status report
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @param string  $starttime
 * @param string  $endtime
 * @param boolean $returnbillinginfo
 * @return false|array
 */
function api_data_responses_wash_report($campaignid, $starttime = null, $endtime = null, $returnbillinginfo = false) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$results = array();

	if (($starttime != null) || ($endtime != null)) {
		$st = strtotime($starttime);
		$et = strtotime($endtime);

		if (!$st || !$et) {
			return api_error_raise("Sorry, they is not a valid period to report on");
		}
	}

	if (isset($st)) {
		$sql = "SELECT `targetid` FROM `call_results` WHERE `campaignid` = ? AND `value` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?)";
		$rs = api_db_query_read($sql, array($campaignid, "DISCONNECTED", $st, $et));
	} else {
		$sql = "SELECT `targetid` FROM `call_results` WHERE `campaignid` = ? AND `value` = ?";
		$rs = api_db_query_read($sql, array($campaignid, "DISCONNECTED"));
	}

	while ($array = $rs->FetchRow()) {
		$results[$array["targetid"] ]["disconnected"] = true;
	}

	if (isset($st)) {
		$sql = "SELECT `targetid`, `eventid`, `action`, `value`  FROM `response_data` WHERE `campaignid` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?)";
		$rs = api_db_query_read($sql, array($campaignid, $st, $et));
	} else {
		$sql = "SELECT `targetid`, `eventid`, `action`, `value`  FROM `response_data` WHERE `campaignid` = ?";
		$rs = api_db_query_read($sql, array($campaignid));
	}

	$returncarrier = api_campaigns_setting_getsingle($campaignid, "returncarrier");
	$returnhlrcode = api_campaigns_setting_getsingle($campaignid, "returnhlrcode");

	while ($array = $rs->FetchRow()) {
		$results[$array["targetid"]]["response_data"][$array["action"] ] = $array["value"];
	}

	if (!isset($st)) {
		$sql = "SELECT * FROM `targets` WHERE `campaignid` = ?";
		$params = [$campaignid];
	} else {
		$subquery = 'SELECT DISTINCT c.`targetid` as `targetid` FROM `call_results` c WHERE c.`campaignid` = ? AND c.`timestamp` >= FROM_UNIXTIME(?) AND c.`timestamp` <= FROM_UNIXTIME(?) UNION ';
		$subquery .= 'SELECT DISTINCT r.`targetid` as `targetid` FROM `response_data` r WHERE r.`campaignid` = ? AND r.`timestamp` >= FROM_UNIXTIME(?) AND r.`timestamp` <= FROM_UNIXTIME(?)';
		$sql = "SELECT * FROM `targets` WHERE targetid IN ($subquery) AND campaignid=?";
		$params = [$campaignid, $st, $et, $campaignid, $st, $et, $campaignid];
	}

	$rs = api_db_query_read($sql, $params);

	if (!api_misc_is_cli() && $rs->RecordCount() > 5000) {
		set_time_limit(ceil($rs->RecordCount() / 100));
	}

	if (empty($st)) {
		$mergedata = api_data_merge_get_alldata($campaignid);
	}

	$settings = api_campaigns_setting_getall($campaignid);

	while ($array = $rs->FetchRow()) {
		$results[$array["targetid"]]["targetkey"] = $array["targetkey"];
		$results[$array["targetid"]]["destination"] = $array["destination"];
		$results[$array["targetid"]]["status"] = $array["status"];
		$results[$array["targetid"]]["billableunits"] = 0;

		// This field is not valid anymore in reports. But leaving it there for backwards compatibility.
		// And also not to break any reports that do not have tests.
		$results[$array["targetid"]]["cost"] = '';

		if (!empty($st)) {
			$results[$array["targetid"]]["merge_data"] = api_data_merge_get_all($campaignid, $array["targetkey"]);
		} elseif (isset($mergedata[$array["targetkey"]])) {
			$results[$array["targetid"]]["merge_data"] = $mergedata[$array["targetkey"]];
		} else {
			$results[$array["targetid"]]["merge_data"] = array();
		}

		if (isset($results[$array["targetid"]]["response_data"]["status"])) {
			$ok = 1;
		} elseif (($array["ringouts"] > 0) || ($array["reattempts"] == 1) || ($array["status"] == "COMPLETE")) {
			$results[$array["targetid"]]["response_data"]["status"] = "CONNECTED";
		} elseif (isset($results[$array["targetid"]]["disconnected"]) && $results[$array["targetid"]]["disconnected"]) {
			$results[$array["targetid"]]["response_data"]["status"] = "DISCONNECTED";
		} elseif (isset($results[$array["targetid"]]["response_data"]["REMOVED"]) && ($results[$array["targetid"]]["response_data"]["REMOVED"] == "DNC")) {
			$results[$array["targetid"]]["response_data"]["status"] = "DISCONNECTED";
		} elseif (($array["status"] != "READY") && ($array["status"] != "INPROGRESS")) {
			$results[$array["targetid"]]["response_data"]["status"] = "INDETERMINATE";
		}

		if ((($returncarrier == "on") || defined("RETURN_CARRIERCODE")) && isset($results[$array["targetid"]]["response_data"]["rt-carriercode"])) {
			$results[$array["targetid"]]["response_data"]["carrier"] = api_hlr_supplier_mccmnctoname($results[$array["targetid"]]["response_data"]["rt-carriercode"]);
		}

		if ((($returnhlrcode == "on") || defined("RETURN_HLRCODE")) && isset($results[$array["targetid"]]["response_data"]["rt-hlrcode"])) {
			$results[$array["targetid"]]["response_data"]["hlrcode"] = api_hlr_supplier_hlrcodetodesc($results[$array["targetid"]]["response_data"]["rt-hlrcode"]);
		}

		unset($results[$array["targetid"]]["response_data"]["rt-carriercode"]);
		unset($results[$array["targetid"]]["response_data"]["rt-hlrcode"]);
	}

	if ($returnbillinginfo) {
		foreach ($results as $targetid => $data) {
			if (isset($data["response_data"]["status"]) && ($data["response_data"]["status"] != "INDETERMINATE")) {
				$billinginfo = api_data_rate($campaignid, 0, $data["destination"], null, $settings);
				$results[$targetid]["billableunits"] = $results[$targetid]["billableunits"] + $billinginfo["units"];
			} else {
				$billinginfo = [];
			}

			$results[$targetid]['billinginfo'] = $billinginfo;
		}
	}

	ksort($results);
	return $results;
}

/**
 * Export Email status report
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @param string  $starttime
 * @param string  $endtime
 * @param boolean $returnbillinginfo
 * @return false|array
 */
function api_data_responses_email_report($campaignid, $starttime = null, $endtime = null, $returnbillinginfo = false) {

	return api_data_responses_sms_report($campaignid, $starttime, $endtime, $returnbillinginfo);
}

/**
 * Export SMS status report
 *
 * @codeCoverageIgnore
 * @param integer $campaignid
 * @param string  $starttime
 * @param string  $endtime
 * @param boolean $returnbillinginfo
 * @return false|array
 */
function api_data_responses_phone_report($campaignid, $starttime = null, $endtime = null, $returnbillinginfo = false) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	if (($starttime != null) || ($endtime != null)) {
		$st = strtotime($starttime);
		$et = strtotime($endtime);

		if (!$st || !$et) {
			return api_error_raise("Sorry, they is not a valid period to report on");
		}
	}

	if (isset($st)) {
		$sql = "SELECT `eventid`, `targetid`, UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `value` FROM `call_results` WHERE `campaignid` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?) ORDER BY `resultid` ASC";
		$rs = api_db_query_read($sql, array($campaignid, $st, $et));
	} else {
		$sql = "SELECT `eventid`, `targetid`, UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `value` FROM `call_results` WHERE `campaignid` = ? ORDER BY `resultid` ASC";
		$rs = api_db_query_read($sql, array($campaignid));
	}

	if (!api_misc_is_cli() && $rs->RecordCount() > 5000) {
		set_time_limit(ceil($rs->RecordCount() / 100));
	}

	$timezone = api_campaigns_gettimezone($campaignid);

	while ($array = $rs->FetchRow()) {
		$results[$array["targetid"]]["billableunits"] = 1;

		$date = new DateTime('@' . $array["timestamp"]);

		$results[$array["targetid"] ]["events"][(integer)$array["eventid"] ][] = [
			"timestamp" => $date->setTimezone($timezone)->format("Y-m-d H:i:s"),
			"unixtimestamp" => $array["timestamp"],
			"value" => $array["value"],
		];

		if ($array["value"] == "ANSWER") {
			$results[$array["targetid"] ]["events"][(integer)$array["eventid"] ]["billsecstart"] = $array["timestamp"];
		}

		if ($array["value"] == "GENERATED") {
			$results[$array["targetid"] ]["events"][(integer)$array["eventid"] ]["durationstart"] = $array["timestamp"];
		}

		if ($array["value"] == "DISCONNECTED") {
			$results[$array["targetid"] ]["disconnected"] = true;
		}
		if ($array["value"] == "HANGUP") {
			// FIXME Temporary hack to handle calls that don't have an ANSWER. Assume we bill from the GENERATED time. Remove January 2018.
			if (!isset($results[$array["targetid"] ]["events"][(integer)$array["eventid"] ]["billsecstart"]) && isset($results[$array["targetid"] ]["events"][(integer)$array["eventid"] ]["durationstart"])) {
				$results[$array["targetid"] ]["events"][(integer)$array["eventid"] ]["billsecstart"] = $results[$array["targetid"] ]["events"][(integer)$array["eventid"] ]["durationstart"];
			}
			if (isset($results[(integer)$array["targetid"]]["events"][(integer)$array["eventid"] ]["billsecstart"])) {
				$results[(integer)$array["targetid"] ]["events"][(integer)$array["eventid"] ]["billsec"] = $array["timestamp"] - $results[$array["targetid"] ]["events"][$array["eventid"] ]["billsecstart"];
			}

			if (isset($results[(integer)$array["targetid"]]["events"][(integer)$array["eventid"] ]["durationstart"])) {
				$results[(integer)$array["targetid"] ]["events"][(integer)$array["eventid"] ]["duration"] = $array["timestamp"] - $results[$array["targetid"] ]["events"][$array["eventid"] ]["durationstart"];
			}
			unset($results[$array["targetid"] ]["events"][$array["eventid"] ]["billsecstart"]);
			unset($results[$array["targetid"] ]["events"][$array["eventid"] ]["durationstart"]);
		}

		if ($array["value"] == "CANCEL") {
			if (isset($results[$array["targetid"] ]["events"][$array["eventid"] ]["durationstart"])) {
				$results[$array["targetid"] ]["events"][$array["eventid"] ]["duration"] = $array["timestamp"] - $results[$array["targetid"] ]["events"][$array["eventid"] ]["durationstart"];
			} else {
				$results[$array["targetid"] ]["events"][$array["eventid"] ]["duration"] = 0;
			}
			unset($results[$array["targetid"] ]["events"][$array["eventid"] ]["billsecstart"]);
			unset($results[$array["targetid"] ]["events"][$array["eventid"] ]["durationstart"]);
		}
	}

	if (isset($st)) {
		$sql = "SELECT `targetid`, `action`, `value`  FROM `response_data` WHERE `campaignid` = ? AND `timestamp` >= FROM_UNIXTIME(?) AND `timestamp` <= FROM_UNIXTIME(?) ORDER BY `resultid` ASC";
		$rs2 = api_db_query_read($sql, array($campaignid, $st, $et));
	} else {
		$sql = "SELECT `targetid`, `action`, `value`  FROM `response_data` WHERE `campaignid` = ? ORDER BY `resultid` ASC";
		$rs2 = api_db_query_read($sql, array($campaignid));
	}

	$a = $rs2->GetArray();

	if (is_array($a)) {
		foreach ($a as $array) {
			$results[$array["targetid"]]["response_data"][$array["action"]] = $array["value"];
		}
	}

	unset($a);

	if (empty($st)) {
		$mergedata = api_data_merge_get_alldata($campaignid);
	}

	$settings = api_campaigns_setting_getall($campaignid);

	$sql = "SELECT `targetid`, `targetkey`, `destination`, `status`, `priority` FROM `targets` WHERE `campaignid` = ?";
	$rs = api_db_query_read($sql, array($campaignid));

	while ($array = $rs->FetchRow()) {
		if (!empty($st) && !isset($results[$array["targetid"]])) {
			continue;
		}

		$results[$array["targetid"]]["targetkey"] = $array["targetkey"];
		$results[$array["targetid"]]["destination"] = $array["destination"];
		$results[$array["targetid"]]["status"] = $array["status"];
		$results[$array["targetid"]]["campaignid"] = $campaignid;
		$results[$array["targetid"]]["priority"] = $array["priority"];

		if (!empty($st)) {
			$results[$array["targetid"]]["merge_data"] = api_data_merge_get_all($campaignid, $array["targetkey"]);
		} elseif (isset($mergedata[$array["targetkey"]])) {
			$results[$array["targetid"]]["merge_data"] = $mergedata[$array["targetkey"]];
		} else {
			$results[$array["targetid"]]["merge_data"] = array();
		}

		if (empty($results[$array["targetid"]]["events"])) {
			$results[$array["targetid"]]["events"] = array();
		}

		if ($returnbillinginfo) {
			$billinginfo = api_data_rate_events($campaignid, $results[$array["targetid"]], $settings);
			$results[$array["targetid"]]['billinginfo'] = $billinginfo;
		}

		if (!isset($results[$array["targetid"]]["disconnected"])) {
			$results[$array["targetid"]]["disconnected"] = false;
		}

		// This field is not valid anymore in reports. But leaving it there for backwards compatibility.
		// And also not to break any reports that do not have tests.
		$results[$array["targetid"]]["cost"] = '';

		$results[$array["targetid"]]["billableunits"] = empty($results[$array["targetid"]]["events"]) ? 0 : 1;
	}

	if (isset($results) && is_array($results)) {
		ksort($results);
		return $results;
	} else {
		return false;
	}
}

/**
 * Get campign questions
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_responses_getquestions($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$questions = array();

	if ($reportformatoverride = api_campaigns_setting_getsingle($campaignid, "reportformatoverride")) {
		return array_unique(explode(",", $reportformatoverride));
	}

	$sql = "SELECT DISTINCT `action` FROM `response_data` WHERE `campaignid` = ?";
	$rs = api_db_query_read($sql, array($campaignid));

	$type = api_campaigns_setting_getsingle($campaignid, "type");

	if ($type == "email") {
		$questions = array("SENT", "TRACK", "CLICK", "WEBVIEW", "HARDBOUNCE", "SOFTBOUNCE", "UNSUBSCRIBE", "REMOVED");
	}

	if ($type == "sms") {
		$questions = array("SENT", "DELIVERED", "UNDELIVERED", "UNKNOWN", "DUPLICATE");
	}

	if ($type == "wash") {
		$questions = array("status");
		if (api_campaigns_setting_getsingle($campaignid, "returncarrier") == "on") {
			$questions[] = "carrier";
		}
		if (api_campaigns_setting_getsingle($campaignid, "returnhlrcode") == "on") {
			$questions[] = "hlrcode";
		}
	}

	while ($array = $rs->FetchRow()) {
		$questions[] = $array["action"];
	}

	$questions = array_unique($questions);

	if (($key = array_search("TRACKCLIENT", $questions)) !== false) {
		unset($questions[$key]);
	}
	if (($key = array_search("CLICKCLIENT", $questions)) !== false) {
		unset($questions[$key]);
	}
	if (($key = array_search("UNSUBSCRIBECLIENT", $questions)) !== false) {
		unset($questions[$key]);
	}
	if (($key = array_search("rt-carriercode", $questions)) !== false) {
		unset($questions[$key]);
	}
	if (($key = array_search("rt-hlrcode", $questions)) !== false) {
		unset($questions[$key]);
	}

	return $questions;
}

/**
 * Delete response data
 *
 * @param integer $campaignid
 * @return boolean
 */
function api_data_responses_delete($campaignid) {

	if (!api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = "DELETE FROM `response_data` WHERE `campaignid` = ?";
	$rs = api_db_query_write($sql, array($campaignid));

	return true;
}

/**
 * @param integer $campaignid
 * @param boolean $override_campaignid_check
 * @return boolean
 */
function api_data_responses_archive($campaignid, $override_campaignid_check = false) {
	if (!$override_campaignid_check && !api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = 'INSERT INTO `response_data_archive`
			(`resultid`, `campaignid`, `targetid`, `eventid`, `targetkey`, `timestamp`, `action`, `value`)
			SELECT `resultid`, `campaignid`, `targetid`, `eventid`, `targetkey`, `timestamp`, `action`, `value`
			FROM `response_data` WHERE `campaignid`=?';
	return api_db_query_write($sql, $campaignid) !== false;
}

/**
 * Format phone numbers
 *
 * @param string $number
 * @return string
 */
function api_data_format_telephonenumber($number) {

	$number = preg_replace("/\D/i", "", $number);

	if (preg_match("/^1300[0-9]{6}$/", $number)) {
		$number = substr($number, 0, 4) . " " . substr($number, 4, 3) . " " . substr($number, 7, 3);
	} elseif (preg_match("/^1800[0-9]{6}$/", $number)) {
		$number = substr($number, 0, 4) . " " . substr($number, 4, 3) . " " . substr($number, 7, 3);
	} elseif (preg_match("/^13[0-9]{4}$/", $number)) {
		$number = substr($number, 0, 2) . " " . substr($number, 2, 2) . " " . substr($number, 4, 2);
	} elseif (preg_match("/^0[2356789][0-9]{8}$/", $number)) {
		$number = "(" . substr($number, 0, 2) . ") " . substr($number, 2, 4) . " " . substr($number, 6, 4);
	} elseif (preg_match("/^[0-9]{8}$/", $number)) {
		$number = "(07) " . substr($number, 0, 4) . " " . substr($number, 4, 4);
	}
	return $number;
}

/**
 * Get single merge data
 *
 * @param integer $campaignid
 * @param string  $targetkey
 * @param mixed   $element
 * @return false|mixed
 */
function api_data_merge_get_single($campaignid, $targetkey, $element) {

	$sql = "SELECT `value` FROM `merge_data` WHERE `campaignid` = ? AND `targetkey` = ? AND `element` = ?";
	$rs = api_db_query_read($sql, array($campaignid, $targetkey, $element));

	if ($rs && $rs->RecordCount() >= 1) {
		return $rs->Fields("value");
	} else {
		return false;
	}
}

/**
 * Get all merge data
 *
 * @param integer $campaignid
 * @param string  $targetkey
 * @return array
 */
function api_data_merge_get_all($campaignid, $targetkey) {

	$sql = "SELECT `element`, `value` FROM `merge_data` WHERE `campaignid` = ? AND `targetkey` = ?";
	$rs = api_db_query_read($sql, array($campaignid, $targetkey));

	if ($rs && $rs->RecordCount() >= 1) {
		return $rs->GetAssoc();
	} else {
		return array();
	}
}

/**
 * Get count merge data
 *
 * @param integer $campaignid
 * @param string  $targetkey
 * @return integer
 */
function api_data_merge_get_count($campaignid, $targetkey) {
	$sql = "SELECT COUNT(*) AS count FROM `merge_data` WHERE `campaignid` = ? AND `targetkey` = ?";
	$rs = api_db_query_read($sql, [$campaignid, $targetkey]);

	return $rs ? (int) $rs->Fields('count') : false;
}

/**
 * Get all data
 *
 * @param integer $campaignid
 * @return array
 */
function api_data_merge_get_alldata($campaignid) {

	$sql = "SELECT `targetkey`, `element`, `value` FROM `merge_data` WHERE `campaignid` = ?";
	$rs = api_db_query_read($sql, array($campaignid));

	$elements = array();

	if ($rs && $rs->RecordCount() > 0) {
		while ($results = $rs->GetArray(100)) {
			foreach ($results as $array) {
				$elements[$array["targetkey"]][$array["element"]] = $array["value"];
			}
		}
	}

	return $elements;
}

/**
 * Get stats
 *
 * @param integer $campaignid
 * @return false|array
 */
function api_data_merge_stats($campaignid) {
	$elements = [];
	$sql = "SELECT `element`, COUNT(`element`) as `count` FROM `merge_data` WHERE `campaignid` = ? GROUP BY `element`";
	$rs = api_db_query_read($sql, array($campaignid));
	if ($rs && $rs->RecordCount() >= 1) {
		while ($array = $rs->FetchRow()) {
			if ($array["element"] != "rt-remoteattachments") {
				$elements[] = array("element" => $array["element"], "count" => $array["count"]);
			}
		}
		return $elements;
	}

	return false;
}

/**
 * Delete merge data
 *
 * @param integer $campaignid
 * @param mixed   $element
 * @return boolean
 */
function api_data_merge_delete($campaignid, $element) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "DELETE FROM `merge_data` WHERE `campaignid` = ? AND `element` = ?";
	$rs = api_db_query_write($sql, array($campaignid, $element));

	return true;
}

/**
 * Delete all merge data
 *
 * @param integer $campaignid
 * @return boolean
 */
function api_data_merge_delete_all($campaignid) {

	if (!is_numeric($campaignid)) {
		return false;
	}

	$sql = "DELETE FROM `merge_data` WHERE `campaignid` = ?";
	$rs = api_db_query_write($sql, array($campaignid));

	return true;
}

/**
 * @param integer $campaignid
 * @param boolean $override_campaignid_check
 * @return boolean
 */
function api_data_merge_archive($campaignid, $override_campaignid_check = false) {
	if (!$override_campaignid_check && !api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = 'INSERT INTO `merge_data_archive` (`campaignid`, `targetkey`, `element`, `value`)
			SELECT `campaignid`, `targetkey`, `element`, `value` FROM `merge_data` WHERE `campaignid`=?';
	return api_db_query_write($sql, $campaignid) !== false;
}

/**
 * Merge datat process
 *
 * @param string  $content
 * @param integer $targetid
 * @param boolean $gracefulFail
 * @return false|string
 */
function api_data_merge_process($content, $targetid, $gracefulFail = false) {

	if (preg_match_all("/\[%([^%]+)%\]/i", $content, $matches)) {
		$target = api_targets_getinfo($targetid);

		if ($target == false) {
			return $content;
		}

		foreach ($matches[0] as $element => $value) {
			$thingToMatch = $matches[1][$element]; // "Some Variable"
			$thingToReplace = $value; // "[%Some Variable%]"

			// Check if the merge field as a pipe character. If it does, we can use the test after the pipe as fallback text if the merge field doesn't exist
			if (preg_match("/^(.*)\|(.*)$/", $thingToMatch, $fallbackMatches)) {
				$mergeField = $fallbackMatches[1];
				$fallback = $fallbackMatches[2];
			} else {
				$mergeField = $thingToMatch;
			}

			$date = date_create(null, api_campaigns_gettimezone($target["campaignid"]));

			// Replace the @ symbol with the target priority

			if (preg_match("/@/", $mergeField)) {
				$mergeField = preg_replace("/@/", $target["priority"], $mergeField);
			}

			if ($mergeField == "targetkey") {
				$content = str_replace($thingToReplace, $target["targetkey"], $content);
			} elseif ($mergeField == "targetid") {
				$content = str_replace($thingToReplace, $target["targetid"], $content);
			} elseif ($mergeField == "destination") {
				$content = str_replace($thingToReplace, $target["destination"], $content);
			} elseif ($mergeField == "campaignid") {
				$content = str_replace($thingToReplace, $target["campaignid"], $content);
			} elseif ($mergeField == "enctargetid") {
				$content = str_replace($thingToReplace, api_misc_crypt_safe($target["targetid"]), $content);
			} elseif ($mergeField == "rt-date") {
				$content = str_replace($thingToReplace, $date->format("d/m/Y"), $content);
			} elseif ($mergeField == "rt-time") {
				$content = str_replace($thingToReplace, $date->format("h:ia"), $content);
			} else {
				$mergedata[$mergeField] = api_data_merge_get_single($target["campaignid"], $target["targetkey"], $mergeField);

				if (isset($mergedata[$mergeField]) && strlen($mergedata[$mergeField])) {
					$content = str_replace($thingToReplace, $mergedata[$mergeField], $content);
				} elseif (isset($fallback)) {
					$content = str_replace($thingToReplace, $fallback, $content);
				} elseif (!$gracefulFail) {
					if (api_campaigns_setting_cas($target["campaignid"], "status", "ACTIVE", "DISABLED")) {
						$email = null;

						/*
							 Send campaign merge field errors to the reporting address on failure
						*/
						$settings = api_campaigns_setting_get_multi_byitem($target["campaignid"], ['emailreport', 'name', "owner"]);
						$owneremail = api_users_setting_getsingle($settings["owner"], "emailaddress");

						if (!empty($owneremail)) {
							$email["to"] = $owneremail;
							$email["bcc"] = "ReachTEL Support <support@reachtel.com.au>";
						} elseif (!empty($settings["emailreport"])) {
								$email["to"] = $settings["emailreport"];
								$email["bcc"] = "ReachTEL Support <support@reachtel.com.au>";
						} else {
							$email["to"] = "ReachTEL Support <support@reachtel.com.au>";
						}

						$email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
						$email["subject"] = "[ReachTEL] Campaign processing error for '" . $settings["name"] . "'";
						$email["textcontent"] = "Hello,\n\nWhen attempting to send to the destination '" . $target['destination'] . "', we were unable to find a value for the merge field called '<strong>" . $thingToMatch . "'.\n\nAs a precaution, the campaign '" . $settings["name"] . "' has been disabled and should be reviewed before it is reactivated.";
						$email["htmlcontent"] = "Hello,\n\nWhen attempting to send to the destination '<strong>" . $target['destination'] . "</strong>', we were unable to find a value for the merge field called '<strong>" . $thingToMatch . "</strong>'.\n\nAs a precaution, the campaign '<strong>" . $settings["name"] . "</strong>' has been disabled and should be reviewed before it is reactivated.";
						api_email_template($email);
					}

					return false;
				} else {
					$content = str_replace($thingToReplace, "", $content);
				}
			}
		}
	}
	return $content;
}

/**
 * Get rate cost
 *
 * @param integer $campaignid
 * @param integer $billsec
 * @param integer $destination
 * @param integer $eventid
 * @param mixed   $settings
 * @return array
 * @throws Exception No intervals found for group.
 */
function api_data_rate($campaignid, $billsec = 0, $destination = 0, $eventid = null, $settings = null) {

	if (!is_array($settings)) {
		$settings = api_campaigns_setting_get_multi_byitem($campaignid, array("type", "region", "content"));
	}

	// if campaign region is set to INTERNATIONAL_SMS
	// then we should rely on destination country code
	if (isset($settings["region"]) && CAMPAIGN_SMS_REGION_INTERNATIONAL === $settings["region"]) {
		$phoneUtil = libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$numberProto = $phoneUtil->parse($destination);
			$region = strtoupper($phoneUtil->getRegionCodeForNumber($numberProto));
			$supported = api_country_supported_rating();
			if (isset($supported[$region])) {
				$settings["region"] = $region;
			}
		} catch (libphonenumber\NumberParseException $e) {
			// set default region few lines later if libphonenumber fails
			unset($settings["region"]);
		}
	}

	if (!isset($settings["region"])) {
		$settings["region"] = strtolower(DEFAULT_REGION);
	}

	if ($settings["type"] !== 'email') {
		$formattednumber = api_data_numberformat($destination, $settings["region"]);
	}

	if ($settings["type"] == "phone") {
		if ($billsec == 0) {
			return [];
		}
		$groupowner = api_campaigns_setting_getsingle($campaignid, CAMPAIGN_SETTING_GROUP_OWNER);

		$intervals = api_groups_setting_get_multi_byitem($groupowner, ['firstinterval', 'nextinterval']);

		if (!$intervals || !isset($intervals['firstinterval']) || !isset($intervals['nextinterval'])) {
			$message = sprintf('No phone intervals found for group id %s when fetching billing data', $groupowner);
			api_error_raise($message);
			throw new Exception($message);
		}

		if ($billsec <= $intervals['firstinterval']) {
			$totalunits = [BILLING_PHONE_INTERVAL_FIRST_KEY => 1];
		} else {
			$units = ($billsec - $intervals['firstinterval']) / $intervals['nextinterval'];

			if (fmod($units, 1) != 0) {
				$units = ceil($units);
			}

			$totalunits = [BILLING_PHONE_INTERVAL_FIRST_KEY => 1, BILLING_PHONE_INTERVAL_NEXT_KEY => $units];
		}

		// @codingStandardsIgnoreStart
		// Retaining the behaviour of old invoicing where if the number is invalid it gets treated as aufixedline
		return [
			'region_id' => isset($formattednumber['billing_region_id']) ?
				$formattednumber['billing_region_id'] :
				Region::REGION_AUSTRALIA,
			'destination_type_id' => isset($formattednumber['billing_destination_type_id']) ?
				$formattednumber['billing_destination_type_id'] :
				DestinationType::ID_LANDLINE,
			'units' => $totalunits
		];
		// @codingStandardsIgnoreEnd
	} elseif ($settings["type"] == "sms") {
		if (is_numeric($eventid)) {
			$sql = "SELECT LENGTH(`contents`) as `length` FROM `sms_sent` WHERE `eventid` = ?";
			$rs = api_db_query_read($sql, array($eventid));

			if ($rs->RecordCount() > 0) {
				$length = $rs->Fields("length");
			} else {
				$length = null;
			}

			if (isset($length)) {
				if ($length <= 160) {
					$units = 1;
				} else {
					if (fmod($length / 153, 1) != 0) {
						$units = (int) ceil($length / 153);
					} else {
						$units = $length / 153;
					}
				}
				// @codingStandardsIgnoreStart
				return [
					'units' => $units,
					'region_id' => isset($formattednumber['billing_region_id']) ?
						$formattednumber['billing_region_id'] :
						Region::REGION_OTHER,
				];
				// @codingStandardsIgnoreEnd
			}
		}

		return [];
	} elseif ($settings["type"] == "email") {
		return [
			'units' => 1,
		];
	} elseif ($settings["type"] == "wash") {
		// @codingStandardsIgnoreStart
		return [
			'region_id' => isset($formattednumber['billing_region_id']) ?
				$formattednumber['billing_region_id']:
				Region::REGION_OTHER,
			'destination_type_id' => isset($formattednumber['billing_destination_type_id']) ?
				$formattednumber['billing_destination_type_id'] :
				DestinationType::ID_UNKNOWN,
			'units' => 1
		];
		// @codingStandardsIgnoreEnd
	} else {
		return [];
	}
}

/**
 * Rate each of the result events
 *
 * @param integer $campaignid
 * @param mixed   $result
 * @param mixed   $settings
 * @return array
 */
function api_data_rate_events($campaignid, $result, $settings = null) {
	$billinginfo = [];

	if (isset($result["events"])) {
		foreach ($result["events"] as $eventid => $event) {
			if (isset($event["billsec"]) && ($event["billsec"] > 0)) {
				$rating = api_data_rate($campaignid, $event["billsec"], $result["destination"], $eventid, $settings);
				if ($rating) {
					$billinginfo[] = $rating;
				}
			}
		}
	}

	if (isset($result["response_data"]["CALLBACK_TRANSDUR"]) && ($result["response_data"]["CALLBACK_TRANSDUR"] > 0)) {
		$rating = api_data_rate($campaignid, $result["response_data"]["CALLBACK_TRANSDUR"], $result["response_data"]["CALLBACK_TRANSDEST"], null, $settings);
		if ($rating) {
			$billinginfo[] = $rating;
		}
	}

	if (isset($result["response_data"]["1_TRANSDUR"]) && ($result["response_data"]["1_TRANSDUR"] > 0)) {
		if (!isset($result["response_data"]["1_TRANSDEST"])) {
			$result["response_data"]["1_TRANSDEST"] = "0200000000";
		}
		$rating = api_data_rate($campaignid, $result["response_data"]["1_TRANSDUR"], $result["response_data"]["1_TRANSDEST"], null, $settings);
		if ($rating) {
			$billinginfo[] = $rating;
		}
	} elseif (isset($result["response_data"]["2_TRANSDUR"]) && ($result["response_data"]["2_TRANSDUR"] > 0)) {
		if (!isset($result["response_data"]["2_TRANSDEST"])) {
			$result["response_data"]["2_TRANSDEST"] = "0200000000";
		}
		$rating = api_data_rate($campaignid, $result["response_data"]["2_TRANSDUR"], $result["response_data"]["2_TRANSDEST"], null, $settings);
		if ($rating) {
			$billinginfo[] = $rating;
		}
	}

	return $billinginfo;
}

/**
 * @param string $destination
 * @param string $type
 * @param string $region
 * @return false|string
 */
function api_data_format($destination, $type, $region = "AU") {

	if (($type == "phone") || ($type == "sms")) {
		$formatteddestination = api_data_numberformat($destination, $region);

		if ($formatteddestination == false) {
			return api_error_raise("Sorry, that is not a valid telephone number: " . $destination);
		} else {
			$destination = $formatteddestination["destination"];
		}

		if (($type == "sms") && (!preg_match("/mobile$/", $formatteddestination["type"]))) {
			return api_error_raise("Sorry, that is not a valid mobile destination");
		}
	} elseif ($type == "email") {
		$destination = trim($destination);

		if (!filter_var($destination, FILTER_VALIDATE_EMAIL)) {
			return api_error_raise("Sorry, that is not a valid email address");
		}
	} elseif ($type == "wash") {
		$destination = substr($destination, 0, 255);

		if (empty($destination)) {
			return api_error_raise("Sorry, that is not a valid wash destination");
		}
	} else {
		return api_error_raise("Sorry, that is not a support data type");
	}

	return $destination;
}

/**
 * @param string $destination
 * @param string $region
 * @return false|string
 */
function api_data_numberformat($destination, $region = "AU") {

	if (empty($destination)) {
		return false;
	}

	global $phoneUtil;

	if (!isset($phoneUtil)) {
		$phoneUtil = libphonenumber\PhoneNumberUtil::getInstance();
	}

	if (CAMPAIGN_SMS_REGION_INTERNATIONAL === strtoupper($region)) {
		// force add a plus sign
		if ('+' !== $destination[0]) {
			$destination = "+{$destination}";
		}

		try {
			$numberProto = $phoneUtil->parse($destination);
			$region = $phoneUtil->getRegionCodeForNumber($numberProto);
		} catch (libphonenumber\NumberParseException $e) {
			return false;
		}

		$protoType = $phoneUtil->getNumberType($numberProto);
		if ($numberProto
			&& $numberProto->hasCountryCode()
			&& $numberProto->hasNationalNumber()
			&& (libphonenumber\PhoneNumberType::MOBILE === $protoType
			|| libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE === $protoType)
		) {
			$type = "othermobile";
			$supported = api_country_supported_rating();
			if (isset($supported[strtoupper($region)])) {
				$type = strtolower($region) . "mobile";
			}

			return [
				"destination" => $numberProto->getCountryCode() . $numberProto->getNationalNumber(),
				"fnn" => preg_replace("/\D/i", "", trim($phoneUtil->format($numberProto, libphonenumber\PhoneNumberFormat::NATIONAL))),
				"type" => $type,
				"numbertype" => 'Mobile',
				'countryname' => api_country_get_name($region),
				'billing_region_id' => Region::getBillingRegionIdFromCode($region),
				'billing_destination_type_id' => DestinationType::ID_MOBILE
			];
		}

		return false;
	}

	// Remove any whitespace from the beginning or end
	$destination = trim($destination);

	// Remove any superflous characters except digits and + symbols
	$destination = preg_replace("/[^0-9\+]/", "", $destination);

	// MOR-1249
	// Fix PhoneNumberUtil Bug: AU landline numbers like 014* returns type mobile
	if ($region == "AU" && strlen($destination) == 10 && preg_match("/^014/", $destination)) {
		return false;
	}

	// Strip the 0011 international prefix and add in a + symbol to meet e164 compliance
	if (preg_match("/^0011/", $destination)) {
		$destination = "+" . substr($destination, 4);
	}

	if (($region == "AU") && preg_match("/^[23478][0-9]{8}$/", $destination)) {
		$destination = "0" . $destination;
	} elseif (($region == "NZ") && (preg_match("/^[34679][0-9]{7}$/", $destination) || (preg_match("/^2[0-9]{7,9}$/", $destination)))) {
		$destination = "0" . $destination;
	} elseif (($region == "GB") && preg_match("/^[127][0-9]{8,9}$/", $destination)) {
		$destination = "0" . $destination;
	}

	$regionid = Region::getBillingRegionIdFromCode('au');

	// Google aggressively drops correctly formatted numbers that have an invalid prefix. We want to accept any correctly formatted but invalid prefix numbers.
	if (preg_match("/^614[0-9]{8}$/", $destination)) {
		return array("destination" => $destination, "type" => "aumobile", "fnn" => "0" . substr($destination, 2), "country" => "au", "countryname" => "Australia", "numbertype" => "Mobile", 'billing_region_id' => $regionid, 'billing_destination_type_id' => DestinationType::ID_MOBILE);
	} elseif (preg_match("/^61[2378][0-9]{8}$/", $destination)) {
		return array("destination" => $destination, "type" => "aufixedline", "fnn" => "0" . substr($destination, 2), "country" => "au", "countryname" => "Australia", "numbertype" => "Fixed line", 'billing_region_id' => $regionid, 'billing_destination_type_id' => DestinationType::ID_LANDLINE);
	} elseif (($region == "AU") && preg_match("/^04[0-9]{8}$/i", $destination)) {
		return array("destination" => "61" . substr($destination, 1), "type" => "aumobile", "fnn" => $destination, "country" => "au", "countryname" => "Australia", "numbertype" => "Mobile", 'billing_region_id' => $regionid, 'billing_destination_type_id' => DestinationType::ID_MOBILE);
	} elseif (($region == "AU") && preg_match("/^0[2378]{1}[0-9]{8}$/i", $destination)) {
		return array("destination" => "61" . substr($destination, 1), "type" => "aufixedline", "fnn" => $destination, "country" => "au", "countryname" => "Australia", "numbertype" => "Fixed line", 'billing_region_id' => $regionid, 'billing_destination_type_id' => DestinationType::ID_LANDLINE);
	}

	try {
		$numberProto = $phoneUtil->parse($destination, $region);
	} catch (libphonenumber\NumberParseException $e) {
		// keep going
		$e;
	}

	if (!empty($numberProto) && $phoneUtil->isValidNumber($numberProto)) {
		$number = array("destination" => $numberProto->getCountryCode() . $numberProto->getNationalNumber(),
			"fnn" => preg_replace("/\D/i", "", trim($phoneUtil->format($numberProto, libphonenumber\PhoneNumberFormat::NATIONAL))));

		$region = strtolower($phoneUtil->getRegionCodeForNumber($numberProto));

		switch ($region) {
			case "au":
				$number["countryname"] = "Australia";
				break;
			case "nz":
				$number["countryname"] = "New Zealand";
				break;
			case "sg":
				$number["countryname"] = "Singapore";
				break;
			case "gb":
				$number["countryname"] = "Great Britain";
				break;
			default:
				// We only support the above countries...at the moment
				return false;
		}

		$number["country"] = $region;

		switch ($phoneUtil->getNumberType($numberProto)) {
			case 0:
				$number["type"] = $region . "fixedline";
				$number["numbertype"] = "Fixed line";
				$number['billing_destination_type_id'] = DestinationType::ID_LANDLINE;
				break;
			case 1:
				$number["type"] = $region . "mobile";
				$number["numbertype"] = "Mobile";
				$number['billing_destination_type_id'] = DestinationType::ID_MOBILE;
				break;
			// @codeCoverageIgnoreStart
			case 2:
				$number["type"] = $region . "fixedlineormobile";
				$number["numbertype"] = "Fixed line or mobile";
				$number['billing_destination_type_id'] = DestinationType::ID_UNKNOWN;
				break;
			// @codeCoverageIgnoreEnd
			case 3:
				$number["type"] = $region . "oneeight";
				$number["numbertype"] = "Toll free";
				$number['billing_destination_type_id'] = DestinationType::ID_TOLL_FREE;
				break;
			case 4:
				$number["type"] = $region . "premiumrate";
				$number["numbertype"] = "Premium rate";
				$number['billing_destination_type_id'] = DestinationType::ID_PREMIUM_RATE;
				break;
			case 5:
				$number["type"] = $region . "onethree";
				$number["numbertype"] = "Shared cost";
				$number['billing_destination_type_id'] = DestinationType::ID_SHARED_COST;
				break;
			case 6:
				$number["type"] = $region . "voip";
				$number["numbertype"] = "VOIP";
				$number['billing_destination_type_id'] = DestinationType::ID_VOIP;
				break;
			case 7:
				$number["type"] = $region . "personalnumber";
				$number["numbertype"] = "Personal number";
				$number['billing_destination_type_id'] = DestinationType::ID_PERSONAL_NUMBER;
				break;
			case 8:
				$number["type"] = $region . "pager";
				$number["numbertype"] = "Pager";
				$number['billing_destination_type_id'] = DestinationType::ID_PAGER;
				break;
			// @codeCoverageIgnoreStart
			// Reason: Could not find numbers to test them
			case 9:
				$number["type"] = $region . "uan";
				$number["numbertype"] = "Universal access number";
				$number['billing_destination_type_id'] = DestinationType::ID_UAN;
				break;
			case 10:
				$number["type"] = $region . "unknown";
				$number["numbertype"] = "Unknown";
				$number['billing_destination_type_id'] = DestinationType::ID_UNKNOWN;
				break;
			case 27:
				$number["type"] = $region . "emergency";
				$number["numbertype"] = "Emergency";
				$number['billing_destination_type_id'] = DestinationType::ID_EMERGENCY;
				break;
			case 28:
				$number["type"] = $region . "voicemail";
				$number["numbertype"] = "Voicemail";
				$number['billing_destination_type_id'] = DestinationType::ID_UNKNOWN;
				break;
			case 29:
				$number["type"] = $region . "shortcode";
				$number["numbertype"] = "Short code";
				$number['billing_destination_type_id'] = DestinationType::ID_SHORT_CODE;
				break;
			case 30:
				$number["type"] = $region . "standardrate";
				$number["numbertype"] = "Standard rate";
				$number['billing_destination_type_id'] = DestinationType::ID_STANDARD_RATE;
				break;
			default:
				$number["type"] = $region . "unknown";
				$number["numbertype"] = "Unknown";
				$number['billing_destination_type_id'] = DestinationType::ID_UNKNOWN;
				break;
			// @codeCoverageIgnoreEnd
		}

		$number['billing_region_id'] = Region::getBillingRegionIdFromCode($region);

		return $number;
	}

	if (!preg_match("/^\+/", $destination)) {
		return api_data_numberformat("+" . $destination, $region);
	} else {
		return false;
	}
}

/**
 * @param string  $data
 * @param integer $delimiter
 * @return string
 */
function api_data_delimit($data = "", $delimiter = 0) {

	$delimiters = api_data_get_delimiters();

	if (!is_numeric($delimiter) || !isset($delimiters[$delimiter])) {
		$delimiter = 0;
	}

	if (((int)$delimiter === 0) && preg_match("/,/", $data)) {
		return "\"" . $data . "\",";
	} else {
		return $data . $delimiters[$delimiter];
	}
}

/**
 * @param boolean $ui
 * @return array
 */
function api_data_get_delimiters($ui = false) {
	$delimiters = [
		"Comma" => ",",
		"Semicolon" => ";",
		"Pipe" => "|",
		"Tab" => "\t",
	];

	return ($ui ? array_keys($delimiters) : array_values($delimiters));
}

/**
 * @param integer $delimiter_id
 * @return mixed
 */
function api_data_get_delimiter($delimiter_id) {
	$delimiters = api_data_get_delimiters();

	return isset($delimiters[$delimiter_id]) ? $delimiters[$delimiter_id] : $delimiters[0];
}

/**
 * @param integer $targetid
 * @return boolean
 */
function api_data_responses_delete_by_targetid($targetid) {
	$sql = 'DELETE from `response_data` where `targetid`=?';
	return api_db_query_write($sql, [$targetid]) !== false;
}

/**
 * @param integer $targetid
 * @return boolean
 */
function api_data_callresult_delete_by_targetid($targetid) {
	$sql = 'DELETE from `call_results` where `targetid`=?';
	return api_db_query_write($sql, [$targetid]) !== false;
}

/**
 * @param integer $campaignid
 * @param string  $targetkey
 * @return boolean
 */
function api_data_merge_delete_by_targetkey($campaignid, $targetkey) {
	$sql = 'DELETE from `merge_data` where `campaignid` = ? and `targetkey` = ?';
	return api_db_query_write($sql, [$campaignid, $targetkey]) !== false;
}
