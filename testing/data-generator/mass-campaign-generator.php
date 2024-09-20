#!/usr/bin/php
<?php

/**
 * @codingStandardsIgnoreFile
 */

// comment this line if you want to use thats script ...
die("! THIS IS A DEV ENV SCRIPT ONLY !\n");
// ... and uncomment this line
//require_once("Morpheus/api.php");

class CampaignCreator {

	public $settings = [];

	public function addSetting($setting, $value) {
		$this->settings[$setting] = $value;
	}

	public function create($type) {
		$faker = Faker\Factory::create();
		$id = api_campaigns_add($faker->bs, $type);
		foreach ($this->settings as $setting => $value) {
			api_campaigns_setting_set($id, $setting, $value);
		}
		return $id;
	}
}

$faker = Faker\Factory::create();

fwrite(STDOUT, "Delete ALL campaigns first ? Type YES to do so ");
if((trim(readline())) === "YES") {
	foreach (api_campaigns_list_all() as $campaign_id) {
		echo "Deleting $campaign_id\n";
		api_campaigns_delete($campaign_id);
	}
}

fwrite(STDOUT, "Set random lastsend times ? Type YES to do so ");
$randomLastSend = false;
if((trim(readline())) === "YES") {
	$randomLastSend = true;
}

fwrite(STDOUT, "How many campaigns to generate ? ");
$creator = new CampaignCreator();

$types = ["phone", "sms"];

$ids = [];
if(($campaigns = (int)trim(readline())) > 0) {
	for ($x = 0; $x <= $campaigns; $x++) {
		if($randomLastSend) {
			$date = $faker->dateTimeBetween("-10 years", "now");
			echo $date->format("Y-m-d H:i:s") . "\n";
			$creator->addSetting(
				"lastsend", $date->getTimestamp()
			);
		}
		$ids[] = $creator->create($types[rand(0, 1)]);
	}
}

echo implode(",", $ids);

