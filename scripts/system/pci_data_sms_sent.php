<?php

require_once(__DIR__ . '/../../../Morpheus/api.php');

use Services\PCI\PCICreditCard;
use Services\PCI\PCIValidator;

$cronid = getenv('CRON_ID');
if (!$cronid) {
	die("ERROR: Invalid env var CRON_ID\n");
}

$tags = api_cron_tags_get($cronid);

$batchsize = isset($tags['query-batch-size']) ? (int) $tags['query-batch-size'] : 500000;

$csvpath = isset($tags['csv-path']) ? $tags['csv-path'] : sys_get_temp_dir();
if (!is_dir($csvpath)) {
	echo "ERROR: '{$csvpath}' is not a valid directory\n";
	exit;
}
if (!is_writable($csvpath)) {
	echo "ERROR: '{$csvpath}' is not a writable directory\n";
	exit;
}

$specificids = ['type' => false, 'values' => []];
if (isset($argv[1]) && $argv[1]) {
	$range = preg_match('/^(\d+)\-(\d+)$/', $argv[1], $match);
	if ($range) {
		$specificids['type'] = 'between';
		$specificids['values'] = [$match[1], $match[2]];
	} else {
		$specificids['type'] = 'in';
		$specificids['values'] = array_map('trim', explode(',', $argv[1]));
	}
}

$csvfile = sprintf('%s/pci_sms_sent_%s.csv', rtrim($csvpath, '/'), date('Ymd_His'));

// csv with PCI matches
$csv_handle = fopen($csvfile, 'w');
if (!$csv_handle) {
	echo "ERROR: Failed to instantiate CSV file in '{$csvfile}'\n";
	exit;
}
api_csv_fputcsv_eol($csv_handle, ['client', 'table', 'column', 'id', 'source', 'timestamp', 'content']);

// PCI instance
$pci_cc = PCICreditCard::getInstance();

// *******************
// sms_sent table check
$i = 0;
do {
	$offset = $batchsize * $i;
	echo str_repeat('*', 40);
	echo "\nFetching sms_sent offset {$offset} = {$batchsize} * {$i} ... ";
	$sql = "SELECT ss.`eventid`, ss.`timestamp`, ss.`sms_account`, ss.`contents`, sam.`userid`
			FROM `sms_sent` ss
			LEFT JOIN `sms_api_mapping` sam ON ss.eventid = sam.rid\n";

	if ($specificids['type']) {
		if ($specificids['type'] === 'between') {
			$sql .= "WHERE ss.`eventid` BETWEEN ? AND ?\n";
		} else {
			$sql .= "WHERE ss.`eventid` IN (" . implode(', ', array_fill(0, count($specificids['values']), '?')) . ")\n";
		}
	}

	$sql .= "LIMIT {$batchsize} OFFSET {$offset};";
	$rs = api_db_query_read($sql, $specificids['values']);
	if (!$rs) die("ERROR: select sms_sent query failed at batch size {$batchsize}\n");
	$count = $rs->RecordCount();

	echo sprintf(
		"found %d records!\n",
		$count
	);

	foreach($rs->GetAssoc() as $eventid => $sms) {
		$pci_validator = new PCIValidator();

		$groupowner = $whitelist = null;

		/*
		 * In the following lines, we try to resolve 2 things:
		 *  - Find which user group sent this SMS
		 *  - Get the related whitelist
		 * but
		 *   - `sms_sent`.userid is sometimes empty
		 *   - `sms_sent`.sms_account (SMS DID id) should be owned by one user group only
		 *      @FIXME Once REACHTEL-39 is done, we can use SMS DID groupowner
		 */
		if ($sms['userid']) {
			$groupownerid = api_users_setting_getsingle($sms['userid'], 'groupowner');
			$groupowner = api_groups_setting_getsingle($groupownerid, 'name');

			if ($groupownerid) {
				$whitelist = api_groups_tags_get($groupownerid, PCIValidator::TAG_NAME_WHITELIST);
				if ($whitelist) {
					$pci_validator->setPANWhitelist(
						(is_array($whitelist)? $whitelist : explode(',', $whitelist))
					);
				}
			}
		} elseif ($sms['sms_account']) {
			// if no user id, we have no choice to tag the sms did
			// @FIXME See REACHTEL-39
			$whitelist = api_sms_dids_tags_get($sms['sms_account'], PCIValidator::TAG_NAME_WHITELIST);
			if ($whitelist) {
				$pci_validator->setPANWhitelist(
					(is_array($whitelist)? $whitelist : explode(',', $whitelist))
				);
			}
		}

		$matches = $pci_validator->matchAllPANData($sms['contents']);
		if($matches) {
			echo sprintf(
				"\n[%s] eventid #%d : %d matches found.\n	> original message: %s\n",
				($groupowner ?: 'UNKNOWN'),
				$eventid,
				count($matches),
				var_export($sms['contents'], true)
			);
			$newcontent = $sms['contents'];
			foreach($matches as $match) {
				api_csv_fputcsv_eol(
					$csv_handle,
					[
						($groupowner ?: '?'), // client
						'sms_sent', // table
						'contents', // column
						$eventid, // id
						"SMS DID {$sms['sms_account']}", // source
						$sms['timestamp'], // timestamp
						$sms['contents'], // content
					]
				);
				echo sprintf(
					"\t> match %s (%s)\n",
					$match,
					$pci_cc->validate($match) // card type
				);

				$masked = $pci_validator->maskPANData($match);
				$newcontent = str_replace($match, $masked, $newcontent);
			}

			if (isset($tags['replace']) && $tags['replace']) {
				// check again did not miss any
				if ($pci_validator->matchAllPANData($newcontent)) {
					echo "\tREPLACE ERROR: sms_sent eventid = {$eventid}. Message content still contains PCI data after replace:\n'{$newcontent}'\n";
					exit;
				}
				$sql = "UPDATE `sms_sent` SET `contents` = ? WHERE `eventid` = ? LIMIT 1;";
				$rs = api_db_query_write($sql, [$newcontent, $eventid]);
				if (!$rs) die("	ERROR: update sms_sent query failed for eventid = {$eventid}\n");
				echo "\t> Replaced successfully!\n";
			}
		}
	}

	$i++;
} while ($count == $batchsize);

echo "\nCSV report file generated in '{$csvfile}'\nScript executed successfully!\n";
