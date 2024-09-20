#!/usr/bin/php
<?php

/**
 * @codingStandardsIgnoreFile
 */

// comment this line if you want to use thats script ...
die("! THIS IS A DEV ENV SCRIPT ONLY !\n");
// ... and uncomment this line
//require_once("Morpheus/api.php");

fwrite(STDOUT, "How many targets to generate per campaign ? ");

if(($nbTargets = (int)trim(readline())) == 0) {
	return false;
}

$faker = Faker\Factory::create();

$campaigns = api_campaigns_list_all();

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

foreach ($campaigns as $campaign_id) {
	$campaign = api_campaigns_setting_getall($campaign_id);
	print_r($campaign);

	$duplicateKey = 0;
	for ($x = 1; $x <= $nbTargets; $x++) {
		if(rand(1, 10) === 1) {
			$targetkey = "duplicate_{$duplicateKey}";
			$duplicateKey++;
		} else {
			$targetkey = uniqid("c{$campaign_id}_t{$x}");
		}
		$target_id = api_targets_add_single(
			$campaign_id, '04' . rand(10000000, 99999999), $targetkey
		);
		if(!$target_id) {
			fwrite(STDERR, "\033[0;31m[FAILED]\033[0m\n");
			exit;
		}
		echo $targetkey . "\n";
		// extra garget data
		api_targets_add_extradata_multiple(
			$campaign_id, $targetkey,
			['firstname' => $faker->firstName, 'lastname' => $faker->lastName, 'postcode' => $faker->postcode,
			 'city' => $faker->city, 'state' => $faker->state, 'age' => $faker->numberBetween(1, 90),]
		);

		// random status
		$status = $data['target']['status'][array_rand($data['target']['status'])];
		$sql = "UPDATE targets
                SET status = ?, nextattempt = ?, reattempts = ?, ringouts = ?, errors = ?
                WHERE `targetid` = ?";
		$rs = api_db_query_write(
			$sql, $aqswde = [$status, ('REATTEMPT' == $status ? date(
			'Y-m-d H:i:s', strtotime('+' . rand(1, 3) . ' hours')
		) : null), // nextattempt
			                 ('REATTEMPT' == $status || ('READY' != $status && rand(0, 5)) ? rand(0, 3) : 0),
			                 // reattempts
			                 ('REATTEMPT' == $status || ('READY' != $status && rand(0, 5)) ? rand(0, 4) : 0),
			                 // ringouts
			                 ('ABANDONED' == $status ? rand(1, 3) : 0), // errors
			                 $target_id]
		);
		if(!$rs) {
			fwrite(STDERR, "\033[0;31m[FAILED]\033[0m\n");
			exit;
		}
	}
	fwrite(STDOUT, "[OK]\n");
}