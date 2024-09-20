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

$csvfile = sprintf('%s/pci_sms_out_%s.csv', rtrim($csvpath, '/'), date('Ymd_His'));

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
// sms_out table check
$i = 0;
do {
	$offset = $batchsize * $i;
	echo str_repeat('*', 40);
	echo "\nFetching sms_out offset {$offset} = {$batchsize} * {$i} ... ";
	$sql = "SELECT `id`, `userid`, `message`, `timestamp`
			FROM `sms_out`\n";

	if ($specificids['type']) {
		if ($specificids['type'] === 'between') {
			$sql .= "WHERE `id` BETWEEN ? AND ?\n";
		} else {
			$sql .= "WHERE `id` IN (" . implode(', ', array_fill(0, count($specificids['values']), '?')) . ")\n";
		}
	}

	$sql .= "LIMIT {$batchsize} OFFSET {$offset};";
	$rs = api_db_query_read($sql, $specificids['values']);
	if (!$rs) die("ERROR: select sms_out query failed at batch size {$batchsize}\n");
	$count = $rs->RecordCount();

	echo sprintf(
		"found %d records!\n",
		$count
	);

	foreach($rs->GetAssoc() as $id => $sms) {
		$pci_validator = new PCIValidator();

		// fetch group owner
		$groupownerid = api_users_setting_getsingle($sms['userid'], 'groupowner');
		$groupowner = api_groups_setting_getsingle($groupownerid, 'name');

		$whitelist = null;
		if ($groupownerid) {
			$whitelist = api_groups_tags_get($groupownerid, PCIValidator::TAG_NAME_WHITELIST);
			if ($whitelist) {
				$pci_validator->setPANWhitelist(
					(is_array($whitelist)? $whitelist : explode(',', $whitelist))
				);
			}
		}

		$matches = $pci_validator->matchAllPANData($sms['message']);
		if($matches) {
			echo sprintf(
				"\n[%s] id #%d : %d matches found.\n	> original message: %s\n",
				($groupowner ?: 'UNKNOWN'),
				$id,
				count($matches),
				var_export($sms['message'], true)
			);
			$newcontent = $sms['message'];
			foreach($matches as $match) {
				api_csv_fputcsv_eol(
					$csv_handle,
					[
						($groupowner ?: '?'), // client
						'sms_out', // table
						'message', // column
						$id, // id
						"USER ID {$sms['userid']}", // source
						$sms['timestamp'], // timestamp
						$sms['message'], // content
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
					echo "\tREPLACE ERROR: sms_out id = {$id}. Message content still contains PCI data after replace:\n'{$newcontent}'\n";
					exit;
				}
				$sql = "UPDATE `sms_out` SET `message` = ? WHERE `id` = ? LIMIT 1;";
				$rs = api_db_query_write($sql, [$newcontent, $id]);
				if (!$rs) die("	ERROR: update sms_out query failed for id = {$id}\n");
				echo "\t> Replaced successfully!\n";
			}
		}
	}

	$i++;
} while ($count == $batchsize);

echo "\nCSV report file generated in '{$csvfile}'\nScript executed successfully!\n";
