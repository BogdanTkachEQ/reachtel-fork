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

$csvfile = sprintf('%s/pci_sms_received_%s.csv', rtrim($csvpath, '/'), date('Ymd_His'));

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
// sms_receive table check
$i = 0;
do {
	$offset = $batchsize * $i;
	echo str_repeat('*', 40);
	echo "\nFetching sms_received offset {$offset} = {$batchsize} * {$i} ... ";
	$sql = "SELECT `smsid`, `timestamp`, `sms_account`, `contents`
			FROM `sms_received`\n";

	if ($specificids['type']) {
		if ($specificids['type'] === 'between') {
			$sql .= "WHERE `smsid` BETWEEN ? AND ?\n";
		} else {
			$sql .= "WHERE `smsid` IN (" . implode(', ', array_fill(0, count($specificids['values']), '?')) . ")\n";
		}
	}

	$sql .= "LIMIT {$batchsize} OFFSET {$offset};";
	$rs = api_db_query_read($sql, $specificids['values']);
	if (!$rs) die("ERROR: select sms_received query failed at batch size {$batchsize}\n");
	$count = $rs->RecordCount();

	echo sprintf(
		"found %d records!\n",
		$count
	);

	foreach($rs->GetAssoc() as $smsid => $sms) {
		$pci_validator = new PCIValidator();

		$groupowner = null;

		$matches = $pci_validator->matchAllPANData($sms['contents']);
		if($matches) {
			echo sprintf(
				"\n[%s] smsid #%d : %d matches found.\n	> original message: %s\n",
				($groupowner ?: 'UNKNOWN'),
				$smsid,
				count($matches),
				var_export($sms['contents'], true)
			);
			$newcontent = $sms['contents'];
			foreach($matches as $match) {
				api_csv_fputcsv_eol(
					$csv_handle,
					[
						($groupowner ?: '?'), // client
						'sms_received', // table
						'contents', // column
						$smsid, // id
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
					echo "\tREPLACE ERROR: sms_received smsid = {$smsid}. Message content still contains PCI data after replace:\n'{$newcontent}'\n";
					exit;
				}
				$sql = "UPDATE `sms_received` SET `contents` = ? WHERE `smsid` = ? LIMIT 1;";
				$rs = api_db_query_write($sql, [$newcontent, $smsid]);
				if (!$rs) die("	ERROR: update sms_received query failed for smsid = {$smsid}\n");
				echo "\t> Replaced successfully!\n";
			}
		}
	}

	$i++;
} while ($count == $batchsize);

echo "\nCSV report file generated in '{$csvfile}'\nScript executed successfully!\n";
