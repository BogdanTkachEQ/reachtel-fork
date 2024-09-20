<?php

function api_campaigns_hooks_wbccallmeprocess($campaignid){

	if(is_numeric(api_campaigns_setting_getsingle($campaignid, "finishtime"))) return api_error_raise("Sorry, it looks as though this campaign has already finished");

	if((api_campaigns_setting_getsingle($campaignid, "type") != "sms") OR api_campaigns_tags_get("wbc-collections-callme")) return api_error_raise("Sorry, that doesn't look like a WBC Call Me campaign");

	// Check back so many days
	$startdate14 = date("Y-m-d 00:00:00", strtotime("-14 days"));

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		if(!in_array($target["status"], array("READY", "REATTEMPT"))) continue;

		// Check if we've sent any messages in the last 14 days

		$sql = "SELECT * FROM `response_data` WHERE `campaignid` != ? AND `targetkey` = ? AND `timestamp` > ? AND `action` = ? ORDER BY `timestamp` DESC LIMIT 1";
		$rs14 = api_db_query_read($sql, array($campaignid, $target["targetkey"], $startdate14, "wbc-callme-messagetoken"));

		if($rs14->RecordCount() > 0) {

			api_data_responses_add($campaignid, 0, $target["targetid"], $target["targetkey"], "REMOVED", "RECENTMESSAGE");
			api_targets_updatestatus($target["targetid"], "ABANDONED", null);

			continue;

		}

		switch(api_data_merge_get_single($campaignid, $target["targetkey"], "BankOfOrg")){

			case "S":
				$bank = "St. George";
				break;
			case "M":
				$bank = "Bank of Melbourne";
				break;
			case "B":
				$bank = "BankSA";
				break;
			default:
				// Don't send messages if we don't know which bank
				api_data_responses_add($campaignid, 0, $target["targetid"], $target["targetkey"], "REMOVED", "INVALIDBANKCODE");
				api_targets_updatestatus($target["targetid"], "ABANDONED", null);
				continue 2;
		}

		$message = "Hi, this is " . $bank . ". Your credit card ending in " . substr($target["targetkey"], -4) . " is overdue. We would like to work with you to find a solution. Please reply CALL ME and we'll call you back.";

		api_targets_add_extradata_single($target["campaignid"], $target["targetkey"], "messagecontent", $message);
		api_data_responses_add($target["campaignid"], 0, $target["targetid"], $target["targetkey"], "wbc-callme-messagetoken", "wbc-callme-message1");

	}

	return true;

}

?>