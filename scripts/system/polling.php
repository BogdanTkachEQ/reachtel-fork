<?php

require_once('Morpheus/api.php');


$counters = array();
$groups = array();

$time = time();

// Asterisk connectivity
foreach(api_voice_servers_listall(1) as $serverid => $name) {
	if(api_voice_servers_setting_getsingle($serverid, "status") == "active"){
		$gauges[] = array("name" => "Asterisk_channels", "description" => "Asterisk channels", "measure_time" => $time, "source" =>  $name, "value" => (integer)api_voice_servers_channels_count($serverid));
	}
}

// Voice supplier channels
foreach(api_voice_supplier_listall(1) as $providerid => $name) {
	if(api_voice_supplier_setting_getsingle($providerid, "status") == "ACTIVE"){
		$gauges[] = array("name" => "morpheus.provider.channels", "description" => "Number of channels for provider " . $name, "measure_time" => $time, "source" => $name, "value" => api_voice_supplier_channels($providerid));
	}
}

// Queue depth
$gauges[] = array("name" => "Morpheus_queue_depth", "description" => "Morpheus queue depth", "measure_time" => $time, "source" => "Morpheus", "value" => (integer)api_queue_getjobcount());

// Gearman queue depth
require_once("Morpheus/api_queue_gearman.php");
$gearman = api_queue_gearman_getjobcount();
foreach($gearman as $queue => $stats) {
	if(($queue != "total") AND ($stats["jobs"] > 0)) $gauges[] = array("name" => "Morpheus_queue_depth", "description" => "Morpheus queue depth", "measure_time" => $time, "source" => $queue, "value" => (integer)$stats["jobs"]);
}

$counters[] = array("name" => "Morpheus_queue", "description" => "Morpheus queue", "measure_time" => $time, "source" => "Morpheus", "value" => (integer)api_db_nextid("event_queue"));

$sql = "SELECT `id`, `value` FROM  `key_store`  WHERE  `type` =  ? AND  `item` =  ?";
$rs = api_db_query_read($sql, array("SMSSUPPLIER", "counter"));

while($row = $rs->FetchRow()) {
	$counters[] = array("name" => "Morpheus_SMS", "description" => "Morpheus SMS", "measure_time" => $time, "source" => preg_replace("/[^A-Za-z0-9\.:\-]/i", "_", api_sms_supplier_setting_getsingle($row["id"], "name")), "value" => (integer)$row["value"]);
}

$peers = api_voice_channels_quality();

if(is_array($peers)) foreach($peers as $peer => $data) {

	if($data["packets"]["recv"] > 0) $data["packets"]["recvlostpercent"] = sprintf("%01.2f", ($data["packets"]["recvlost"] / $data["packets"]["recv"]) * 100);
	else $data["packets"]["recvlostpercent"] = 0;

	if($data["packets"]["sent"] > 0) $data["packets"]["sentlostpercent"] = sprintf("%01.2f", ($data["packets"]["sentlost"] / $data["packets"]["sent"]) * 100);
	else $data["packets"]["sentlostpercent"] = 0;

	if($data["packets"]["recvlostpercent"] > 0) $gauges[] = array("name" => "SIP_QoS", "description" => "SIP Quality of Service", "measure_time" => $time, "source" => $peer . "_recv", "value" => $data["packets"]["recvlostpercent"]);
	if($data["packets"]["sentlostpercent"] > 0) $gauges[] = array("name" => "SIP_QoS", "description" => "SIP Quality of Service", "measure_time" => $time, "source" => $peer . "_sent", "value" => $data["packets"]["sentlostpercent"]);
}

api_misc_metrics_submit(array("counters" => $counters, "gauges" => $gauges));

foreach (api_campaigns_list_active() as $campaignid) {
	// It's possible that there is a race condition if a campaign has just been created and the healthcheck hasn't yet been run.
	// If the healthcheck fails once, wait three seconds and see if it fails again. If so, error out.
	if (!api_campaigns_healthcheck($campaignid) && (sleep(3) === 0) && !api_campaigns_healthcheck($campaignid)) {
		$name = api_campaigns_setting_getsingle($campaignid, "name");
		$created = date("Y-m-d H:i:s", api_campaigns_setting_getsingle($campaignid, "created"));
		$heartbeat = api_campaigns_setting_getsingle($campaignid, "heartbeattimestamp");
		$heartbeat = $heartbeat ? date("Y-m-d H:i:s", $heartbeat) : 'No heartbeat created';
		$url = "https://morpheus.reachtel.com.au/admin_listcampaign.php?campaignid={$campaignid}";
		$content = "We have detected a campaign spooler outage event for campaign:"
			. "\n - Id: {$campaignid}"
			. "\n - Name: {$name}"
			. "\n - Created: {$created}"
			. "\n - Last Heartbeat: {$heartbeat}"
			. "\n - URL: {$url}";

		$email["to"]          = "ReachTEL Support <support@reachtel.com.au>";
		$email["subject"]     = "[ReachTEL] Outage Report - " . date("Y-m-d H:i:s");
		$email["textcontent"] = $content;
		$email["htmlcontent"] = nl2br($content) . " <a href='{$url}'>Link</a>";
		api_email_template($email);
	}
}