<?php

require_once("Morpheus/api.php");

foreach(api_campaigns_list_active() as $campaignid){

	if(api_campaigns_setting_getsingle($campaignid, "stopateod") == "on"){

		// Stop the campaign
		api_campaigns_setting_set($campaignid, "status", "DISABLED");

		$settings = api_campaigns_setting_getall($campaignid);

		// Generate whatever reporting is required
		api_campaigns_setting_set($campaignid, "finishtime", time());

		if(!empty($settings["noreport"]) AND ($settings["noreport"] != "on")) api_queue_add("report", $campaignid);

		if(!empty($settings["delayedreport1"]) AND is_numeric($settings["delayedreport1"])) api_queue_add("report", $campaignid, date("Y-m-d H:i:s", time()+($settings["delayedreport1"]*60)));
		if(!empty($settings["delayedreport2"]) AND is_numeric($settings["delayedreport2"])) api_queue_add("report", $campaignid, date("Y-m-d H:i:s", time()+($settings["delayedreport2"]*60)));

		print "Stopped: " . $settings["name"] . "\n";

	}

}

?>
