<?php

function api_campaigns_hooks_boqcollectionsvoicepreprocess($campaignid){

	if(is_numeric(api_campaigns_setting_getsingle($campaignid, "finishtime"))) return api_error_raise("Sorry, it looks as though this campaign has already finished");

	if((api_campaigns_setting_getsingle($campaignid, "type") != "phone") OR (api_campaigns_setting_getsingle($campaignid, "groupowner") != 178)) return api_error_raise("Sorry, that doesn't look like a BOQ Collections campaign");

	// Check back three days
	$startdate = date("Y-m-d 00:00:00", strtotime("-3 days"));

	foreach(api_targets_listall($campaignid) as $targetid => $destination){

		$target = api_targets_getinfo($targetid);

		/*

			BOQ names their customers "Customer_Name", "Customer_Name2", "Customer_Name3" and "Customer_Name4".
			If we are at priority 1, search for "Customer_Name" instead of "Customer_Name1".

		*/

		if($target["priority"] == 1) {
			$priority = "";
			api_targets_add_extradata_single($campaignid, $target["targetkey"], "Customer_Name1", api_data_merge_get_single($campaignid, $target["targetkey"], "Customer_Name"));
		} else {
			$priority = $target["priority"];
		}

		// Add some date of birth magic

		$dob = api_data_merge_get_single($campaignid, $target["targetkey"], "DOB" . $priority);

		if(empty($dob)) { // Check if we have a company. If so, delete it.
			api_targets_delete_single_bytargetid($target["targetid"]);
			continue;
		}

		$dob = strtotime($dob);
		api_targets_add_extradata_single($campaignid, $target["targetkey"], "DobDayMonth" . $target["priority"], date("jS F", $dob));
		api_targets_add_extradata_single($campaignid, $target["targetkey"], "DobYear" . $target["priority"], date("Y", $dob));

		// Delete anything that has been contacted in the last 3 days

		$sql = "SELECT * FROM `response_data` WHERE `targetkey` = ? AND `timestamp` > ? AND ((`value` = ?) OR (`value` = ?) OR (`action` = ? AND `value` = ?) OR (`value` = ?))";
		$rs = api_db_query_read($sql, array($target["targetkey"], $startdate, "ISCUSTOMER", "1_ISCUSTOMER", "0_AMD", "MACHINE", "SENT"));

		if($rs->RecordCount() > 0) if($target["status"] == "READY") api_targets_delete_single_bytargetid($target["targetid"]);

	}

	api_targets_dedupe($campaignid);

	return true;

}