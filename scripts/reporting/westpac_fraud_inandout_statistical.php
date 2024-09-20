#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$timezone = "Australia/Sydney";
date_default_timezone_set($timezone);
$groupid = 384;

$start = strtotime(date("Y-m-01 00:00:00", strtotime("last day of last month")));
$end = strtotime(date("Y-m-d 23:59:59", time() - 86400));

$stats["lastweek"] = array("sent" => 0, "autosent" => 0, "cardfraudsms" => 0, "autoreply" => 0, "manualsent" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0, "otherreplies" => 0, "replies" => 0);
$stats["thismonth"] = array("sent" => 0, "autosent" => 0, "cardfraudsms" => 0, "autoreply" => 0, "manualsent" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0, "otherreplies" => 0, "replies" => 0);
$stats["lastmonth"] = array("sent" => 0, "autosent" => 0, "cardfraudsms" => 0, "autoreply" => 0, "manualsent" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0, "otherreplies" => 0, "replies" => 0);

foreach(api_keystore_getidswithvalue("USERS", "groupowner", $groupid) as $userid) if(api_security_check(120, null, true, $userid) OR api_security_check(125, null, true, $userid)) $userdata[$userid] = api_users_setting_getall($userid);

$sql = "SELECT UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `sms_account`, `from`, `contents` FROM `sms_received` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `sms_account` IN (?, ?, ?) ORDER BY `timestamp` ASC";
$rs = api_db_query_read($sql, array($start, $end, 300, 318, 327));

while(!$rs->EOF){

    if(!isset($stats[date("Y-m-d", $rs->Fields("timestamp"))])) $stats[date("Y-m-d", $rs->Fields("timestamp"))] = array("sent" => 0, "autosent" => 0, "cardfraudsms" => 0, "autoreply" => 0, "manualsent" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0, "otherreplies" => 0, "replies" => 0);

	if(preg_match("/^yes(.|!)?$/i", $rs->Fields("contents")) OR preg_match("/^y$/i", $rs->Fields("contents"))) $stats[date("Y-m-d", $rs->Fields("timestamp"))]["yesreplies"]++;
	else if(preg_match("/^no(.|!)?$/i", $rs->Fields("contents")) OR preg_match("/^n$/i", $rs->Fields("contents"))) $stats[date("Y-m-d", $rs->Fields("timestamp"))]["noreplies"]++;
	else if(preg_match("/^fraud(.|!)?$/i", $rs->Fields("contents"))) $stats[date("Y-m-d", $rs->Fields("timestamp"))]["fraudreplies"]++;
	else $stats[date("Y-m-d", $rs->Fields("timestamp"))]["otherreplies"]++;

	$stats[date("Y-m-d", $rs->Fields("timestamp"))]["replies"]++;

	$rs->MoveNext();

}

$sql = "SELECT `id`, UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `userid`, `from`, `destination`, `message` FROM `sms_out` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `userid` IN (";
$variables = array($start, $end);

foreach($userdata as $userid => $user){

	$sql .= "?, ";
	$variables[] = $userid;

}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";
$rs = api_db_query_read($sql, $variables);

while(!$rs->EOF){

    if(!isset($stats[date("Y-m-d", $rs->Fields("timestamp"))])) $stats[date("Y-m-d", $rs->Fields("timestamp"))] = array("sent" => 0, "autosent" => 0, "cardfraudsms" => 0, "autoreply" => 0, "manualsent" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0, "otherreplies" => 0, "replies" => 0);

	$stats[date("Y-m-d", $rs->Fields("timestamp"))]["sent"]++;

	if(in_array($rs->Fields("userid"), array(1452, 1533, 1534))) $stats[date("Y-m-d", $rs->Fields("timestamp"))]["autosent"]++;
	elseif(in_array($rs->Fields("userid"), array(1500))) $stats[date("Y-m-d", $rs->Fields("timestamp"))]["cardfraudsms"]++;
	else $stats[date("Y-m-d", $rs->Fields("timestamp"))]["manualsent"]++;

	$rs->MoveNext();

}

$sql = "SELECT UNIX_TIMESTAMP(`timestamp`) as `timestamp`, `userid` FROM `sms_api_mapping` WHERE `timestamp` > FROM_UNIXTIME(?) AND `timestamp` < FROM_UNIXTIME(?) AND `userid` IN (";
$variables = array($start, $end);

foreach($userdata as $userid => $user){

	$sql .= "?, ";
	$variables[] = $userid;

}

$sql = substr($sql, 0, -2) . ") ORDER BY `timestamp` ASC";
$rs = api_db_query_read($sql, $variables);

while(!$rs->EOF){

    if(!isset($stats[date("Y-m-d", $rs->Fields("timestamp"))])) $stats[date("Y-m-d", $rs->Fields("timestamp"))] = array("sent" => 0, "autosent" => 0, "manualsent" => 0, "yesreplies" => 0, "noreplies" => 0, "fraudreplies" => 0, "otherreplies" => 0, "replies" => 0);

	if(in_array($rs->Fields("userid"), array(1452, 1534))) {

		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["autosent"]++;
		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["sent"]++;

	} elseif(in_array($rs->Fields("userid"), array(1500))) {

		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["cardfraudsms"]++;
		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["sent"]++;

	} elseif(in_array($rs->Fields("userid"), array(1533))) {

		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["autoreply"]++;

	} else {

		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["manualsent"]++;
		$stats[date("Y-m-d", $rs->Fields("timestamp"))]["sent"]++;
	}

	$rs->MoveNext();

}

ksort($stats);

$data = [["period", "westpac total sent", "westpac auto sent", "cardfraudsms sent", "westpac manual sent", "westpac auto reply", "customer responses", "customer yes replies", "customer no replies", "customer fraud replies", "customer response rate", "customer yes response rate", "customer no response rate", "customer fraud response rate"]];

foreach($stats as $day => $stat){

	$timestamp = strtotime($day . " 00:00:00");

	if($timestamp >= strtotime("last monday")) { // This week
        $data[] = [
			$day,
			$stat["sent"],
			$stat["autosent"],
			$stat["cardfraudsms"],
			$stat["manualsent"],
			$stat["autoreply"],
			$stat["replies"],
			$stat["yesreplies"],
			$stat["noreplies"],
			$stat["fraudreplies"],
			sprintf("%01.0f%%", $stat["sent"] ? ($stat["replies"] / $stat["sent"])*100 : 0),
			sprintf("%01.0f%%", $stat["replies"] ? ($stat["yesreplies"] / $stat["replies"])*100 : 0),
			sprintf("%01.0f%%", $stat["replies"] ? ($stat["noreplies"] / $stat["replies"])*100 : 0) ,
			sprintf("%01.0f%%", $stat["replies"]? ($stat["fraudreplies"] / $stat["replies"])*100 : 0)
		];

	} else if($timestamp >= (strtotime("last monday") - (86400 * 7))){ // Last week

		$stats["lastweek"]["sent"] = $stats["lastweek"]["sent"] + $stat["sent"];
		$stats["lastweek"]["autosent"] = $stats["lastweek"]["autosent"] + $stat["autosent"];
		$stats["lastweek"]["cardfraudsms"] = $stats["lastweek"]["cardfraudsms"] + $stat["cardfraudsms"];
		$stats["lastweek"]["manualsent"] = $stats["lastweek"]["manualsent"] + $stat["manualsent"];
		$stats["lastweek"]["autoreply"] = $stats["lastweek"]["autoreply"] + $stat["autoreply"];
		$stats["lastweek"]["yesreplies"] = $stats["lastweek"]["yesreplies"] + $stat["yesreplies"];
		$stats["lastweek"]["noreplies"] = $stats["lastweek"]["noreplies"] + $stat["noreplies"];
		$stats["lastweek"]["fraudreplies"] = $stats["lastweek"]["fraudreplies"] + $stat["fraudreplies"];
		$stats["lastweek"]["otherreplies"] = $stats["lastweek"]["otherreplies"] + $stat["otherreplies"];
		$stats["lastweek"]["replies"] = $stats["lastweek"]["replies"] + $stat["replies"];

	}

	if($timestamp < strtotime(date("Y-m-01 00:00:00"))) { // Last month

		$stats["lastmonth"]["sent"] = $stats["lastmonth"]["sent"] + $stat["sent"];
		$stats["lastmonth"]["autosent"] = $stats["lastmonth"]["autosent"] + $stat["autosent"];
		$stats["lastmonth"]["cardfraudsms"] = $stats["lastmonth"]["cardfraudsms"] + $stat["cardfraudsms"];
		$stats["lastmonth"]["manualsent"] = $stats["lastmonth"]["manualsent"] + $stat["manualsent"];
		$stats["lastmonth"]["autoreply"] = $stats["lastmonth"]["autoreply"] + $stat["autoreply"];
		$stats["lastmonth"]["yesreplies"] = $stats["lastmonth"]["yesreplies"] + $stat["yesreplies"];
		$stats["lastmonth"]["noreplies"] = $stats["lastmonth"]["noreplies"] + $stat["noreplies"];
		$stats["lastmonth"]["fraudreplies"] = $stats["lastmonth"]["fraudreplies"] + $stat["fraudreplies"];
		$stats["lastmonth"]["otherreplies"] = $stats["lastmonth"]["otherreplies"] + $stat["otherreplies"];
		$stats["lastmonth"]["replies"] = $stats["lastmonth"]["replies"] + $stat["replies"];
	}

	if($timestamp >= strtotime(date("Y-m-01 00:00:00"))) { // This month

		$stats["thismonth"]["sent"] = $stats["thismonth"]["sent"] + $stat["sent"];
		$stats["thismonth"]["autosent"] = $stats["thismonth"]["autosent"] + $stat["autosent"];
		$stats["thismonth"]["cardfraudsms"] = $stats["thismonth"]["cardfraudsms"] + $stat["cardfraudsms"];
		$stats["thismonth"]["manualsent"] = $stats["thismonth"]["manualsent"] + $stat["manualsent"];
		$stats["thismonth"]["autoreply"] = $stats["thismonth"]["autoreply"] + $stat["autoreply"];
		$stats["thismonth"]["yesreplies"] = $stats["thismonth"]["yesreplies"] + $stat["yesreplies"];
		$stats["thismonth"]["noreplies"] = $stats["thismonth"]["noreplies"] + $stat["noreplies"];
		$stats["thismonth"]["fraudreplies"] = $stats["thismonth"]["fraudreplies"] + $stat["fraudreplies"];
		$stats["thismonth"]["otherreplies"] = $stats["thismonth"]["otherreplies"] + $stat["otherreplies"];
		$stats["thismonth"]["replies"] = $stats["thismonth"]["replies"] + $stat["replies"];
	}

}

// Leave space in between
$data[] = [];
$data[] = [];

$data[]= [
	"Last week (" . date("d/m/Y", strtotime("last monday") - (86400 * 7)) . " to " . date("d/m/Y", strtotime("last monday") - 86400) . ")",
	$stats["lastweek"]["sent"],
	$stats["lastweek"]["autosent"],
	$stats["lastweek"]["cardfraudsms"],
	$stats["lastweek"]["manualsent"],
	$stats["lastweek"]["autoreply"],
	$stats["lastweek"]["replies"],
	$stats["lastweek"]["yesreplies"],
	$stats["lastweek"]["noreplies"],
	$stats["lastweek"]["fraudreplies"],
	sprintf("%01.0f%%", $stats["lastweek"]["sent"] ? ($stats["lastweek"]["replies"] / $stats["lastweek"]["sent"])*100 : 0),
	sprintf("%01.0f%%", $stats["lastweek"]["replies"] ? ($stats["lastweek"]["yesreplies"] / $stats["lastweek"]["replies"])*100 : 0),
	sprintf("%01.0f%%", $stats["lastweek"]["replies"] ? ($stats["lastweek"]["noreplies"] / $stats["lastweek"]["replies"])*100 : 0),
	sprintf("%01.0f%%", $stats["lastweek"]["replies"] ? ($stats["lastweek"]["fraudreplies"] / $stats["lastweek"]["replies"])*100 : 0)
];

// Leave space in between
$data[] = [];
$data[] = [];

$data[] = [
	"This month to date (" . date("01/m/Y", strtotime("yesterday")) . " to " . date("d/m/Y", strtotime("yesterday")) . ")",
	$stats["thismonth"]["sent"],
	$stats["thismonth"]["autosent"],
	$stats["thismonth"]["cardfraudsms"],
	$stats["thismonth"]["manualsent"],
	$stats["thismonth"]["autoreply"],
	$stats["thismonth"]["replies"],
	$stats["thismonth"]["yesreplies"],
	$stats["thismonth"]["noreplies"],
	$stats["thismonth"]["fraudreplies"],
	sprintf("%01.0f%%", $stats["thismonth"]["sent"] ? ($stats["thismonth"]["replies"] / $stats["thismonth"]["sent"])*100 : 0),
	sprintf("%01.0f%%", $stats["thismonth"]["replies"] ? ($stats["thismonth"]["yesreplies"] / $stats["thismonth"]["replies"])*100 : 0),
	sprintf("%01.0f%%", $stats["thismonth"]["replies"] ? ($stats["thismonth"]["noreplies"] / $stats["thismonth"]["replies"])*100 : 0),
	sprintf("%01.0f%%", $stats["thismonth"]["replies"] ? ($stats["thismonth"]["fraudreplies"] / $stats["thismonth"]["replies"])*100 : 0)
];

// Leave space in between
$data[] = [];
$data[] = [];

$data[] = [
	"Last month (" . date("F Y", strtotime(date("Y-m-01 00:00:00")) - 86400) . ")",
	$stats["lastmonth"]["sent"],
	$stats["lastmonth"]["autosent"],
	$stats["lastmonth"]["cardfraudsms"],
	$stats["lastmonth"]["manualsent"],
	$stats["lastmonth"]["replies"],
	$stats["lastmonth"]["autoreply"],
	$stats["lastmonth"]["yesreplies"],
	$stats["lastmonth"]["noreplies"],
	$stats["lastmonth"]["fraudreplies"],
	sprintf("%01.0f%%", $stats["lastmonth"]["sent"] ? ($stats["lastmonth"]["replies"] / $stats["lastmonth"]["sent"])*100 : 0),
	sprintf("%01.0f%%", $stats["lastmonth"]["replies"] ? ($stats["lastmonth"]["yesreplies"] / $stats["lastmonth"]["replies"])*100 : 0),
	sprintf("%01.0f%%", $stats["lastmonth"]["replies"] ? ($stats["lastmonth"]["noreplies"] / $stats["lastmonth"]["replies"])*100 : 0),
	sprintf("%01.0f%%", $stats["lastmonth"]["replies"] ? ($stats["lastmonth"]["fraudreplies"] / $stats["lastmonth"]["replies"])*100 : 0)
];

$contents = api_csv_string($data);

$email["to"]	      = api_cron_tags_get(44, "reporting-destination");
$email["subject"]     = "[ReachTEL] Fraud SMS statistical report - " . date("d/m/Y", strtotime("yesterday"));
$email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL Fraud SMS statistical report";
$email["htmlcontent"] = "Hello,<br /><br />Please find attached the ReachTEL Fraud SMS statistical report";
$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

$email["attachments"][] = array("content" => $contents, "filename" => "ReachTEL-Fraud-SMS-Statistical-" . date("Ymd", strtotime("yesterday")) . ".csv");

api_email_template($email);