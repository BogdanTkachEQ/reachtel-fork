<?php

require_once("Morpheus/api.php");

$sql = "SELECT * FROM `targets` WHERE `status` = ?";
$rs = api_db_query_read($sql, array("INPROGRESS"));

if($rs->RecordCount() == 0) exit;

$calls = api_voice_servers_channels_summary();

$targets = array();

foreach($calls as $call){

	if(!empty($call["Account"]) AND is_numeric($call["Account"]) AND !in_array($call["Account"], $targets)) $targets[] = $call["Account"];
	if(!empty($call["Accountcode"]) AND is_numeric($call["Accountcode"]) AND !in_array($call["Accountcode"], $targets)) $targets[] = $call["Accountcode"];

}

$type = array();
$stuck = array();

while($target = $rs->FetchRow()) {

	if(empty($type[$target["campaignid"]])) $type[$target["campaignid"]] = api_campaigns_setting_getsingle($target["campaignid"], "type");

	if(!in_array($type[$target["campaignid"]], array("phone", "wash"))) continue;

	if(!in_array($target["targetid"], $targets)) $stuck[] = $target["targetid"]; // Possible stuck call

}

if(count($stuck) > 0) sleep(90);

if(count($stuck) > 0) foreach($stuck as $targetid){

	$target = api_targets_getinfo($targetid);

	if($target["status"] == "INPROGRESS"){

		print "Unsticking target " . $target["targetid"] . "\n";

		if($type[$target["campaignid"]] == "wash") api_targets_updatestatus($target["targetid"], "READY", null, 1);
		else api_targets_updatestatus($target["targetid"], "COMPLETE", null);

		api_voice_supplier_deletecall($target["targetid"]);

	} // No need to unstick target
}

$sql = "SELECT  `provider_map`.`targetid` FROM  `provider_map` ,  `targets` WHERE  `provider_map`.`targetid` =  `targets`.`targetid` AND  `targets`.`status` !=  ?";
$rs = api_db_query_read($sql, array("INPROGRESS"));

if($rs && ($rs->RecordCount() > 0)) {

	api_voice_supplier_deletecall($rs->Fields("targetid"));

	print "Dumped " . $rs->RecordCount() . " provider calls\n";

}