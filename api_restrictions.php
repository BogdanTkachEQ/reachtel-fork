<?php

use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\Validators\CampaignTimingValidationService;
use Services\Campaign\Validators\RecurringTimePickerValidationService;
use Services\Campaign\Validators\SpecificTimePickerValidationService;
use Services\Container\ContainerAccessor;
use Services\Utils\CampaignUtils;
use Services\Exceptions\CampaignValidationException;

// Add

function api_restrictions_time_recurring_add($campaignid, $starttime, $endtime, $periodid = null, $daysOfWeek = CampaignUtils::TIMING_RECURRING_WEEKDAYS_BITWISE) {
	if (!preg_match("/^(0?[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $starttime)) return api_error_raise("Sorry, that is not a valid start time");
	if (!preg_match("/^(0?[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/", $endtime)) return api_error_raise("Sorry, that is not a valid end time");

	$starttime = str_pad($starttime, 5, "0", STR_PAD_LEFT) . ":00";
	$endtime = str_pad($endtime, 5, "0", STR_PAD_LEFT) . ":00";

	if ($starttime >= $endtime)  return api_error_raise("Sorry, the end time must be after the start time");
	if (!is_numeric($campaignid)) return api_error_raise("Sorry, that is not a valid campaign");
	if (!empty($periodid) AND !is_numeric($periodid)) return api_error_raise("Sorry, that is not a valid existing period");
	if (!CampaignUtils::isValidDaysOfWeek($daysOfWeek) || $daysOfWeek == 0) {
		return api_error_raise("Sorry, the recurring time period days of week are invalid");
	}

	try {
		$timezone = api_campaigns_gettimezone($campaignid);

		$campaignSettingsDirector = ContainerAccessor::getContainer()->get(CampaignSettingsDirector::class);
		$recurringTimeValidationService = ContainerAccessor::getContainer()->get(RecurringTimePickerValidationService::class);
		$campaignSettings = $campaignSettingsDirector->buildCampaignSettings($campaignid);
		if (
			!$recurringTimeValidationService
			->isValid(
				$campaignSettings,
				[
					'timezone' => $timezone,
					'recurring' => [
						[
							'starttime' => $starttime,
							'endtime' => $endtime,
							'daysofweek' => $daysOfWeek
						]
					]
				]
			)
		) {
			throw new CampaignValidationException(
				ContainerAccessor::getContainer()
					->get(CampaignTimingValidationService::class)->getDisclaimer($campaignSettings)
			);
		}
	} catch (Exception $e) {
		return api_error_raise($e->getMessage());
	}

	$structure = api_restrictions_time_structure($campaignid);

	if (!is_numeric($periodid)) { // This is a new period so generate a new periodid
		$periodid = api_misc_uniqueid();
	}

	$structure["recurring"][$periodid] = array("starttime" => $starttime, "endtime" => $endtime, "daysofweek" => $daysOfWeek);

	api_campaigns_setting_set($campaignid, "timing", serialize($structure));

	return $periodid;
}

// Remove

function api_restrictions_time_recurring_remove($campaignid, $periodid) {

	if (!is_numeric($campaignid)) return false;
	if (!is_numeric($periodid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	if(isset($structure["recurring"][$periodid])) {

		unset($structure["recurring"][$periodid]);

		api_campaigns_setting_set($campaignid, "timing", serialize($structure));

		return true;

	} else return false;
}

// List
// Specific


function api_restrictions_time_recurring_listsingle($campaignid, $periodid) {

	if (!is_numeric($campaignid)) return false;
	if (!is_numeric($periodid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	return $structure["recurring"][$periodid];
}

// All

function api_restrictions_time_recurring_listall($campaignid) {

	if (!is_numeric($campaignid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	$recurringformat = "H:i:s";

	$timezone = api_campaigns_gettimezone($campaignid);

	foreach($structure["recurring"] as $periodid => $period) {
		// Check if we have a DayOfWeek bitwise set and if not, default to a bitwise of 31 which is Mon-Fri
		if (isset($period['daysofweek']) && is_numeric($period['daysofweek'])) {
			$dayOfWeekBitwise = $period['daysofweek'];
		} else {
			$dayOfWeekBitwise = CampaignUtils::TIMING_RECURRING_WEEKDAYS_BITWISE; // 31 aka Mon-Fri
			// update structure with default
			$structure['recurring'][$periodid]['daysofweek'] = $dayOfWeekBitwise;
		}

		// Takes a string like "09:00:00" and creates a DateTime object for the start and end times
		$starttime = date_create_from_format($recurringformat, $period["starttime"], $timezone);
		$endtime = date_create_from_format($recurringformat, $period["endtime"], $timezone);

		// We need to convert the PHP day of week value (1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat,7=Sun) into a bitmask (1=Mon,2=Tue,4=Wed,8=Thu,16=Fri,32=Sat,64=Sun)
		$startDay = 1 << ($starttime->format('N') - 1);

		// Determine if this time period is current (0) or future (1)
		if((($dayOfWeekBitwise & $startDay) == $startDay) AND ($starttime->format('U') <= time()) AND ($endtime->format('U') >= time())) $structure["recurring"][$periodid]["status"] = 0;
		else $structure["recurring"][$periodid]["status"] = 1;
	}

	return $structure["recurring"];
}

// Specific

// Add

function api_restrictions_time_specific_add($campaignid, $starttime, $endtime, $periodid = null) {
	if ($starttime >= $endtime) return api_error_raise("Sorry, the end time must be after the start time");
	if (!is_numeric($campaignid)) return api_error_raise("Sorry, that is not a valid campaign");
	if (!empty($periodid) AND !is_numeric($periodid)) return api_error_raise("Sorry, that is not a valid existing period");

	try {
		$timezone = api_campaigns_gettimezone($campaignid);
		$campaignSettings = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class)
			->buildCampaignSettings($campaignid);

		$settings = [
			'timezone' => $timezone,
			'specific' => [[
				'starttime' => $starttime,
				'endtime' => $endtime,
				'status' => \Models\CampaignSpecificTime::STATUS_CURRENT
			]]
		];

		if (
			!ContainerAccessor::getContainer()
				->get(SpecificTimePickerValidationService::class)
				->isValid($campaignSettings, $settings)
		) {
			$validator = ContainerAccessor::getContainer()->get(CampaignTimingValidationService::class);
			throw new CampaignValidationException($validator->getDisclaimer($campaignSettings));
		}

	} catch (Exception $e) {
		return api_error_raise($e->getMessage());
	}

	$structure = api_restrictions_time_structure($campaignid);

	if (!is_numeric($periodid)) $periodid = api_misc_uniqueid();

	$structure["specific"][$periodid] = array("starttime" => $starttime, "endtime" => $endtime);

	api_campaigns_setting_set($campaignid, "timing", serialize($structure));

	return $periodid;
}

// Remove

function api_restrictions_time_specific_remove($campaignid, $periodid) {

	if (!is_numeric($campaignid)) return false;
	if (!is_numeric($periodid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	if(isset($structure["specific"][$periodid])) {

		unset($structure["specific"][$periodid]);

		api_campaigns_setting_set($campaignid, "timing", serialize($structure));

		return true;

	} return false;
}

// List

// Single

function api_restrictions_time_specific_listsingle($campaignid, $periodid) {

	if (!is_numeric($campaignid)) return false;
	if (!is_numeric($periodid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	return $structure["specific"][$periodid];
}

// All

function api_restrictions_time_specific_listall($campaignid) {

	if (!is_numeric($campaignid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	foreach($structure["specific"] as $periodid => $period) {
		// Determine if this time period is current (0), future (1) or past (-1)
		if(($period["starttime"] <= time()) AND ($period["endtime"] >= time())) $structure["specific"][$periodid]["status"] = 0;
		elseif($period["starttime"] > time()) $structure["specific"][$periodid]["status"] = 1;
		else $structure["specific"][$periodid]["status"] = -1;
	}

	return $structure["specific"];
}

function api_restrictions_time_specific_default($campaignid) {
	$timezone = api_campaigns_gettimezone($campaignid);
	$starttime = new DateTime('18:00:00', $timezone);
	$endtime = new DateTime('20:30:00', $timezone);

	return ['starttime' => $starttime, 'endtime' => $endtime];
}

function api_restrictions_time_recurring_default($campaignid) {
	return ['starttime' => '10:00', 'endtime' => '16:00'];
}

// Check

function api_restrictions_time_check($campaignid) {

	// Returns true if there is an active time period. Return false if there is no active periods

	if (!is_numeric($campaignid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	$time = time();

	$recurringformat = "H:i:s";

	$timezone = api_campaigns_gettimezone($campaignid);

	foreach ($structure["specific"] as $periodid => $value) {

		if(($value["starttime"] <= $time) AND ($value["endtime"] > $time)) return true;

	}

	foreach ($structure["recurring"] as $periodid => $value) {

		// Check if we have a DayOfWeek bitwise set and if not, default to a bitwise of 31 which is Mon-Fri
		$dayOfWeekBitwise = isset($value["daysofweek"]) && is_numeric($value["daysofweek"]) ? $value["daysofweek"] : CampaignUtils::TIMING_RECURRING_WEEKDAYS_BITWISE;

		$starttime = date_create_from_format($recurringformat, $value["starttime"], $timezone);
		$endtime = date_create_from_format($recurringformat, $value["endtime"], $timezone);

		// We need to convert the PHP day of week value (1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat,7=Sun) into a bitmask (1=Mon,2=Tue,4=Wed,8=Thu,16=Fri,32=Sat,64=Sun)
		$startDay = 1 << ($starttime->format('N') - 1);

		if ((($dayOfWeekBitwise & $startDay) == $startDay) AND ($starttime->format('U') <= $time) AND ($endtime->format('U') >= $time)) return true;

	}

	return false;
}

function api_restrictions_time_remaining($campaignid) {

	// Takes a campaign id and returns the number of seconds remaining in the current day

	if (!is_numeric($campaignid)) return false;

	$structure = api_restrictions_time_structure($campaignid);

	$secondsRemaining = 0;

	$timezone = api_campaigns_gettimezone($campaignid);

	$periods = array();

	$now = new DateTime('now');
	$midnight = new DateTime('tomorrow', $timezone);

	foreach($structure as $type => $typeperiods) {

		if($type == "recurring") $format = 'H:i:s';
		else $format = 'U';

		foreach($typeperiods as $periodid => $newperiod){

			$newperiod["starttime"] = DateTime::createFromFormat($format, $newperiod["starttime"], $timezone);
			$newperiod["endtime"] = DateTime::createFromFormat($format, $newperiod["endtime"], $timezone);

			if($type == "recurring") {

				// Check if we have a DayOfWeek bitwise set and if not, default to a bitwise of 31 which is Mon-Fri
				$dayOfWeekBitwise = isset($newperiod["daysofweek"]) && is_numeric($newperiod["daysofweek"]) ? $newperiod["daysofweek"] : CampaignUtils::TIMING_RECURRING_WEEKDAYS_BITWISE;

				$startDay = 1 << ($newperiod["starttime"]->format('N') - 1);

				if (($dayOfWeekBitwise & $startDay) != $startDay) {
					// This recurring period isn't set to run today
					continue;
				}
			}

			if($newperiod["endtime"] <= $now) {
				// If the endtime has already passed, the time period is over
				continue;
			}

			if($newperiod["starttime"] > $midnight) {
				// Time period starts after midnight so ignore it
				continue;
			}

			if($now >= $newperiod["starttime"]) {
				// Time period already commenced. Make the start time "now"
				$newperiod["starttime"] = $now;
			}

			if($newperiod["endtime"] > $midnight) {
				// Time period extends past midnight. Cut it off.
				$newperiod["endtime"] = $midnight;
			}

			$periods = api_restrictions_time_remaining_merge($periods, $newperiod);

		}

	}

	// It is possible that the merge process has created new overlapping periods.
	// Run the merge process until we have no changes

	$inputPeriods = $periods;
	$outputPeriods = array();

	do {

		foreach($inputPeriods as $period) {
			$outputPeriods = api_restrictions_time_remaining_merge($outputPeriods, $period);
		}

		// Check if there were no more changes thus no overlapping periods
		if($inputPeriods == $outputPeriods) break;

	} while(0);

	foreach($outputPeriods as $period) {

		$secondsRemaining += $period["endtime"]->format('U') - $period["starttime"]->format('U');
	}

	return $secondsRemaining;

}

function api_restrictions_time_remaining_merge($periods, $newperiod) {

	// Takes an array of time periods and merges in a new time period

	$processed = false;

	// Iterate over the existing time periods for overlaps or existing periods
	foreach($periods as $key => $existingperiod) {

		if(($newperiod["starttime"] >= $existingperiod["starttime"]) AND ($newperiod["endtime"] <= $existingperiod["endtime"])){
			// The proposed period is already contained within an existing, larger time period so ignore it
			$processed = true;
			break;
		}

		if(($newperiod["starttime"] <= $existingperiod["starttime"]) AND ($newperiod["endtime"] >= $existingperiod["starttime"])) {
			// The proposed time period extends an existing time period at the start - extend the existing period
			$periods[$key]["starttime"] = $newperiod["starttime"];
			$processed = true;
		}

		if(($newperiod["starttime"] <= $existingperiod["endtime"]) AND ($newperiod["endtime"] >= $existingperiod["endtime"])) {
			// The proposed time period extends an existing time period at the end - extend the existing period
			$periods[$key]["endtime"] = $newperiod["endtime"];
			$processed = true;
		}
	}

	// If we haven't found an existing time period to adjust, add a completely new time period
	if(!$processed) $periods[] = array("starttime" => $newperiod["starttime"], "endtime" => $newperiod["endtime"]);

	return $periods;

}

// Get time arrays

function api_restrictions_time_structure($campaignid) {

	$timing = api_campaigns_setting_getsingle($campaignid, "timing");

	if(!empty($timing)) return unserialize($timing);
	else return array("recurring" => array(), "specific" => array());

}

// Do Not Call

// Add

// Single

function api_restrictions_donotcontact_add($type, $destination, $listid = "", $region = "AU") {

	if (!api_restrictions_donotcontact_checkidexists($listid)) return api_error_raise("Sorry, that list doesn't exist.");

	if(!api_restrictions_donotcontact_is_valid_type($type)) return api_error_raise("Sorry, that is not a valid data type");

	if(!is_numeric($listid)) return api_error_raise("Sorry, that is not a valid list id");

	$destination = api_data_format($destination, $type, $region);

	if (empty($destination)) return api_error_raise("Sorry, that is not a valid destination.");

	$dateadded = api_restrictions_donotcontact_check_single($type, $destination, $listid, $region);

	if ($dateadded != FALSE) return api_error_raise("Sorry, that destination is already on that DNC list. Date added: " . date("d F Y H:i:s", strtotime($dateadded)));

	if ($type == "sms") $type = "phone";

	$sql = "INSERT INTO `do_not_contact_data` (`type`, `destination`, `listid`) VALUES (?, ?, ?)";
	$rs = api_db_query_write($sql, array($type, $destination, (integer)$listid));

	return true;
}

/**
 * @param string $type
 * @return boolean
 */
function api_restrictions_donotcontact_is_valid_type($type) {
	return in_array($type, array("phone", "sms", "email"));
}

function api_restrictions_donotcontact_add_upload($type, $file, $name, $listid, $region = "AU"){

	if(!api_restrictions_donotcontact_checkidexists($listid)) return api_error_raise("Sorry, that is not a valid list");

	if(!in_array($type, array("phone", "sms", "email"))) return api_error_raise("Sorry, that is not a valid data type");

	if(preg_match("/\.csv$/i", $name)) $filetype = "csv";
	elseif(preg_match("/\.txt$/i", $name)) $filetype = "csv";
	elseif(preg_match("/\.xls[x]{0,1}$/i", $name)) $filetype = "xls";

	if(!isset($filetype)) return api_error_raise("The uploaded file is not a CSV or 97/2003 XLS file.");

	if($filetype == "csv"){

		$handle = fopen($file, "r");

		$r = 0;

	} else {

		try{

			PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

			$excelReader = PHPExcel_IOFactory::createReaderForFile($file);

			if(!method_exists($excelReader, 'setReadDataOnly')) return api_error_raise("Sorry, that Excel spreadsheet seems to be incompatible.");

			$excelReader->setReadDataOnly(false);

			$excelFile = $excelReader->load($file);
			$excelWorkSheet = $excelFile->getActiveSheet();

			$highestRow = $excelWorkSheet->getHighestRow();
			$highestColumn = $excelWorkSheet->getHighestColumn();
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

		} catch(Exception $e) {

			return api_error_raise("Sorry, that Excel spreadsheet seems to be incompatible.");
		}

		$header = array();

		for ($row = 0; $row < $highestRow; $row++) {
			for ($col = 0; $col < $highestColumnIndex; $col++) {

				$cell = $excelWorkSheet->getCellByColumnAndRow($col, $row+1);
				$style = $excelWorkSheet->getStyle($cell->getCoordinate())->getNumberFormat()->getFormatCode();

                // PHPExcel defaults to US dates so change this to Australian otherwise treat everything else as text
				if($style == "mm-dd-yy") $style = "dd-mm-yy";
				elseif($style == "mm/dd/yy") $style = "dd/mm/yyyy";
				elseif($style == "dd/mm/yy") $style = "dd/mm/yy";
				elseif($style == "dd/mm/yyyy") $style = "dd/mm/yyyy";
				elseif($style == "d-mmm") $style = "d-mmm";
				else $style = "General";

				$exceldata[$row][$col] = PHPExcel_Style_NumberFormat::toFormattedString($cell->getCalculatedValue(), $style);

			}
		}

		unset($excelWorkSheet);
		unset($excelFile);
		unset($excelReader);

	}

	$results = array("total" => 0, "good" => 0, "bad" => 0);
	$k = 0;
	$r = 0;

	do {

		if($filetype == "csv") $data = fgetcsv($handle, 1024768, ",");
		elseif($filetype == "xls"){

			if(isset($exceldata[$k])) $data = $exceldata[$k];
			else break;
			$k++;

		}

		if($data == FALSE) break;

		set_time_limit(10);

		$result = api_restrictions_donotcontact_add($type, $data[0], $listid, $region);

		$results["total"]++;

		if($result == true) $results["good"]++;
		elseif($result == false) $results["bad"]++;


	} while(1);

	if($filetype == "csv")      fclose($handle);
	else                    unset($exceldata);

	if(!unlink($file)) return api_error_raise("Couldn't delete temporary upload file.");

	return $results;


}

function api_restrictions_baddata_add($type, $destination, $timestamp = null, $region = "AU") {

	$destination = api_data_format($destination, $type, $region);

	if (empty($destination)) return api_error_raise("Sorry, that is not a valid DNC destination.");

	if ($timestamp == null) $timestamp = date("Y-m-d H:i:s");

    // Don't DNC boqfinance.com.au addresses
	if(preg_match("/boqfinance.com.au$/i", $destination)) return api_error_raise("Sorry, can't add that address to the bad data list");

	$sql = "INSERT INTO `bad_data` (`timestamp`, `type`, `destination`) VALUES (NOW(), ?, ?) ON DUPLICATE KEY UPDATE `timestamp` = VALUES(`timestamp`)";
	$rs = api_db_query_write($sql, array($type, $destination));

	return true;
}

function api_restrictions_donotcontact_addbytargetid($targetid) {

	if (!is_numeric($targetid)) return api_error_raise("Sorry, that is not a valid target id");

	$targetinfo = api_targets_getinfo($targetid);

	if ($targetinfo !== FALSE) {

		$type = api_campaigns_setting_getsingle($targetinfo["campaignid"], "type");
		$region = api_campaigns_setting_getsingle($targetinfo["campaignid"], "region");
		$destination = $targetinfo["destination"];
		$dnclistid = api_campaigns_setting_getsingle($targetinfo["campaignid"], "donotcontactdestination");

		if ($dnclistid == FALSE) $dnclistid = 6;

		return api_restrictions_donotcontact_add($type, $destination, $dnclistid, $region);

	} else return false;
}

// List

function api_restrictions_donotcontact_addlist($list, $groupownerid) {

	if (api_restrictions_donotcontact_checknameexists($list)) {
	    return api_error_raise("Sorry, a list with the name '" . htmlentities($list) . "' already exists");
    }

	if(!api_groups_checkidexists($groupownerid)) {
	    return api_error_raise("Sorry that group id does not exist");
    }

	$lastid = api_keystore_increment("DONOTCONTACT", 0, "nextid");

	api_restrictions_donotcontact_setting_set($lastid, "name", $list);
    api_restrictions_donotcontact_setting_set($lastid, "groupownerid", $groupownerid);

	return $lastid;
}

// Check if list already created

function api_restrictions_donotcontact_checknameexists($list) {

	if (api_keystore_checkkeyexists("DONOTCONTACT", "name", $list)) return true;
	else return false;
}

// Remove
// Single


function api_restrictions_donotcontact_remove_single($type, $destination, $list = null, $region = "AU") {

	$destination = api_data_format($destination, $type, $region);

	if (empty($destination)) return api_error_raise("Sorry, that is not a valid destination.");

	if (!is_numeric($list)) {

		$sql = "DELETE FROM `do_not_contact_data` WHERE `type` = ? AND `destination` = ?";

		if (api_db_query_write($sql, array($type, $destination)) === FALSE) return api_error_raise("Sorry, couldn't remove the record");
		else return true;

	} else {

		$sql = "DELETE FROM `do_not_contact_data` WHERE `type` = ? AND `destination` = ? AND `listid` = ?";

		if (api_db_query_write($sql, array($type, $destination, $list)) === FALSE) return api_error_raise("Sorry, couldn't remove the record");
		else return true;
	}
}

function api_restrictions_donotcontact_checkidexists($listid) {

	if (!is_numeric($listid)) return false;

	if (api_keystore_get("DONOTCONTACT", $listid, "name") !== FALSE) return true;
	else return false;
}

// List

function api_restrictions_donotcontact_remove_list($list) {

	if (!api_restrictions_donotcontact_checkidexists($list)) return api_error_raise("Sorry, that is not a valid list");

	if ($list == 6) return api_error_raise("Sorry, you can't delete the default list");

	if(api_keystore_checkkeyexists("CAMPAIGNS", "donotcontactdestination", $list)) return api_error_raise("Sorry, cannot delete a Do Not Contact list that is assigned to a campaign");

	api_keystore_purge("DONOTCONTACT", $list);

	$sql = "DELETE FROM `do_not_contact_data` WHERE `listid` = ?";

	if (api_db_query_write($sql, array($list)) === FALSE) return api_error_raise("Sorry, the list removal failed");
	else return true;
}

// Check
// Single


function api_restrictions_donotcontact_check_single($type, $destination, $lists = null, $region = "AU") {

	if ($type == "sms") $type = "phone";

	$destination = api_data_format($destination, $type, $region);

	if (empty($destination)) return api_error_raise("Sorry, that is not a valid destination.");

	if ($lists === null) { // Check all DNC data

		$sql = "SELECT `timestamp` FROM `do_not_contact_data` WHERE `type` = ? AND `destination` = ? LIMIT 1";
		$rs = api_db_query_read($sql, array($type, $destination));

		if($rs->RecordCount() > 0) return $rs->Fields("timestamp");
		else return false;

	} elseif (is_numeric($lists)) {

		$sql = "SELECT `timestamp` FROM `do_not_contact_data` WHERE `type` = ? AND `listid` = ? AND `destination` = ? LIMIT 1";
		$rs = api_db_query_read($sql, array($type, $lists, $destination));

		if ($rs->RecordCount() > 0) return $rs->Fields("timestamp");
		else return false;

	} elseif (is_array($lists)) {

		if (count($lists) == 0) return false;
		$array[] = $type;

		$bindlist = "";

		foreach ($lists as $key) {
			$array[] = $key;
			$bindlist.= "?,";
		}

		$bindlist = substr($bindlist, 0, -1);
		$array[] = $destination;

		$sql = "SELECT `timestamp` FROM `do_not_contact_data` WHERE `type` = ? AND `listid` IN (" . $bindlist . ") AND `destination` = ? LIMIT 1";
		$rs = api_db_query_read($sql, $array);

		if ($rs->RecordCount() > 0) return $rs->Fields("timestamp");
		else return false;
	}
}

function api_restrictions_baddata_check_single($type, $destination, $backcheck = null, $region = "AU") {

	if (($type == "sms") OR ($type == "wash")) $type = "phone";

	$destination = api_data_format($destination, $type, $region);

	if(is_numeric($backcheck)) $days = $backcheck;
	elseif ($type == "phone") $days = BAD_DATA_BACKCHECK_DAYS_PHONE;
	elseif ($type == "email") $days = BAD_DATA_BACKCHECK_DAYS_EMAIL;
	else return api_error_raise("Sorry, that is not a valid data type");

	$sql = "SELECT `timestamp` FROM `bad_data` WHERE `type` = ? AND `destination` = ? AND `timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT 1";
	$rs = api_db_query_read($sql, array($type, $destination, $days));

	if (!empty($rs) AND ($rs->RecordCount() > 0)) return $rs->Fields("timestamp");
	else return false;
}

function api_restrictions_baddata_remove_single($type, $destination, $region = "AU") {

	$destination = api_data_format($destination, $type, $region);

	if (($type == "sms") OR ($type == "wash")) $type = "phone";

	$sql = "DELETE FROM `bad_data` WHERE `type` = ? AND `destination` = ? LIMIT 1";
	$rs = api_db_query_write($sql, array($type, $destination));

	if ($rs === FALSE) return api_error_raise("Sorry, couldn't remove the record");
	else return true;

}

// DNC settings

// Add or update setting

function api_restrictions_donotcontact_setting_set($listid, $setting, $value) {

	return api_keystore_set("DONOTCONTACT", $listid, $setting, $value);
}

// Delete setting

// Single

function api_restrictions_donotcontact_setting_delete_single($listid, $setting) {

	return api_keystore_delete("DONOTCONTACT", $listid, $setting);
}

// Get

// Single

function api_restrictions_donotcontact_setting_getsingle($listid, $setting) {

	return api_keystore_get("DONOTCONTACT", $listid, $setting);
}

// DNC lists

/**
 * @param $userid
 * @param array $listids
 * @return bool
 */
function api_restrictions_donotcontact_user_hasaccess($userid, array $listids) {

    if (empty($listids)) {
        return true;
    }

    $is_admin = api_security_isadmin($userid);
    $user_group_ids = array_keys(api_groups_listall_for_user($userid));

    $short_available_lists = api_restrictions_donotcontact_lists(true, !$is_admin ? $user_group_ids : null);
    if (empty($short_available_lists)) {
        return false;
    }

    if (!array_diff($listids, array_keys($short_available_lists))) {
        return true;
    }
    return false;
}

/**
 * @param integer $groupid
 * @param integer $listid
 * @return boolean
 */
function api_restrictions_donotcontact_list_belongs_to_group($groupid, $listid) {
    $lists = api_restrictions_donotcontact_lists(true, [$groupid]);

    return in_array($listid, array_keys($lists));
}

/**
 * @param $userid
 * @param $campaignid
 * @return bool
 */
function api_restrictions_donotcontact_user_campaign_hasaccess($userid, $campaignid) {

    if (!api_users_checkidexists($userid)) {
        return false;
    }

    if (!api_campaigns_checkidexists($campaignid)) {
        return false;
    }

    $dncdest = api_campaigns_setting_getsingle($campaignid, "donotcontactdestination");
    $dnclist = unserialize(api_campaigns_setting_getsingle($campaignid, "donotcontact"));
    $dnclist[] = $dncdest;

    return api_restrictions_donotcontact_user_hasaccess($userid, $dnclist);
}

// Export DNC list

function api_restrictions_donotcontact_exportlist($lists) {

	if ($lists === null) { // Check all DNC data

		$sql = "SELECT `timestamp`, `type`, `destination` FROM `do_not_contact_data`";
		$rs = api_db_query_read($sql, array());

	} elseif (is_numeric($lists)) {

		$sql = "SELECT `timestamp`, `type`, `destination` FROM `do_not_contact_data` WHERE `listid` = ?";
		$rs = api_db_query_read($sql, array($lists));

	} elseif (is_array($lists) AND (count($lists) > 0)) {

		foreach ($lists as $key){
			if(is_numeric($key)){
				$search[] = $key;
			}
		}

		$sql = "SELECT `timestamp`, `type`, `destination` FROM `do_not_contact_data` WHERE `listid` IN (". implode(',', array_fill(0, count($search), '?')) .")";
		$rs = api_db_query_read($sql, $search);

	}
	return $rs->GetArray();
}

// Display statistics

function api_restrictions_donotcontact_lists($short = 0, $groupownerids = null) {

    // An empty array of group owner ids means no lists
    if ($groupownerids !== null && empty($groupownerids)) {
        return [];
    }

    // Check if groupownerids contains only numeric entries
    if ($groupownerids !== null && !(count($groupownerids) == count(array_filter($groupownerids, 'is_numeric')))) {
        return [];
    }

    $sql = "SELECT key_store.`id`, key_store.`value`, k1.value as groupownerid, k2.value as groupownername from key_store ";
    $sql .= "LEFT JOIN key_store as k1 on (k1.type = 'DONOTCONTACT' and key_store.id = k1.id and k1.item = 'groupownerid') ";
    $sql .= "LEFT JOIN key_store as k2 on (k2.type = 'GROUPS' and k2.id = k1.value and k2.item = 'name') ";
    $sql .= "WHERE key_store.type = 'DONOTCONTACT' and key_store.item = 'name'";
    if (!empty($groupownerids)) {
        $sql .= " AND k1.value IN (".implode(',', array_fill(0, count($groupownerids), '?')).") " ;
    }

    api_db_ping();
    $rs = api_db_query_read($sql, !empty($groupownerids) ? $groupownerids : []);
    if (!($rs || !$rs->RecordCount())) {
        return [];
    }

    if($short) {
        $names = [];
        foreach($rs->GetAssoc() as $id => $value) {
            $names[$id] = $value['value'];
        }
        natcasesort($names);
        return $names;
    }

	$lists = array();

	foreach ($rs->GetAssoc() as $id => $data){
	    $lists[$id] = [
	        "name" => $data['value'],
            "groupownerid" => $data['groupownerid'],
            "groupownername" => $data['groupownername'],
            "count" => 0
        ];
    }

	$listids = array_keys($lists);
	if (!empty($listids)) {
        $sql = "SELECT `listid`, COUNT(*) as `value` FROM `do_not_contact_data` 
            WHERE listid IN (" . implode(',', array_fill(0, count($listids), '?')) . ") 
            GROUP BY `listid`";
        $rs = api_db_query_read($sql, $listids);

        while ($row = $rs->FetchRow()) {
            $lists[$row["listid"]]["count"] = $row["value"];
        }
    }

	return api_misc_natcasesortbykey($lists, "name");

}

// Channel limit

function api_restrictions_channels_checkall($campaignid, $channelMap = null, $settings) {

	// Should return TRUE if the event should be delayed

	// Generic sendrate check for all campaigns
	if (is_numeric($settings["sendrate"]) AND !empty($settings["lastsend"]) AND ((microtime(true) - $settings["lastsend"]) < (3600 / $settings["sendrate"]))) return true;

	if(api_restrictions_stopconditions($campaignid, $settings)) return true;

	if ($settings["type"] == "phone") {

		if($channelMap == null) $channelMap = api_restrictions_channels_channelmap();

		if (api_restrictions_channels_campaign($channelMap, $campaignid, $settings["maxchannels"])) return true; // Check campaign channels
		elseif (($settings["voicesupplier"] != 0) AND api_restrictions_caps_provider($settings["voicesupplier"])) return true; // Check provider call-attempts-per-second
		elseif (($settings["voicesupplier"] != 0) AND api_restrictions_channels_provider($channelMap, $settings["voicesupplier"])) return true; // Check provider channel limit

	} elseif($settings["type"] == "wash") {

		if($channelMap == null) $channelMap = api_restrictions_channels_channelmap();

		if(isset($channelMap["campaigns"][$campaignid]) AND ($channelMap["campaigns"][$campaignid] >= $settings["maxchannels"])) return true;

	}

	return false;
}

// Generate channel map

function api_restrictions_channels_channelmap() {

	$sql = "SELECT `campaignid`, COUNT(`targetid`) as `count` FROM `targets` WHERE `status` = ? GROUP BY `campaignid`";
	$rs = api_db_query_read($sql, array("INPROGRESS"));

	$channelMap["campaigns"] = $rs->GetAssoc();

	$channelMap["campaigns"]["total"] = 0;

	foreach ($channelMap["campaigns"] as $key => $value) $channelMap["campaigns"]["total"]+= $value;

	return $channelMap;
}

// Campaign limit

function api_restrictions_channels_campaign($channelMap, $campaignid, $maxchannels) {

	if (!is_numeric($campaignid)) return false;

	if(!empty($maxchannels) AND !is_numeric($maxchannels)) return true;
	elseif(empty($maxchannels)) return false;
	elseif (isset($channelMap["campaigns"][$campaignid]) AND ($channelMap["campaigns"][$campaignid] >= $maxchannels)) return true;
	else return false;
}

// Provider channel limit

function api_restrictions_channels_provider($channelMap, $providerid) {

	if (!is_numeric($providerid)) return false;

	$maxchannels = api_voice_supplier_setting_getsingle($providerid, "maxchannels");

	if(!is_numeric($maxchannels)) return false;

	$sql = "SELECT COUNT(`providerid`) as `count` FROM `provider_map` WHERE `providerid` = ?";
	$rs = api_db_query_read($sql, array($providerid));

	if($rs AND ($rs->RecordCount() > 0)) $count = $rs->Fields("count");
	else $count = 0;

	if ($count >= $maxchannels) return true;
	return false;
}

// Provider CAPS limit

function api_restrictions_caps_provider($providerid, $provider = false) {

	if (!is_numeric($providerid)) return false;

	if(!is_array($provider)) $provider = api_voice_supplier_setting_get_multi_byitem($providerid, array("lastcall", "callspersecond"));

	if(empty($provider["callspersecond"]) OR !is_numeric($provider["callspersecond"])) return false;

	if(empty($provider["lastcall"])) $provider["lastcall"] = 0;

	$period = microtime(true) - $provider["lastcall"];
	$interval = 1 / $provider["callspersecond"];

	if ($period < $interval) return true;
	else return false;
}

function api_restrictions_caps_sms_provider($providerid, $provider = false) {

	if (!is_numeric($providerid)) return false;

	if(!is_array($provider)) $provider = api_sms_supplier_setting_get_multi_byitem($providerid, array("lastsms", "smspersecond"));

	if(empty($provider["smspersecond"]) OR !is_numeric($provider["smspersecond"])) return false;

	if(empty($provider["lastsms"])) $provider["lastsms"] = 0;

	$period = microtime(true) - $provider["lastsms"];
	$interval = 1 / $provider["smspersecond"];

	if ($period < $interval) return true;
	else return false;
}

function api_restrictions_caps_hlr_provider($providerid, $provider = false) {

	if (!is_numeric($providerid)) return false;

	if(!is_array($provider)) $provider = api_hlr_supplier_setting_get_multi_byitem($providerid, array("lasthlr", "hlrpersecond"));

	if(empty($provider["hlrpersecond"]) OR !is_numeric($provider["hlrpersecond"])) return false;

	if(empty($provider["lasthlr"])) $provider["lasthlr"] = 0;

	$period = microtime(true) - $provider["lasthlr"];
	$interval = 1 / $provider["hlrpersecond"];

	if ($period < $interval) return true;
	else return false;
}

// Provider limit

function api_restrictions_channels_provider_madecall($providerid, $targetid = null) {

	if (!is_numeric($providerid)) return false;

	if(is_numeric($targetid)){

		$sql = "INSERT INTO `provider_map` (`targetid`, `providerid`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `providerid` = ?";
		$rs = api_db_query_write($sql, array($targetid, $providerid, $providerid));

	}

	return true;
}

function api_restrictions_channels_provider_sentsms($campaignid) {

	if (!is_numeric($campaignid)) return false;

	api_campaigns_setting_getsingle($campaignid, "lastsend");

	return true;
}

function api_restrictions_channels_provider_sentemail($campaignid) {

	if (!is_numeric($campaignid)) return false;

	api_campaigns_setting_getsingle($campaignid, "lastsend");

	return true;
}

// Check stop conditions

function api_restrictions_stopconditions($campaignid, $settings) {

	if (!is_numeric($campaignid)) return false;

	if(empty($settings["stopflag"]) OR !isset($settings["stopflagcount"]) OR !is_numeric($settings["stopflagcount"])) return false;

	$sql = "SELECT COUNT(*) as `count` FROM `response_data` WHERE `campaignid` = ? AND `action` = ?";
	$rs = api_db_query_read($sql, array($campaignid, $settings["stopflag"]));

	if ($rs->Fields("count") >= $settings["stopflagcount"]) {

		api_campaigns_setting_set($campaignid, "status", "DISABLED");

		return true;

	} else return false;

}
