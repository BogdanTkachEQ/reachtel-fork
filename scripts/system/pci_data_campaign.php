<?php

require_once(__DIR__ . '/../../../Morpheus/api.php');

use Services\PCI\PCICreditCard;
use Services\PCI\PCIValidator;

$cronid = getenv('CRON_ID');
if (!$cronid) {
	die("ERROR: Invalid env var CRON_ID\n");
}

$tags = api_cron_tags_get($cronid);

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

$csvfile = sprintf('%s/pci_campaigns_%s.csv', rtrim($csvpath, '/'), date('Ymd_His'));

// csv with PCI matches
$csv_handle = fopen($csvfile, 'w');
if (!$csv_handle) {
	echo "ERROR: Failed to instantiate CSV file in '{$csvfile}'\n";
	exit;
}
api_csv_fputcsv_eol($csv_handle, ['client', 'table', 'column', 'id', 'source', 'timestamp', 'content']);

// PCI instance
$pci_cc = PCICreditCard::getInstance();

// ***************
// campaigns check
echo "Checking campaigns...";


// campaign id filter
if ($specificids['type']) {
	$sql = "SELECT `id`, `value` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `id` ";
	$parameters = ['CAMPAIGNS', 'name'];
	if ($specificids['type'] === 'between') {
		$sql .= "BETWEEN ? AND ?";
		$parameters[] = $specificids['values'][0];
		$parameters[] = $specificids['values'][1];
	} else {
		$sql .= "IN(" . implode(',', array_fill(0, count($specificids['values']), '?')) . ")";
		$parameters = array_merge($parameters, $specificids['values']);
	}

	$sql .= " ORDER BY `id` ASC;";
	$rs = api_db_query_read($sql, $parameters);
	$campaigns = $rs->GetAssoc();
} else {
	$campaigns = api_campaigns_list_all(
		true,
		false,
		false
	);
	$campaigns = array_reverse($campaigns, true);
}

echo sprintf("Found %d campaign(s)\n\n", $total = count($campaigns));
foreach($campaigns as $campaignid => $name) {
	api_db_ping();

	$pci_validator = new PCIValidator();

	// fetch group owner
	$groupownerid = api_campaigns_setting_getsingle($campaignid, 'groupowner');
	$groupowner = api_groups_setting_getsingle($groupownerid, 'name');

	// whitelist, campaign overrides group whitelist tag
	$whitelist = api_campaigns_tags_get($campaignid, PCIValidator::TAG_NAME_WHITELIST);
	if (!$whitelist) {
		$whitelist = api_groups_tags_get($groupownerid, PCIValidator::TAG_NAME_WHITELIST);
	}

	if ($whitelist) {
		$pci_validator->setPANWhitelist(
			(is_array($whitelist)? $whitelist : explode(',', $whitelist))
		);
	}

	// PCI check in targets (including archives)
	$maskedMap = [];
	foreach(['targets', 'targets_archive'] as $table) {
		$sql = "SELECT `targetid`, `targetkey`  FROM `{$table}` WHERE `campaignid` = ?;";
		$rs = api_db_query_read($sql, [$campaignid]);
		if (!$rs) die("\nERROR: {$table} query failed\n" . api_db_last_error_write());

		// validate target key
		$stats = [];
		foreach($rs->GetAssoc() as $targetid => $targetkey) {
			if ($pci_validator->isPANData($targetkey)) {
				$stats[$targetid] = $targetkey;
				api_csv_fputcsv_eol(
					$csv_handle,
					[
						$groupowner, // client
						$table, // table
						'targetkey', // column
						$targetid, // id
						"campaign {$campaignid}", // source
						null, // timestamp
						$targetkey, // content
					]
				);
			}
		}

		if ($stats) {
			$percent = round(count($stats) / $rs->RecordCount() * 100, 2);

			echo sprintf(
				"[%s] %s (id=%d):\n	> `%s`.targetkey (%s%% - %d/%d)\n",
				$groupowner,
				$name,
				$campaignid,
				$table,
				$percent,
				count($stats),
				$rs->RecordCount()
			);

			foreach($stats as $targetid => $targetkey) {
				$type = $pci_cc->validate($targetkey); // card type
				echo "\t- {$targetkey} ({$type} - targetid={$targetid})\n";

				if (isset($tags['replace']) && $tags['replace']) {
					$masked = $pci_validator->maskPANData($targetkey, true);
					echo "\t\t* Masking {$masked}...";
					$sql = "UPDATE `{$table}` SET `targetkey` = ?
							WHERE `campaignid` = ? AND `targetid` = ? AND `targetkey` = ? LIMIT 1;";
					$rs = api_db_query_write($sql, [$masked, $campaignid, $targetid, (string) $targetkey]);
					if (!$rs) die("\nERROR: update `{$table}` query failed for targetid = {$targetid}\n" . api_db_last_error_write());
					$maskedMap[$targetkey] = $masked;
					echo "✔\n";
				}
			}
		}
		unset($rs);
	}

	// replace target keys in merge data tables
	if ($maskedMap && isset($tags['replace']) && $tags['replace']) {
		foreach($maskedMap as $targetkey => $masked) {
			echo "	> Masking targetkeys {$targetkey} in merge tables...";
			foreach(['merge_data', 'merge_data_archive'] as $table) {
				$sql = "UPDATE `{$table}` SET `targetkey` = ?
							WHERE `campaignid` = ? AND `targetkey` = ?;";
				$rs = api_db_query_write($sql, [$masked, $campaignid, (string) $targetkey]);
				if (!$rs) die("\nERROR: update targetkeys in `{$table}` query failed for targetkey = {$targetkey}\n" . api_db_last_error_write());
			}
			echo "✔\n";
		}
	}

	foreach(['merge_data', 'merge_data_archive'] as $table) {
		// query taken from api_data_merge_get_alldata()
		$sql = "SELECT `targetkey`, `element`, `value` FROM `{$table}` WHERE `campaignid` = ?;";
		$rs = api_db_query_read($sql, [$campaignid]);
		if (!$rs) die("\nERROR: Query failed to fetch `{$table}` for campaignid = {$campaignid}\n" . api_db_last_error_write());

		$targetdata = [];
		if ($rs && $rs->RecordCount() > 0) {
			while ($res = $rs->GetArray(100)) {
				foreach ($res as $r) {
					$targetdata[$r["targetkey"]][$r["element"]] = $r["value"];
				}
			}
		}

		$stats = [];
		foreach($targetdata as $targetkey => $data) {
			foreach($data as $element => $value) {
				if ($value) { // only do stats if not empty
					if (!isset($stats[$element]['count'])) {
						$stats[$element]['count'] = 0;
					}
					$stats[$element]['count']++;
					if ($pci_validator->matchAllPANData($value, true)) {
						$stats[$element]['pci'][$targetkey] = $value;
						api_csv_fputcsv_eol(
							$csv_handle,
							[
								$groupowner, // client
								$table, // table
								$element, // column
								$targetkey, // id
								"campaign {$campaignid}", // source
								null, // timestamp
								$value, // content
							]
						);
					}
				}
			}
		}

		foreach($stats as $element => $stat) {
			if (isset($stat['pci'])) {
				$nbPci = count($stat['pci']);
				$percent = round($nbPci / $stat['count'] * 100, 2);


				echo sprintf(
					"[%s] %s (id=%d):\n	> `{$table}`.%s (%s%% match - %d/%d)\n",
					$groupowner,
					$name,
					$campaignid,
					$element,
					$percent,
					$nbPci,
					$stat['count']
				);

				foreach ($stat['pci'] as $targetkey => $value) {
					$type = $pci_cc->validate($value); // card type
					echo "\t- {$value} ({$type} - targetkey={$targetkey})\n";
					if (isset($tags['replace']) && $tags['replace']) {
						$masked = $pci_validator->maskPANData($value);
						echo "\t\t* Masking {$masked}...";
						$sql = "UPDATE `{$table}` SET `value` = ?
							WHERE `campaignid` = ? AND `targetkey` = ? AND `element` = ? AND `value` = ? LIMIT 1;";
						$rs = api_db_query_write($sql, [$masked, $campaignid, (string) $targetkey, $element, $value]);
						if (!$rs) die("\nERROR: update `{$table}` query failed for targetkey/element = {$targetkey}/{$element}\n" . api_db_last_error_write());
						echo "✔\n";
					}
				}

			}
		}
	}
}

echo "\nCSV report file generated in '{$csvfile}'\nScript executed successfully!\n";
