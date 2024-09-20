<?php

use Models\CampaignType;
use Models\Entities\QueueItem;
use Services\ActivityLogger;
use Services\Campaign\Archiver\ArchiverEnum;
use Services\Campaign\Hooks\CampaignHookBuilder;
use Services\Container\ContainerAccessor;
use Services\Exceptions\Campaign\NoDataException;
use Services\Exceptions\Validators\CampaignTargetDataValidatorFactoryException;
use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\PCI\PCIRecorder;
use Services\PCI\PCIValidator;
use Services\Queue\QueueManager;
use Services\Queue\QueueProcessTypeEnum;
use Services\Utils\ActivityLoggerActions;
use Services\Validators\Factory\CampaignTargetDataValidatorFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;

// Add single target

function api_targets_add_single($campaignid, $destination, $targetkey = null, $priority = null, $mergedata = null, $nextattempt = null, $params = array()){
	if(!is_numeric($campaignid)) return api_error_raise("That is not a valid campaign id");
	if(empty($destination)) return api_error_raise("That is not a valid destination");
	if(!is_numeric($priority) AND ($priority != NULL)) return false;

	if(!empty($params["settings"])) $destination = api_targets_dataformat($destination, $campaignid, $params["settings"]);
	else $destination = api_targets_dataformat($destination, $campaignid);

	if(empty($destination)) return api_error_raise("That is not a valid destination");
    $isTargetKeyDestination = false;
	if($priority == NULL) $priority = 1;
	if($targetkey == NULL) {
		$targetkey = $destination;
		$isTargetKeyDestination = true;
	}

	if($nextattempt != null) {

		if($timestamp = strtotime($nextattempt)) $nextattempt = date("Y-m-d H:i:s", $timestamp);
		else return api_error_raise("Couldn't process the rt-sendat value '" . $nextattempt . "'");

		$status = "REATTEMPT";

	} else $status = "READY";

	$originaltargetkey = $targetkey;
	$pci_validator = new PCIValidator();
	// set campaign whitelist from tags
	$whitelist = api_campaigns_tags_get($campaignid, PCIValidator::TAG_NAME_WHITELIST);
	if ($whitelist) {
		$pci_validator->setPANWhitelist(
			(is_array($whitelist)? $whitelist : explode(',', $whitelist))
		);
	}

	if ($isTargetKeyDestination) {
		$validator = api_targets_build_target_data_validator($campaignid, $targetkey, $mergedata ? : []);
		if (!is_null($validator)) {
			$targetkey = $validator->getSanitizedTargetKey();
		}
	}

	$validData = api_targets_validate_data(
	    $campaignid,
        $targetkey,
        $mergedata ? : [],
        (isset($params["throwexception"]) && $params["throwexception"])
    );

	if (is_null($validData)) {
		return false;
	}

    $targetkey = $validData[0];
    $mergedata = $validData[1];

	$pci_recorder = PCIRecorder::getInstance();

	// check for PCI data in target key
	if ($pci_validator->isPANData($targetkey)) {
		// push to error stack
		api_error_raise("That targetkey matches PAN format", 'warning');
		// write to syslog
		api_error_audit('PCI_DATA', "PAN format data detected in targetkey");
		$status = "ABANDONED";
		// Record targetkey only if PCIRecorder has been started using start()
		$pci_recorder->addTargetKey($campaignid, $targetkey);
		$targetkey = $pci_validator->maskPANData($targetkey, true);
	}

	if(is_array($mergedata) AND count($mergedata) > 0) {
		// check for PCI data in merge data
		foreach($mergedata as $element => $value) {
			if ($match = $pci_validator->matchAllPANData($value)) {
				// push to error stack
				api_error_raise("Merge data {$element} matches PAN format", 'warning');
				// write to syslog
				api_error_audit('PCI_DATA', "PAN format data detected in merge data '{$element}'");
				$status = "ABANDONED";
				// Record merge data only if PCIRecorder has been started using start()
				$pci_recorder->addMergeData($campaignid, $originaltargetkey, $element, $value);
				foreach ($match as $pci) {
					$value = str_replace(
						$pci,
						$pci_validator->maskPANData($pci),
						$value
					);
				}
				$mergedata[$element] = $value;
			}
		}

		if (!api_targets_add_extradata_multiple($campaignid, $targetkey, $mergedata, false)) {
			return api_error_raise("Sorry, that is not valid merge data");
		}
	}
	// making sure PCi data not used again later
	unset($originaltargetkey);

    // If we have a nextattempt time, update the status as well as the nextattempt field
	if($nextattempt != null){

		$sql = "INSERT INTO `targets` (`campaignid`, `targetkey`, `priority`, `destination`, `status`, `nextattempt`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `targetid` = LAST_INSERT_ID(`targetid`), `destination` = VALUES(`destination`), `status` = VALUES(`status`), `nextattempt` = VALUES(`nextattempt`)";
		$rs = api_db_query_write($sql, array($campaignid, $targetkey, $priority, $destination, $status, $nextattempt));

	} else {

		$sql = "INSERT INTO `targets` (`campaignid`, `targetkey`, `priority`, `destination`, `status`, `nextattempt`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `targetid` = LAST_INSERT_ID(`targetid`), `destination` = VALUES(`destination`)";
		$rs = api_db_query_write($sql, array($campaignid, $targetkey, $priority, $destination, $status, $nextattempt));

	}

	if(!$rs) {

		if(isset($params["throwexception"]) AND $params["throwexception"]) {
			throw new Exception('Add target insert failed');
		} else {
			return false;
		}
	}

	if(!empty($params["returntrueonly"]) AND $params["returntrueonly"]) $targetid = true;
	else $targetid = api_db_lastid();

	if($targetid !== false) return $targetid;
    else return api_error_raise("Something went wrong"); //false;

}

/**
 * @param integer $campaignid
 * @param mixed   $targetkey
 * @param array   $mergedata
 * @param boolean $throwException
 * @return array|null
 * @throws Exception
 */
function api_targets_validate_data(
	$campaignid,
	$targetkey,
	array $mergedata = [],
	$throwException = false
) {
	$validator = api_targets_build_target_data_validator($campaignid, $targetkey, $mergedata);

	if (is_null($validator)) {
		return [$targetkey, $mergedata];
	}

	try {
		$validator->isValid();
	} catch (ValidatorRuntimeException $exception) {
		if ($throwException) {
			throw $exception;
		}
		api_error_raise($exception->getMessage() . ' [Campaignid:' . $campaignid . ']');
		return null;
	}

	return [
		$validator->getSanitizedTargetKey(),
		$validator->getSanitizedMergeData()
	];
}

/**
 * @param integer $campaignid
 * @param mixed $targetkey
 * @param array $mergedata
 * @return null|\Services\Validators\Interfaces\CampaignTargetDataValidatorInterface
 */
function api_targets_build_target_data_validator($campaignid, $targetkey, array $mergedata = []) {
	/** @var CampaignTargetDataValidatorFactory $validatorFactory */
	$validatorFactory = ContainerAccessor::getContainer()->get(CampaignTargetDataValidatorFactory::class);
	$type = api_campaigns_setting_getsingle($campaignid, CAMPAIGN_SETTING_TYPE);

	try {
		$targetDataValidator = $validatorFactory->create(CampaignType::byValue($type));
	} catch (CampaignTargetDataValidatorFactoryException $exception) {
		return null;
	}

	return $targetDataValidator
		->setTargetKey($targetkey)
		->setMergeData($mergedata);
}

// Add single merge data

function api_targets_add_extradata_single($campaignid, $targetkey, $element, $value) { return api_targets_add_extradata_multiple($campaignid, $targetkey, array($element => $value)); }

// Add merge data by array

function api_targets_add_extradata_multiple($campaignid, $targetkey, $elements, $dataCheck = true, $trimspace = false){

	if(!is_numeric($campaignid)) return api_error_raise("Sorry, that is not a valid campaign id");
	if(!is_array($elements)) return api_error_raise("Sorry, elements must be an array");

	if(count($elements) == 0) return true;

	$validData = api_targets_validate_data($campaignid, $targetkey, $elements);

	if (is_null($validData)) {
		return false;
	}

	$targetkey = $validData[0];
	$elements = $validData[1];

	$array = array();

	if ($dataCheck) {
		$updatestatus = false;
		$originaltargetkey = $targetkey;
		$pci_validator = new PCIValidator();
		$pci_recorder = PCIRecorder::getInstance();

		// check for PCI data in target key
		if ($pci_validator->isPANData($targetkey)) {
			// push to error stack
			api_error_raise("That targetkey matches PAN format", 'warning');
			// write to syslog
			api_error_audit('PCI_DATA', "PAN format data detected in targetkey");
			$pci_recorder->addTargetKey($campaignid, $targetkey);
			$targetkey = $pci_validator->maskPANData($targetkey, true);
		}
		foreach($elements as $element => $value) {
			if ($match = $pci_validator->matchAllPANData($value)) {
				// update target status to ABANDONED
				$updatestatus = true;
				// push to error stack
				api_error_raise("Merge data {$element} matches PAN format", 'warning');
				// write to syslog
				api_error_audit('PCI_DATA', "PAN format data detected in merge data '{$element}'");
				$pci_recorder->addMergeData(
					$campaignid,
					$originaltargetkey,
					$element,
					$elements[$element]
				);
				foreach ($match as $pci) {
					$value = str_replace(
						$pci,
						$pci_validator->maskPANData($pci),
						$value
					);
				}
				$elements[$element] = $value;
			}
		}
		unset($originaltargetkey);

		if ($updatestatus) {
			$sql = "UPDATE `targets` SET `status` = ? WHERE `campaignid` = ? AND `targetkey` = ? LIMIT 1;";
			$rs = api_db_query_write($sql, ["ABANDONED", $campaignid, $targetkey]);
		}
	}

	$sql = "INSERT INTO `merge_data` (`campaignid`, `targetkey`, `element`, `value`) VALUES ";

	foreach($elements as $element => $value) {

		// Detect if the value contains windows-1252 characters and if so, convert it to UTF-8 as expected
		if(!preg_match('/^\\X*$/u', $value)) {
			$value = iconv("CP1252", "UTF-8//TRANSLIT", $value);
		}

		// Convert any non-breaking spaces to regular spaces
		$value = str_replace(array("\xc2\xa0", "\xa0"), ' ', $value);

		// Trim off any tabs, new lines, carriage returns or vertical tabs. We should leave ordinary spaces so overide the default character mask for trim.
		$trimcharlist = "\t\n\r\0\x0B";

		// Trim whitespace if required. The reason why whitespace is omitted in trim charlist in previous commit
		// is not known. But we have clients requesting to trim whitespace and so this is done if forced
		// so that it does not break anything existing.
		if ($trimspace) {
			$trimcharlist = ' ' . $trimcharlist;
		}

		$value = trim($value, $trimcharlist);

		$sql .= "(?, ?, ?, FROM_BASE64(?)),";
		array_push($array, $campaignid, $targetkey, $element, base64_encode(mb_convert_encoding($value, "UTF-8", "UTF-8")));
	}

	$sql = substr($sql, 0, -1) . " ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";

	$rs = api_db_query_write($sql, $array);

	if($rs !== FALSE) return true;
	else return false;

}

// Returns target info by target id

function api_targets_getinfo($targetid){

	if(!is_numeric($targetid)) return api_error_raise("Sorry, that is not a valid target id");

	$sql = "SELECT * FROM `targets` WHERE `targetid` = ?";
	$rs = api_db_query_read($sql, array($targetid));

	if(empty($rs)) return false;
	elseif($rs->RecordCount() == 1) return $rs->GetRowAssoc(false);
	else return false;

}

// Searches all campaigns and returns an array of results

function api_targets_search($destination){

	if(empty($destination)) return api_error_raise("Sorry, that is not a valid destination");

	$results = array();

	if(preg_match("/@/", $destination)) {

	        $type = "email";

	        $destination = api_data_format($destination, "email");

	        if(!$destination) return api_error_raise("Sorry, that is not a valid destination");
	        $params = [$destination];

	} else {

		$type = "phone";
		$formatteddestination = api_data_numberformat($destination);

		if(!$formatteddestination) return api_error_raise("Sorry, that is not a valid destination");

		$params = [$destination];
		// @see REACHTEL-143: wash campaigns targets are not ffn formatted in DB
		if ($destination !== $formatteddestination["fnn"]) {
			// remove non-digits and duplicates
			$params = array_unique(
				preg_replace(
					'/[^\d]+/',
					'',
					[
						$formatteddestination["fnn"],
						$formatteddestination["destination"],
						$destination
					]
				)
			);
		}
		$destination = $formatteddestination["fnn"];
	}

	$sql = "SELECT targetid,campaignid,targetkey,priority,";
	$sql .= "status,destination,nextattempt,reattempts,ringouts,errors,'live target' as targetstatus";
	$sql .= " FROM targets WHERE destination";

	if (count($params) > 1) {
		$sql .= sprintf(' IN(%s)', implode(',', array_fill(0, count($params), '?')));
	} else {
		$sql .= " = ?";
	}

	$sql .= " UNION ALL SELECT targetid,campaignid,targetkey,priority,";
	$sql .= "status,destination,nextattempt,reattempts,ringouts,errors,'archived target' as targetstatus";
	$sql .= " FROM targets_archive WHERE destination";
	if (count($params) > 1) {
		$sql .= sprintf(' IN(%s)', implode(',', array_fill(0, count($params), '?')));
		$params = array_merge($params, $params);
	} else {
		$sql .= " = ?";
		$params[] = $params[0];
	}

	$rs = api_db_query_read($sql, $params);

	$groups = api_security_groupaccess($_SESSION['userid']);

	while(!$rs->EOF){

		if($groups["isadmin"] OR in_array(api_campaigns_setting_getsingle($rs->Fields("campaignid"), "groupowner"), $groups["groups"])){

			$created = api_campaigns_setting_getsingle($rs->Fields("campaignid"), "created");

			$result = array("source" => "campaign",
				"id" => $rs->Fields("campaignid"),
				"name" => api_campaigns_setting_getsingle($rs->Fields("campaignid"), "name"),
				"targetstatus" => $rs->Fields("targetstatus"),
				"type" => api_campaigns_setting_getsingle($rs->Fields("campaignid"), "type"));

			$sql = "SELECT * FROM `call_results` WHERE `targetid` = ?";
			$rs2 = api_db_query_read($sql, array($rs->Fields("targetid")));

			while(!$rs2->EOF){

				$result["events"][$rs2->Fields("eventid")][$rs2->Fields("timestamp")][] = array("value" => $rs2->Fields("value"));

				ksort($result["events"][$rs2->Fields("eventid")]);

				$rs2->MoveNext();
			}

			$sql = "SELECT * FROM `response_data` WHERE `targetid` = ?";
			$rs2 = api_db_query_read($sql, array($rs->Fields("targetid")));

			while(!$rs2->EOF){

				$result["events"][$rs2->Fields("eventid")][$rs2->Fields("timestamp")][] = array("action" => $rs2->Fields("action"), "value" => $rs2->Fields("value"));
				ksort($result["events"][$rs2->Fields("eventid")]);
				$rs2->MoveNext();
			}

		}

		$results[$created][] = $result;

		$rs->MoveNext();

	}

	if($type == "email") return $results;

	$destination = api_data_numberformat($destination);

	$sql = "SELECT `id`, `userid`, UNIX_TIMESTAMP(`timestamp`) as `created`, `timestamp`, `status`, `reason` FROM `wash_out` WHERE `destination` = ?";
	$rs = api_db_query_read($sql, array($formatteddestination["destination"]));

	while(!$rs->EOF){

		if($groups["isadmin"] OR in_array($rs->Fields("userid"), $groups["groups"])){

			$results[$rs->Fields("created")][] = array("source" => "restwash",
				"id" => api_users_setting_getsingle($rs->Fields("userid"), "username"),
				"name" => api_users_setting_getsingle($rs->Fields("userid"), "username"),
				"events" => array($rs->Fields("id") => array($rs->Fields("timestamp") => array(array("action" => "STATUS", "value" => $rs->Fields("status") . " / " . $rs->Fields("reason"))))));

		} // else they don't have access to this users results

		$rs->MoveNext();
	}

	$sql = "SELECT `id`, `userid`, UNIX_TIMESTAMP(`timestamp`) as `created`, `timestamp`, `from`, `message` FROM `sms_out` WHERE `destination` = ?";
	$rs = api_db_query_read($sql, array($formatteddestination["destination"]));

	while(!$rs->EOF){

		if($groups["isadmin"] OR in_array($rs->Fields("userid"), $groups["groups"])){

			$results[$rs->Fields("created")][] = array("source" => "restsms",
				"id" => api_users_setting_getsingle($rs->Fields("userid"), "username"),
				"name" => api_users_setting_getsingle($rs->Fields("userid"), "username"),
				"events" => array($rs->Fields("id") => array($rs->Fields("timestamp") => array(array("action" => $rs->Fields("from"), "value" => $rs->Fields("message"))))));

		} // else they don't have access to this users results

		$rs->MoveNext();
	}

	$sql = "SELECT `smsid`, UNIX_TIMESTAMP(`timestamp`) as `created`, `timestamp`, `sms_account`, `contents` FROM `sms_received` WHERE `from` = ?";
	$rs = api_db_query_read($sql, array($formatteddestination["destination"]));

	while(!$rs->EOF){

		$results[$rs->Fields("created")][] = array("source" => "smsreceived",
			"id" => $rs->Fields("sms_account"),
			"name" => api_sms_dids_setting_getsingle($rs->Fields("sms_account"), "name"),
			"events" => array($rs->Fields("smsid") => array($rs->Fields("timestamp") => array(array("action" => "content", "value" => $rs->Fields("contents"))))));

		$rs->MoveNext();
	}

	$sql = "SELECT `sms_sent`.`eventid` as `eventid`, `sms_api_mapping`.`userid` as `userid`, UNIX_TIMESTAMP(`sms_api_mapping`.`timestamp`) as `created`, `sms_api_mapping`.`timestamp` as `timestamp`, `sms_sent`.`sms_account` as `sms_account`, `sms_sent`.`contents` as `contents` FROM `sms_api_mapping`, `sms_sent` WHERE `sms_api_mapping`.`rid` = `sms_sent`.`eventid` AND `sms_sent`.`to` = ?";
	$rs = api_db_query_read($sql, array($formatteddestination["destination"]));

	while(!$rs->EOF){

		$results[$rs->Fields("created")][] = array("source" => "smssent",
			"id" => api_users_setting_getsingle($rs->Fields("userid"), "username"),
			"name" => api_users_setting_getsingle($rs->Fields("userid"), "username"),
			"events" => array($rs->Fields("eventid") => array($rs->Fields("timestamp") => array(array("action" => "content", "value" => $rs->Fields("contents"))))));

		$rs->MoveNext();
	}

	ksort($results);

	return $results;

}

function api_targets_findrecent($destination, $type){

	$sql = "SELECT `targetid`, `campaignid` FROM `targets` WHERE `destination` = ? ORDER BY `targetid` DESC";
	$rs = api_db_query_read($sql, array($destination));

	while($array = $rs->FetchRow()){

		if(api_campaigns_setting_getsingle($array["campaignid"], "type") == $type) return $array["targetid"];

	}

	return false;

}

/**
 * @param $campaign
 * @return bool | integer
 */
function api_targets_count_campaign_total($campaign) {
	if (!is_numeric($campaign)) {
		return api_error_raise("Campaign id must be numeric: {$campaign}");
	}

	if (!api_campaigns_checkidexists($campaign)) {
		return api_error_raise("Campaign does not exist: {$campaign}");
	}

	$sql = "SELECT count(*) as targets FROM `targets` where `campaignid` = ?";
	$rs = api_db_query_read($sql, [$campaign]);
	if ($rs) {
		$row = $rs->GetRowAssoc();
		return $row['targets'];
	}
	return false;
}

/**
 * @param $campaign
 * @return bool | integer
 */
function api_targets_archive_count_campaign_total($campaign) {
    if (!is_numeric($campaign)) {
        return api_error_raise("Campaign id must be numeric: {$campaign}");
    }

    if (!api_campaigns_checkidexists($campaign)) {
        return api_error_raise("Campaign does not exist: {$campaign}");
    }

    $sql = "SELECT count(*) as targets FROM `targets_archive` where `campaignid` = ?";
    $rs = api_db_query_read($sql, [$campaign]);
    if ($rs) {
        $row = $rs->GetRowAssoc();
        return $row['targets'];
    }
    return false;
}

/**
 *
 * Finds the last sms sent to the given destination and sms_account
 *
 * @param $destination
 * @param $sms_account
 * @return bool
 */
function api_targets_find_last_sent_event($destination, $sms_account){

	if (!is_numeric($sms_account)) {
		return api_error_raise("Sorry, that is not a valid SMS Account");
	}

	if (empty($destination)) {
		return api_error_raise("Sorry, that is not a valid destination");
	}

	$destination = api_data_numberformat($destination);
	if (!is_array($destination)) {
		return api_error_raise("NUMBER_FAILURE: $destination is not a valid phone number");
	}

	$sql = "SELECT sms_sent.to, 
					sms_sent.eventid,
					sms_sent.sms_account, 
					call_results.targetid, 
					call_results.campaignid, 
					call_results.timestamp       				
					FROM sms_sent 
						JOIN call_results ON (call_results.eventid = sms_sent.eventid AND call_results.value = 'SENT')
					WHERE ((`to` = ?) OR (`to` = ?)) 
					AND `sms_account` = ?					            		
			UNION 
				SELECT sms_sent.to, 
					sms_sent.eventid,
					sms_sent.sms_account,  
					null as targetid,
					null as campaignid,
					sms_api_mapping.timestamp       				
					FROM sms_sent 
						JOIN sms_api_mapping ON (sms_sent.eventid = sms_api_mapping.rid)					    
					WHERE ((`to` = ?) OR (`to` = ?))
					AND `sms_account` = ?
		ORDER BY `eventid` DESC LIMIT 1";

	$rs = api_db_query_read(
		$sql, array(
			$destination["fnn"],
			$destination["destination"],
			$sms_account,
			$destination["fnn"],
			$destination["destination"],
			$sms_account
		)
	);

	return $rs->FetchRow();
}

function api_targets_findrecentsms($destination, $smsdid){

	if(!is_numeric($smsdid)) return api_error_raise("Sorry, that is not a valid SMS DID");

	if(empty($destination)) return api_error_raise("Sorry, that is not a valid destination");

	$destination = api_data_numberformat($destination);

	if(!is_array($destination)) return false;

	$sql = "SELECT `eventid` FROM `sms_sent` WHERE ((`to` = ?) OR (`to` = ?)) AND `sms_account` = ? ORDER BY `eventid` DESC";
	$rs = api_db_query_read($sql, array($destination["fnn"], $destination["destination"], $smsdid));

	while($array = $rs->FetchRow()){

		$sql = "SELECT `targetid` FROM `call_results` WHERE `eventid` = ? AND `value` = ?";
		$rs2 = api_db_query_read($sql, array($array["eventid"], "SENT"));

		if($rs2->RecordCount() > 0) return $rs2->Fields("targetid");

	}

	return false;

}


// Delete

  // Single

    // By targetid

function api_targets_delete_single_bytargetid($targetid){

	if(!is_numeric($targetid)) return false;

	$sql = "DELETE FROM `targets` WHERE `targetid` = ?";
	$rs = api_db_query_write($sql, array($targetid));

	if($rs !== FALSE) return true;
	else return false;

}

    // By destination

function api_targets_delete_single_bydestination($campaignid, $destination){

	if(!is_numeric($campaignid)) return false;

	$sql = "SELECT `targetid` FROM `targets` WHERE `campaignid` = ? AND `destination` = ? ";
	$rs = api_db_query_read($sql, array($campaignid, $destination));

	if(($rs !== FALSE) AND ($rs->RecordCount() > 0)) return api_targets_delete_single_bytargetid($rs->Fields("targetid"));
	else return false;

}


  // All targets

function api_targets_delete_all($campaignid){

	if(!api_campaigns_checkidexists($campaignid)) return false;

	$sql = "DELETE FROM `targets` WHERE `campaignid` = ?";
	$rs = api_db_query_write($sql, array($campaignid));

	return true;

}

// All targets

function api_targets_archive_delete_all($campaignid, ArchiverEnum $type){

    if(!api_campaigns_checkidexists($campaignid)) return false;

    $sql = "DELETE FROM `targets_archive` WHERE `campaignid` = ? and archiver = ?";
    $rs = api_db_query_write($sql, array($campaignid, $type->getValue()));

    return true;

}

/**
 * @param integer $campaignid
 * @param bool $override_campaignid_check
 * @param ArchiverEnum $archiver
 * @param null $limit
 * @param null $offset
 * @return bool
 */
function api_targets_archive($campaignid, $override_campaignid_check = false, ArchiverEnum $archiver, $limit = null, $offset = null) {
	if (!$override_campaignid_check && !api_campaigns_checkidexists($campaignid)) {
		return false;
	}

	$sql = 'INSERT INTO `targets_archive`
			(`targetid`, `campaignid`, `targetkey`, `priority`, `status`, `destination`, `nextattempt`,
			`reattempts`, `ringouts`, `errors`, `archiver`)
			SELECT `targetid`, `campaignid`, `targetkey`, `priority`, `status`, `destination`, `nextattempt`,
			`reattempts`, `ringouts`, `errors`, ? FROM `targets` WHERE `campaignid`=? ORDER by `targetid`';
	$params = [$archiver->getValue(), $campaignid];
	if($limit) {
		$sql .= " LIMIT ? ";
		$params[] = $limit;
		if($offset) {
			$sql .= " OFFSET ? ";
			$params[] = $offset;
		}
	}
	return api_db_query_write($sql, $params) !== false;
}

/**
 * Reverse archiver query
 * @param $campaignid
 * @param bool $override_campaignid_check
 * @param ArchiverEnum $archiver
 * @param null $limit
 * @param null $offset
 * @return bool
 */
function api_targets_dearchive($campaignid, $override_campaignid_check = false, ArchiverEnum $archiver, $limit = null, $offset = null) {
    if (!$override_campaignid_check && !api_campaigns_checkidexists($campaignid)) {
        return false;
    }

    $sql = 'INSERT INTO `targets`
			(`targetid`, `campaignid`, `targetkey`, `priority`, `status`, `destination`, `nextattempt`,
			`reattempts`, `ringouts`, `errors`)
			SELECT `targetid`, `campaignid`, `targetkey`, `priority`, `status`, `destination`, `nextattempt`,
			`reattempts`, `ringouts`, `errors` FROM `targets_archive` WHERE `campaignid`= ?
            AND archiver = ?
            ORDER by `targetid`';
    $params = [$campaignid, $archiver->getValue()];
    if($limit) {
        $sql .= " LIMIT ? ";
        $params[] = $limit;
        if($offset) {
            $sql .= " OFFSET ? ";
            $params[] = $offset;
        }
    }
    return api_db_query_write($sql, $params) !== false;
}

function api_targets_get_archive($campaign_id, $target_id = null) {
    if(!api_campaigns_checkidexists($campaign_id)) return false;

    $sql = "SELECT * FROM `targets_archive` WHERE `campaignid` = ?";

    $params = [$campaign_id];
    if ($target_id){
        $sql .= " target_id = ?";
        $params[] = $target_id;
    }

    $rs = api_db_query_read($sql, $params);
    return $rs->GetRows();
}

// Update status

function api_targets_updatestatus($targetid, $status, $nextattempt = null, $error = 0){

	if(!is_numeric($targetid)) return false;

	if(is_numeric($nextattempt)) $nextattempt = date("Y-m-d H:i:s", $nextattempt);

	if($error == 1) {

		$sql = "SELECT `errors` FROM `targets` WHERE `targetid` = ?";
		$rs = api_db_query_write($sql, array($targetid));

		if(($rs->RecordCount() > 0) AND ($rs->Fields("errors") >= 4)){

			$sql = "UPDATE `targets` SET `status` = ?, `nextattempt` = ?, `errors` = `errors` + 1 WHERE `targetid` = ?";
			$rs = api_db_query_write($sql, array("ABANDONED", NULL, $targetid));

		} else {

			$sql = "UPDATE `targets` SET `status` = ?, `nextattempt` = ?, `errors` = `errors` + 1 WHERE `targetid` = ?";
			$rs = api_db_query_write($sql, array($status, $nextattempt, $targetid));

		}

	} else {

		$sql = "UPDATE `targets` SET `status` = ?, `nextattempt` = ? WHERE `targetid` = ?";
		$rs = api_db_query_write($sql, array($status, $nextattempt, $targetid));
	}

	if($rs !== FALSE) return true;
	else return false;
}

function api_targets_updatestatus_completebytargetkey($campaignid, $targetkey){

	if(!api_campaigns_checkidexists($campaignid)) return false;

	$sql = "UPDATE `targets` SET `status` = ?, `nextattempt` = ? WHERE `campaignid` = ? AND `targetkey` = ?";
	$rs = api_db_query_write($sql, array("COMPLETE", null, $campaignid, $targetkey));

	return true;

}

function api_targets_resetready($campaignid){

	if(!api_campaigns_checkidexists($campaignid)) return false;

	api_targets_archive($campaignid, false, ArchiverEnum::MANUAL());

	$sql = "UPDATE `targets` SET `status` = ?, `nextattempt` = null WHERE `campaignid` = ?";
	$rs = api_db_query_write($sql, array("READY", $campaignid));

	ActivityLogger::getInstance()->addLog(
		KEYSTORE_TYPE_CAMPAIGNS,
		ActivityLoggerActions::ACTION_CAMPAIGN_RESET_READY,
		'Reset targets for campaign ' . $campaignid,
		$campaignid
	);

	return true;

}

/**
 * Fetch a target by campaign id and target key
 *
 * @param $campaign_id
 * @param $target_key
 * @return an|bool
 */
function api_targets_get_target_by_campaign_target_key($campaign_id, $target_key){

	if(!api_campaigns_checkidexists($campaign_id)) return false;

	$sql = "SELECT `targetid`, `destination` FROM `targets` WHERE `campaignid` = ? and targetkey = ?";

	$rs = api_db_query_read($sql, [$campaign_id, $target_key]);
	return $rs->GetRowAssoc();
}

// List all

function api_targets_listall($campaignid, array $byStatus = []){

	if(!api_campaigns_checkidexists($campaignid)) return false;

	$sql = "SELECT `targetid`, `destination` FROM `targets` WHERE `campaignid` = ?";

	if(!empty($byStatus)){
		$sql .= ' AND status IN (' . implode(',', array_fill(0, count($byStatus), ' ? ')) . ')';
	}

	$rs = api_db_query_read($sql, array_merge([$campaignid],$byStatus));
	return $rs->GetAssoc();
}

// De-dupe

function api_targets_dedupe($source, $comparison = 0){

	if(!api_campaigns_checkidexists($source)) return api_error_raise("Sorry, that is not a valid campaign");
	if(!empty($comparison) AND !api_campaigns_checkidexists($comparison)) return api_error_raise("Sorry, that is not a valid campaign to compare against");

	$success = true;
	$logdetails = "source={$source};comparison={$comparison}";
	api_misc_audit('CAMPAIGN_DEDUPE', "Started for {$logdetails}");

	try {
		if(empty($comparison)){

			$sql = "SELECT `a`.`targetid`, `a`.`targetkey` FROM `targets` `a`, `targets` `b` FORCE INDEX(`campaignid`) WHERE `a`.`campaignid` = ? AND `b`.`campaignid` = ? AND `a`.`status` != ? AND `a`.`status` != ? AND `b`.`status` != ? AND `b`.`status` != ? AND `a`.`destination` = `b`.`destination` AND `a`.`targetid` > `b`.`targetid`";
			$rs = api_db_query_read($sql, array($source, $source, "ABANDONED", "COMPLETE", "ABANDONED", "COMPLETE"));

			while($array = $rs->FetchRow()){
				api_targets_updatestatus($array["targetid"], "ABANDONED", null);
				api_data_responses_add($source, 0, $array["targetid"], $array["targetkey"], "DUPLICATE", "DUPLICATE");
			}

		} else {

			$comparisonDestinations = [];

			$sql = "SELECT DISTINCT `destination` FROM `targets` WHERE `campaignid` = ?";
			$rs = api_db_query_read($sql, array($comparison));

			foreach($rs->GetArray() as $i) {
				$comparisonDestinations[$i["destination"]] = 1;
			}

			$sql = "SELECT `targetid`, `targetkey`, `destination` FROM `targets` WHERE `campaignid` = ? AND `status` != ? AND `status` != ?";
			$rs = api_db_query_read($sql, array($source, "ABANDONED", "COMPLETE"));

			foreach ($rs->GetArray() as $i) {
				if (isset($comparisonDestinations[$i["destination"]])) {
					api_targets_updatestatus($i["targetid"], "ABANDONED", null);
					api_data_responses_add($source, 0, $i["targetid"], $i["targetkey"], "DUPLICATE", "DUPLICATE");
				}
			}
		}
	} catch (Exception $e) {
		api_error_raise(sprintf("CAMPAIGN_DEDUPE Exception '%s' for %s", $e->getMessage(), $logdetails));
		$success = false;
	}

	api_campaigns_setting_delete_single($source, "duplicatecheck");

	api_misc_audit(
		'CAMPAIGN_DEDUPE',
		sprintf("%s for %s", ($success ? 'Finished successfully' : 'Failed'), $logdetails)
	);

	return $success;

}

function api_targets_removeprevioushumans($campaignid, $days){

	if(!api_campaigns_checkidexists($campaignid)) return api_error_raise("Sorry, that is not a valid campaign");
	if(!is_numeric($days)) return api_error_raise("Sorry, that is not a valid date limit");
	if($days > 184) return api_error_raise("Sorry, we can only check back 6 months");

	$sql = "SELECT `targetid`, `targetkey` FROM `targets` WHERE `campaignid` = ? AND `status` IN (?, ?) AND `destination` IN (SELECT `destination` FROM `response_data`, `targets` WHERE `response_data`.`action` = ? AND `response_data`.`value` = ? AND `response_data`.`timestamp` > DATE_SUB(NOW(), INTERVAL ? DAY) AND `response_data`.`targetid` = `targets`.`targetid`)";
	$rs = api_db_query_read($sql, array($campaignid, "READY", "REATTEMPT", "0_AMD", "HUMAN", $days));

	if($rs){

		while($array = $rs->FetchRow()){

			api_targets_updatestatus($array["targetid"], "ABANDONED", null);
			api_data_responses_add($campaignid, 0, $array["targetid"], $array["targetkey"], "REMOVED", "PREVIOUSLYCONTACTED");
		}

		return $rs->RecordCount();

	}

	return false;

}

/**
 * Takes a campaignid, merge data element or destination or targetkey and sets all targets with a specific value or range to abandoned
 *
 * @param integer $campaignid
 * @param string $element
 * @param string $startofrange
 * @param string $endofrange
 * @param mixed $options
 * @return integer
 */
function api_targets_abandonbydata($campaignid, $element, $startofrange, $endofrange = null, $options = []) {

	if(!api_campaigns_checkidexists($campaignid)) return api_error_raise("Sorry, that is not a valid campaign");
	if(empty($element)) return api_error_raise("Sorry, that is not a valid element to search by");
	if($startofrange == "") return api_error_raise("Sorry, that is not a valid start value");
	if(($endofrange != "") && ($endofrange < $startofrange)) return api_error_raise("Sorry, the end of range value cannot be less than the start value");

	$parameters = array();

	if(isset($options["countonly"]) && $options["countonly"]) {
		$sql = "SELECT COUNT(DISTINCT `targets`.`targetid`) as `count` FROM `merge_data`, `targets`";
	} else {
		$sql = "UPDATE `merge_data`, `targets` SET `targets`.`status` = ?";
		$parameters[] = "ABANDONED";
	}

	$sql .= " WHERE `merge_data`.`targetkey` = `targets`.`targetkey` AND `merge_data`.`campaignid` = `targets`.`campaignid` AND `targets`.`status` IN (?, ?) AND `merge_data`.`campaignid` = ?";
	$parameters[] = "READY";
	$parameters[] = "REATTEMPT";
	$parameters[] = $campaignid;

	if($element == "destination") {

		$searchterm = "`targets`.`destination`";

	} else if ($element == "targetkey") {

		$searchterm = "`targets`.`targetkey`";

	} else {

		$sql .= " AND `merge_data`.`element` = ?";
		$parameters[] = $element;

		$searchterm = "`merge_data`.`value`";

	}

	if($endofrange == "") {
		$sql .= " AND " . $searchterm . " = ?";
		$parameters[] = $startofrange;
	} else {
		$sql .= " AND " . $searchterm . " BETWEEN ? AND ?";
		$parameters[] = $startofrange;
		$parameters[] = $endofrange;
	}

	$rs = api_db_query_write($sql, $parameters);

	if(!$rs) {
		return api_error_raise("Sorry, we couldn't process that request");
	}

	if(isset($options["countonly"]) && $options["countonly"]) {
		return (int)$rs->Fields("count");
	} else {
		return api_db_affectedrows();
	}

}

function api_targets_dupecheck($campaignid){

	if(!api_campaigns_checkidexists($campaignid)) return false;

	$hasdupes = api_campaigns_setting_getsingle($campaignid, "duplicatecheck");

	if(is_numeric($hasdupes)) return $hasdupes;

	$sql = "SELECT `destination` FROM `targets` WHERE `campaignid` = ? AND `status` != ? AND `status` != ? GROUP BY `destination` HAVING COUNT(`destination`) > 1";
	$rs = api_db_query_read($sql, array($campaignid, "ABANDONED", "COMPLETE"));

	api_campaigns_setting_set($campaignid, "duplicatecheck", $rs->RecordCount());

	return $rs->RecordCount();

}

// Handle file upload

/**
 * @param int $campaignid
 * @param string $filepath
 * @param string $filename
 * @param int $user_id
 * @param int $priority
 * @return bool|QueueItem
 */
function api_targets_queued_fileupload($campaignid, $filepath, $filename, $user_id, $priority = 0){

	if(!api_campaigns_checkidexists($campaignid)) {
		return api_error_raise("FILE_UPLOAD_FAILURE: Invalid campaign id: {$campaignid}");
	}

    $queue_item = new QueueItem();
    try {

        $queue_item->setProcessType(QueueProcessTypeEnum::FILEUPLOAD())
            ->setUserId($user_id)
            ->setPriority($priority)
            ->setCampaignId($campaignid)
            ->setCreatedAt(new DateTime());

        $qm = ContainerAccessor::getContainer()->get(QueueManager::class);

        if(!$qm->persistToQueue($queue_item)){
            return api_error_raise("FILE_UPLOAD_FAILURE: Could not save file upload");
        }

	    $file = new File($filepath);

	    ActivityLogger::getInstance()->addLog(
		    KEYSTORE_TYPE_CAMPAIGNS,
		    ActivityLoggerActions::ACTION_CAMPAIGN_UPLOAD_DATA,
		    'Queueing data for upload filename: $filename, filesize: '.$file->getSize(),
		    $campaignid,
		    $user_id
	    );

        $queue_item->getQueueFiles()->add(
            $qm->addAttachment($queue_item, $filename, $file)
        );

        $on_save = function (QueueItem $item) use ($filepath){
            try {
                $fs = new Filesystem();
                $fs->remove($filepath);
            } catch (FileException $e) {
                api_error_raise("FILE_UPLOAD_FAILURE: Could not remove the uploaded file: $filepath");
            }
            api_misc_audit("Adding queued file upload to gearman: ".$item->getId());

            $payload = [
                "queue_id" => $item->getId(),
                "attempts" => 0
            ];
            api_queue_add(QueueProcessTypeEnum::FILEUPLOAD()->getValue(), $payload);
        };
        $queue_item->setCanRun(true);
        $qm->persistToQueue($queue_item, $on_save);
    } catch (Exception $e) {
        $queue_item->setReturnText($e->getMessage());
        $queue_item->setReturnCode(-1);
        api_error_raise("FILE_UPLOAD_FAILURE: ".$e->getMessage()." FILE: ".$e->getFile()." LINE: ".$e->getLine());
        $qm->persistToQueue($queue_item);
    }

    return $queue_item;
}

/**
 * @param $campaignid
 * @param $file
 * @param $name
 * @param bool $deleteonsuccess
 * @param bool $trimspace
 * @return array|bool
 * @throws PHPExcel_Exception
 */
function api_targets_fileupload($campaignid, $file, $name, $deleteonsuccess = false, $trimspace = false, $throwNoDataException = false, $returnBadRecords = false){

	if(!api_campaigns_checkidexists($campaignid)) return api_error_raise("Sorry, that is not a valid campaign id");

	if(preg_match("/\.csv$/i", $name)) $type = "csv";
	elseif(preg_match("/\.txt$/i", $name)) $type = "csv";
	elseif(preg_match("/\.xlsx?$/i", $name)) $type = "xls";
	elseif(preg_match("/\.pgp$/", $name)){

		$content = file_get_contents($file);

		if($content == false) return api_error_raise("Sorry, we couldn't open that file");

		$content = api_misc_pgp_decrypt($content);

		if($content == false) return api_error_raise("Sorry, we couldn't decrypt that file");

		if(!file_put_contents($file, $content)) api_error_raise("Sorry, we couldn't save the decrypted file");

		if(preg_match("/\.csv\.pgp$/i", $name)) $type = "csv";
		elseif(preg_match("/\.txt\.pgp$/i", $name)) $type = "csv";
		elseif(preg_match("/\.xlsx?\.pgp$/i", $name)) $type = "xls";

	}

	if(!isset($type)) return api_error_raise("The uploaded file is not a CSV or 97/2003 XLS file.");

	$settings = api_campaigns_setting_getall($campaignid);

	$delimiters = array(0 => ",", 1 => ";", 2 => "|", 3 => "\t");

	if(isset($delimiters[$settings["filedelimiter"]])) $delimiter = $delimiters[$settings["filedelimiter"]];
	else $delimiter = ",";

	// Support files without a header row by inserting a campaign set dummy header row
	if(isset($settings["headerrow"]) && trim($settings["headerrow"])) {

		// Take the setting "headerrow", explode the line by the campaign set delimiter and then trim each of the values
		$header = array_map('trim', explode($delimiter, trim($settings["headerrow"])));

		// If the explode fails, return an error as this isn't right
		if(empty($header)) return api_error_raise("Sorry, the add header row field has been incorrectly specified");

	} else {
		$header = array();
	}

	if($type == "csv"){

		$handle = fopen($file, "r");

		// Make sure the file isn't rich text format
		$snoop = fread($handle, 1024768);
		if (stripos($snoop, '{\rtf') !== false) {
			return api_error_raise("Sorry, Rich Text Format (RTF) is not supported");
		}
		rewind($handle);

		// Check if we need to skip some initial data rows
		if (isset($settings["skipinitialdatarows"]) && is_numeric($settings["skipinitialdatarows"])) {
			while($settings["skipinitialdatarows"] > 0) {
				fgetcsv($handle, 1024768, $delimiter);
				$settings["skipinitialdatarows"]--;
			}
		}

		if(empty($header)) {
			$header = fgetcsv($handle, 1024768, $delimiter);
		}

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

			if($excelWorkSheet == false) return api_error_raise("Sorry, that Excel spreadsheet seems to be incompatible or password protected.");

			$highest = [
			    'column' => $excelWorkSheet->getHighestDataColumn(),
			    'row' => $excelWorkSheet->getHighestDataRow()
			];

			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highest['column']);

		} catch(Exception $e) {
			return api_error_raise("Sorry, that Excel spreadsheet seems to be incompatible.");
		}

		for ($row = 0; $row < $highest['row']; $row++) {
		    for ($col = 0; $col <= $highestColumnIndex; $col++) {
		        $cell = $excelWorkSheet->getCellByColumnAndRow($col, $row+1);
		        $style = $excelWorkSheet->getStyle($cell->getCoordinate())->getNumberFormat()->getFormatCode();

		        // PHPExcel defaults to US dates so change this to Australian otherwise treat everything else as text
		        if($style == "mm-dd-yy") $style = "dd-mm-yy";
		        elseif($style == "mm/dd/yy") $style = "dd/mm/yyyy";
		        elseif($style == "dd/mm/yy") $style = "dd/mm/yy";
		        elseif($style == "dd/mm/yyyy") $style = "dd/mm/yyyy";
		        elseif($style == "d/mm/yyyy") $style = "dd/mm/yyyy";
		        elseif($style == "d/mm/yyyy;@") $style = "dd/mm/yyyy";
		        elseif($style == "dd/mm/yyyy;@") $style = "dd/mm/yyyy";
		        elseif($style == "d-mmm") $style = "d-mmm";
		        else $style = "General";

		        try {
		            $formattedValue = PHPExcel_Style_NumberFormat::toFormattedString($cell->getCalculatedValue(), $style);
		        } catch(Exception $e) {
		            return api_error_raise("Sorry, that Excel spreadsheet seems to be incompatible. Please check '" . $e->getMessage() . "'");
		        }

		        if(preg_match("/[0-9]+\.[0-9]+E\+[0-9]+$/", $formattedValue)) $formattedValue = (string) intval($formattedValue);

		        $exceldata[$row][$col] = $formattedValue;

		    }
		}

		// Check if we need to skip some initial data rows
		if (isset($settings["skipinitialdatarows"]) && is_numeric($settings["skipinitialdatarows"])) {
			while($settings["skipinitialdatarows"] > 0) {
				array_shift($exceldata);
				$settings["skipinitialdatarows"]--;
			}
		}

		if(empty($header) && count($exceldata)) $header = array_shift($exceldata);

		unset($excelWorkSheet);
		unset($excelFile);
		unset($excelReader);

	}

	if(is_array($header)) foreach($header as $key => $value) $header[$key] = trim($value);

	// Lets try and find the location of the targetkey column
	$targetkeypossiblelocations = array("targetkey", "uniqueid", "Id", "reference", "ref");

	if(!empty($settings["defaulttargetkey"])) array_unshift($targetkeypossiblelocations, trim($settings["defaulttargetkey"]));

	foreach($targetkeypossiblelocations as $location) {

		$result = api_misc_array_search_in($location, $header);

		if($result !== false) {

			$targetkeypos = $result;
			break;
		}
	}

	// Lets try and find the location of up to ten destination columns

	$destinationpos = array();

	for($i = 1; $i < 10; $i++){

		$destinationpossiblelocations = array("destination" . $i);

		if(!empty($settings["defaultdestination" . $i])) array_unshift($destinationpossiblelocations, trim($settings["defaultdestination" . $i]));

		if($i == 1) array_unshift($destinationpossiblelocations, "destination");

		foreach($destinationpossiblelocations as $location) {

			$result = api_misc_array_search_in($location, $header);

			if($result !== false) {
				$destinationpos[$i] = $result;
				break;
			}
		}
	}

	// Check if the file only has one column of data. If so, use that as the destination column
	$notblank = 0;

	if(is_array($header)){

		foreach($header as $key => $column){

			if(!empty($header[$key])) {
				$notblank++;
				$possibleDestination = $key;
			}

		}

	}

	if(empty($destinationpos) AND ($notblank == 1)){
		$destinationpos[1] = $possibleDestination;
		$useheaderdata = true;
	} else $useheaderdata = false;

	// If we have no destinations to upload, return an error
	if(empty($destinationpos)) return api_error_raise("Cannot find a DESTINATION column");

	$mandatoryfields = array();

	// Check if we have any mandatory fields
	if(!empty($settings["mandatoryfields"])){

		foreach(explode(",", $settings["mandatoryfields"]) as $mandatoryfield) $mandatoryfields[] = trim($mandatoryfield);

		$mandatoryfields = array_unique($mandatoryfields);
	}

	// Start the load
	api_db_starttrans();

	$good = 0;
	$bad = 0;
	$pci = 0;
	$k = 0;
	$r = 0;
	$row = 0;

	$warnOnDefultTargetkey = false;

	// Iterate over each row of data
	do {

		$row++;

		// Get a row of data to process
		if($useheaderdata) {

			$data = $header;
			$useheaderdata = false;

		} elseif($type == "csv") $data = fgetcsv($handle, 1024768, $delimiter);
		elseif($type == "xls"){

			if(isset($exceldata[$k])) $data = $exceldata[$k];
			else break;
			$k++;
		}

		// If there is no more rows, end processing
		if($data == FALSE) break;

		set_time_limit(60);

		$numberofcolumns = count($header);

		$record = array("merge_data" => array());

		// Loop over every column and collect the targetkey, destination(s) and merge data
		for ($i = $r; $i <= $numberofcolumns; $i++) {

			if(!isset($data[$i]) OR !strlen($data[$i])) continue;

			if(isset($targetkeypos) AND ($i == $targetkeypos)) $record["targetkey"] = $data[$i];
			elseif($position = array_search($i, $destinationpos)) $record["destinations"][$position] = $data[$i];
			elseif(!empty($header[$i])) $record["merge_data"][$header[$i]] = $data[$i];

		}

		// If we don't have any destinations, skip to the next row
		if(empty($record["destinations"])) continue;

		// Check if all the mandatory fields are present
		$diff = array_diff($mandatoryfields, array_keys($record["merge_data"]));

		if(!empty($diff)) {
			// We might have a heap of data format errors on the stuck. We don't want the user to see this so purge the error stack and return a clean error.
			api_error_purge();

			api_db_failtrans();
			api_db_endtrans();
			return api_error_raise("Some rows in the data file are missing required fields: " . htmlspecialchars(implode(", ", $diff)));
		}

		// If one of the colums was rt-sendat, process that and use it to delay the record
		if(!empty($record["merge_data"]["rt-sendat"])){

			if($timestamp = strtotime($record["merge_data"]["rt-sendat"])) {
                $sendat = date("Y-m-d H:i:s", $timestamp);
            }
            else {
				api_db_failtrans();
				api_db_endtrans();
                return api_error_raise("Couldn't process the rt-sendat value '" . $record["merge_data"]["rt-sendat"] . "'");
            }

		} else $sendat = null;

		// If we don't have a targetkey, use the first valid destination as the targetkey
		if(empty($record["targetkey"])) {

			if(!$warnOnDefultTargetkey) $warnOnDefultTargetkey = true;

			foreach($record["destinations"] as $destination) {

				if($record["targetkey"] = api_targets_dataformat($destination, $campaignid, $settings)) break;

			}
		}

		// If we still don't have a targetkey, skip the row.
		if(empty($record["targetkey"])) continue;

		// Insert each destination
		foreach($record["destinations"] as $priority => $destination) {

			// Don't add targets where the destination field is blank
			if (empty($destination)) {
				continue;
			}

			try {
				if (api_targets_add_single($campaignid, $destination, $record["targetkey"], $priority, null, $sendat, array("settings" => $settings, "returntrueonly" => true, "throwexception" => true))) {
					$good++;
				} else {
					$bad++;
					if ($returnBadRecords) {
						$badRecords[] = $data;
					}
				}
			} catch (ValidatorRuntimeException $exception) {
				$bad++;
				if ($returnBadRecords) {
					$badRecords[] = $data;
				}
			} catch (Exception $e) {

				// We had an insert that failed so fail the transaction and return an error
				api_db_failtrans();
				api_db_endtrans();

				// The first error on the error stack is the DB error. We don't want the user to see this so purge the error stack and return a clean error.
				api_error_purge();

				return api_error_raise("Failed to insert some data for {$record["targetkey"]}={$destination}. Aborting the upload.", 'warning');

			}
		}

		// If we have inserted at least one record and we have some merge_data, import the merge_data
		if(($good > 0) AND count($record["merge_data"])) api_targets_add_extradata_multiple($campaignid, $record["targetkey"], $record["merge_data"], true, $trimspace);

	} while(1);

	$pcirecords = PCIRecorder::getInstance()->getRecords(PCIRecorder::HYDRATE_TARGETS_FILE_UPLOAD);

	if (isset($pcirecords[$campaignid])) {
		$pcirecords = $pcirecords[$campaignid];
		// number of unique csv rows that conmtains pci data
		$pci = count($pcirecords[PCIRecorder::KEY_MERGE_DATA])
				+
				count(
					array_diff(
						$pcirecords[PCIRecorder::KEY_TARGETKEY],
						$pcirecords[PCIRecorder::KEY_MERGE_DATA]
					)
				);
		if ($pci > 0) {
			$bad += $pci;
			$good -= $pci;
		}
	}

	$postUploadHook = api_campaigns_tags_get($campaignid, "post-upload-hook");

	if(!empty($postUploadHook) AND is_readable(__DIR__ . "/scripts/hooks/" . $postUploadHook . ".php")){

		try {

			include_once(__DIR__  . "/scripts/hooks/" . $postUploadHook . ".php");

			$function = "api_campaigns_hooks_" . $postUploadHook;

			if(!is_callable($function)) {
				api_db_failtrans();
				api_db_endtrans();
				return api_error_raise("Unable to run the post upload hook for campaignid " . $campaignid);
			}
			else if(!$function($campaignid)) {
				api_db_failtrans();
				api_db_endtrans();
				return api_error_raise("The post upload hook for this campaign failed");
			}

		} catch (Exception $e){
			api_db_failtrans();
			api_db_endtrans();
			return api_error_raise("Unable to run the post upload hook for campaignid " . $campaignid);

		}

	}

	$commit = api_db_endtrans();

	if($type == "csv") fclose($handle);
	else unset($exceldata);

	if($deleteonsuccess AND !unlink($file)) return api_error_raise("Couldn't delete temporary upload file.");

	api_campaigns_setting_delete_single($campaignid, "duplicatecheck");

	api_campaigns_setting_set($campaignid, "lastupload", $name);

	if(!empty($_SESSION['userid'])) $owner = $_SESSION['userid'];
	else $owner = 2;

	api_error_purge();

	if($commit == false) {
		return api_error_raise("Sorry, the data couldn't be uploaded at this time. Please try again later.");
	}

	ActivityLogger::getInstance()->addLog(
		KEYSTORE_TYPE_CAMPAIGNS,
		ActivityLoggerActions::ACTION_CAMPAIGN_UPLOAD_DATA,
		sprintf('Uploaded data to campaign. %d rows (%d good + %d bad) ', $row, $good, $bad),
		$campaignid,
		$owner
	);

	if(($good + $bad) == 0) {
		if ($throwNoDataException) {
			throw new NoDataException('There was no data uploaded');
		}

		return api_error_raise("Sorry, there was no data uploaded");
	}
	elseif(($row > 10) && (($good/$row) < 0.8)) api_error_raise("More than 20% of the targets were bad. This could indicate data quality issues.");

	$returnData = ["rows" => $row, "good" => $good, "bad" => $bad, "pci" => $pci, "defaulttargetkey" => $warnOnDefultTargetkey];

	if (isset($badRecords) && $badRecords) {
		$returnData['badrecords'] = $header ? array_merge([$header], $badRecords) : $badRecords;
	}

	return $returnData;

}

  // Grab a target

function api_targets_gettarget($campaignid, $settings){

	global $spoollist;

	$spool_limit = 100;

	$targets = array();

    // First, check if the pre-computed spool list has any targets
	if(!empty($spoollist[$campaignid]) AND is_array($spoollist[$campaignid])) $targets = $spoollist[$campaignid];

	if(!is_array($targets) OR (count($targets) == 0)) {

		$targets = array();

		if(empty($settings["random"]) OR ($settings["random"] == "off")) $sql = "SELECT `targetid` FROM `targets` WHERE ((`status` = ?) OR (`status` = ? AND `nextattempt` < NOW())) AND `campaignid` = ? ORDER BY `status` ASC, `priority` ASC, `targetid` ASC LIMIT " . $spool_limit;
		else $sql = "SELECT `targetid` FROM `targets` WHERE ((`status` = ?) OR (`status` = ? AND `nextattempt` < NOW())) AND `campaignid` = ? ORDER BY `status` ASC, `priority` ASC, RAND() LIMIT " . $spool_limit;

		$rs = api_db_query_write($sql, array("READY", "REATTEMPT", $campaignid));

		if($rs->RecordCount() > 0) {

			$result = $rs->GetArray();

			foreach($result as $i) $targets[] = (integer)$i["targetid"];


		}
	}

	$target = array_shift($targets);

    // Update pre-computed spool list
	if(count($targets) > 0) $spoollist[$campaignid] = $targets;
	else unset($spoollist[$campaignid]);

	if(isset($target) AND is_numeric($target)){

		$sql = "UPDATE `targets` SET `status` = ?, `nextattempt` = ? WHERE `targetid` = ? AND ((`status` = ?) OR (`status` = ? AND `nextattempt` < NOW()))";
		$rs = api_db_query_write($sql, array("INPROGRESS", null, $target, "READY", "REATTEMPT"));

		if(api_db_affectedrows() > 0) return api_targets_getinfo($target);

	}

    // We couldn't find a target to return to check if the campaign is complete

	$sql = "SELECT `targetid` FROM `targets` WHERE `campaignid` = ? AND `status` IN (?,?,?) LIMIT 1";
	$rs = api_db_query_write($sql, array($campaignid, "READY", "REATTEMPT", "INPROGRESS"));

	if($rs->RecordCount() > 0) return false;
	elseif(api_campaigns_setting_cas($campaignid, "status", "ACTIVE", "DISABLED")) {

		try {
			$campaignHooks = CampaignHookBuilder::build($campaignid);
			$campaignHooks->runPostHooks();
		}catch ( Exception $e ){
			api_error_raise($e->getMessage());
		}

		api_campaigns_setting_set($campaignid, "finishtime", time());

		if(!empty($settings["noreport"]) AND ($settings["noreport"] != "on")) api_queue_add("report", $campaignid);

		if(!empty($settings["startwhendone"])) {
			// Support both numeric campaign id's or name campaign id's
			if(is_numeric($settings["startwhendone"]) || ($settings["startwhendone"] = api_campaigns_checknameexists($settings["startwhendone"]))) {
				api_campaigns_setting_cas($settings["startwhendone"], "status", "DISABLED", "ACTIVE");
			}
		}

		if(!empty($settings["delayedreport1"]) AND is_numeric($settings["delayedreport1"])) api_queue_add("report", $campaignid, date("Y-m-d H:i:s", time()+($settings["delayedreport1"]*60)));
		if(!empty($settings["delayedreport2"]) AND is_numeric($settings["delayedreport2"])) api_queue_add("report", $campaignid, date("Y-m-d H:i:s", time()+($settings["delayedreport2"]*60)));

		return false;

	}

}

function api_targets_dataformat($destination, $campaignid, $settings = false){

	if(!is_numeric($campaignid)) return false;

	if(!isset($settings["type"])) $settings["type"] = api_campaigns_setting_getsingle($campaignid, "type");

	if(empty($settings["type"])) return false;

	if(($settings["type"] == "phone") OR ($settings["type"] == "sms")){

		if(!isset($settings["region"])) $settings["region"] = api_campaigns_setting_getsingle($campaignid, "region");

		if(empty($settings["region"])) $settings["region"] = DEFAULT_REGION;

		$destination = api_data_numberformat($destination, $settings["region"]);

		if(($settings["type"] == "sms") AND !preg_match("/mobile$/", $destination["type"])) return false;

		if ($settings['region'] == CAMPAIGN_SMS_REGION_INTERNATIONAL) {
			if ('+' === $destination['destination'][0]) {
				return $destination['destination'];
			}

			return '+'.$destination['destination'];
		}

		if(!preg_match("/^" . strtolower($settings["region"]) . "/", $destination["type"])) return false;

		$destination = $destination["fnn"];

	} elseif($settings["type"] == "email") {

		$destination = filter_var(trim($destination), FILTER_SANITIZE_EMAIL);
		if(!filter_var(trim($destination), FILTER_VALIDATE_EMAIL)) return false;

	} elseif($settings["type"] == "wash"){

		$destination = substr($destination, 0, 255);

	}

	if(empty($destination)) return false;
	else return $destination;


}

/**
 * Add a target for a callme campaign
 *
 * Currently used for legacy api from dialplans
 *
 * @param string  $customer_destination
 * @param array   $mergedata
 * @param integer $source_campaign_id
 * @param string  $callme_campaign_name
 * @param integer $callme_duplicate_campaign_id
 * @param string  $source
 * @return integer|boolean
 * @throws Exception
 */
function api_targets_add_callme($customer_destination, array $mergedata, $source_campaign_id, $callme_campaign_name, $callme_duplicate_campaign_id, $source = 'api') {
	$campaign_id = api_campaigns_checkorcreate($callme_campaign_name, $callme_duplicate_campaign_id);
	if (!$campaign_id) {
		return api_error_raise('Unable to create callme campaign ' . $callme_campaign_name . ' duplicating from: ' . $callme_duplicate_campaign_id);
	}
	$targetkey = api_misc_uniqueid();

	$callme_destination = api_campaigns_setting_getsingle($source_campaign_id, "callmedestination");
	if(!preg_match("/^[0-9]{10,11}$/", $callme_destination)) {
		$callme_destination = api_campaigns_setting_getsingle($campaign_id, "callmedestination");
	}

	$elements = array(
		"date" => date("Y-m-d H:i:s"),
		"customernumber" => $customer_destination,
	);

	if (!empty($mergedata)) {
		$elements = array_merge(
			$mergedata,
			$elements
		);
	}

	$target_id = api_targets_add_single($campaign_id, $callme_destination, $targetkey, 1, $elements);

	if ($target_id) {
		$source_campaign_name = api_campaigns_setting_getsingle($source_campaign_id, 'name');
		api_data_responses_add($campaign_id, 0, $target_id, $targetkey, 'sourcecampaign', $source_campaign_name);
		api_data_responses_add($campaign_id, 0, $target_id, $targetkey, 'source', $source);
		api_campaigns_setting_set($campaign_id, 'status', 'ACTIVE');
	} else {
		return api_error_raise('Unable to create callme target for campaign: ' . $campaign_id);
	}

	return $target_id;
}

function api_targets_get_merge_data($targetid) {
    $info = api_targets_getinfo($targetid);
    if (!$info) {
        return [];
    }

    $sql = 'SELECT * FROM `merge_data` WHERE `targetkey` = ? AND `campaignid` = ?';
    $rs = api_db_query_read($sql, [$info['targetkey'], $info['campaignid']]);
    if (!$rs || !$rs->RecordCount()) {
        return [];
    }

    return $rs->GetArray();
}

function api_targets_fileupload_result_builder($results) {
	$messages = [];
    if ($results["defaulttargetkey"]) {
	    $messages[] = "WARNING: No targetkey was found for some records so the destination has been used instead.";
    }

    if ($results['good'] > CAMPAIGN_TARGET_WARNING_THRESHOLD) {
        $threshold = number_format(CAMPAIGN_TARGET_WARNING_THRESHOLD);
        $messages[] = "WARNING: With more than {$threshold} target phone numbers this job will likely cause performance problems. You are advised to keep the number of targets below {$threshold}.";
    }

    $messages[] =
        sprintf(
            'Data successfully uploaded - Uploaded <strong>%d</strong> row(s) with <strong>%d</strong> target(s): <strong>%d</strong> good and <strong>%d</strong> bad.',
            $results["rows"],
            ($results["good"] + $results["bad"]),
            $results["good"],
            $results["bad"]
        ) ;

    if (isset($results['pci']) && $results['pci'] > 0) {
        $messages[] =
            sprintf(
                "<strong>WARNING</strong>: %d target(s) with PCI PAN data were discovered, and directly set to abandoned.",
                number_format($results["pci"])
            );
    }
    return $messages;
}

function api_targets_abandontarget($targetid, $reason = null, $eventid = 0) {
    $info = api_targets_getinfo($targetid);
    if (!$info) {
        return false;
    }
    if (api_targets_updatestatus($targetid, "ABANDONED") && $reason) {
        return api_data_responses_add($info['campaignid'], $eventid, $targetid, $info["targetkey"], "REMOVED", $reason);
    }

    return true;
}

function api_targets_get_last_sms_sent_time($targetid) {
    $call_results = api_data_callresult_get_all_bytargetid($targetid, 'resultid');

     if (!$call_results || !isset($call_results['SENT'])) {
         return null;
     }

     return $call_results['SENT'];
}
