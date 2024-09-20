<?php

function api_campaigns_hooks_toyotafsvoice($campaignid){

	// Get this campaign's name
	$name = api_campaigns_setting_getsingle($campaignid, "name");

	// Work out what the name of the FollowUp campaign should be
	$followupname = preg_replace("/Voice/", "FollowUp", $name);

	api_misc_audit("CAMPAIGN_HOOK", "Received " . $name . "; Now looking for " . $followupname);

	$followupid = api_campaigns_checknameexists($followupname);

	if(!is_numeric($followupid)) return api_error_raise("Cannot find the FollowUp campaign for " . $name);

	$targets = api_data_target_status($followupid);

	if($targets["READY"] > 0) return api_error_raise("The FollowUp campaign already has data. We won't reprocess again");

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		if(empty($target) OR ($target["status"] != "ABANDONED")) continue;

		api_targets_add_single($followupid, $target["destination"], $target["targetkey"], $target["priority"], api_data_merge_get_all($campaignid, $target["targetkey"]));
	}

	api_campaigns_setting_set($followupid, "status", "ACTIVE");

	// SFTP the TFS-Daily-SMS file

	$report = api_campaigns_report_summary_phone($campaignid);

	$filename = "ReachTel_Voice_" . date("Ymd") . ".csv";

	$tempfname = tempnam("/tmp", "toyotafs");

	file_put_contents($tempfname, $report["content"]);

	$tags = api_groups_tags_get(186);

	$options = array("hostname"  => $tags["sftp-hostname"],
		"username"  => $tags["sftp-username"],
	    "password"  => $tags["sftp-password"],
		"localfile" => $tempfname,
		"remotefile" => $tags["sftp-path-reports"] . $filename);

	$result = api_misc_sftp_put($options);

	unlink($tempfname);

	return true;

}