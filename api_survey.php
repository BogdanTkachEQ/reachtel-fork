<?php

function api_misc_surveyresults($campaigns, $weightingmapoverride = null, $options = []){

	error_reporting('E_NONE');

	if(is_numeric($campaigns)) $campaigns = array($campaigns);

	$returndata = (isset($options["returndata"]) && $options["returndata"]) ? true : false;

	foreach($campaigns as $campaignid){

		if(!api_campaigns_checkidexists($campaignid)) return api_error_raise("Sorry, that is not a valid campaign");

		$surveyweight = api_campaigns_setting_getsingle($campaignid, "surveyweight");

		if(!api_survey_weight_checkidexists($surveyweight)) $surveyweight = 6;

		$weightingmap = api_survey_weight_generatemap($surveyweight);

		set_time_limit(120);

		$results = api_data_responses_getall_bycampaignid($campaignid, true, true);

		$lastquestion = api_campaigns_tags_get($campaignid, "survey-last-question");

		foreach($results as $targetid => $responses){

			if(empty($responses)) continue;

			if(empty($responses["GENDER"]) OR empty($responses["AGE"])) continue;

			if(isset($responses["AGE"]) AND ($responses["AGE"] == "Under18")) continue;

			if(!empty($lastquestion) AND empty($responses[$lastquestion])) continue;

			foreach($responses as $action => $value){

				$resultrow[$targetid][$action] = $value;

				if ($action == "targetkey") continue;

				if($action == "GENDER") {
					$matrix[$action][ucfirst(strtolower($value))][ucfirst(strtolower($responses["GENDER"]))]++;
					$questions[$action][ucfirst(strtolower($value))]++;
				} else {
					$matrix[$action][$value][ucfirst(strtolower($responses["GENDER"]))]++;
					$questions[$action][$value]++;
				}

				$questiontotal[$action][ucfirst(strtolower($responses["GENDER"]))]++;

				$weighteddata[$action][$value][ucfirst(strtolower($responses["GENDER"]))][$responses["AGE"]]++;

			}

		}
	}

	if($weightingmapoverride) $weightingmap =  api_survey_weight_generatemap($weightingmapoverride);

	if(!is_array($weightingmap)) return api_error_raise("Sorry, that is not a valid weighting");

	$total = $questions["GENDER"]["Male"] + $questions["GENDER"]["Female"];

	$wmgender = array("Male" => 0, "Female" => 0);

	foreach($weightingmap as $gender => $ages) foreach($ages as $age => $weighting){

		$wm[$gender][$age] = $weighting / ($matrix["AGE"][$age][$gender] / $total);

		$wmgender[$gender] += $weighting;

	}

	$genweight["Male"] = $wmgender["Male"] / ($questiontotal["GENDER"]["Male"] / $total);
	$genweight["Female"] = $wmgender["Female"] / ($questiontotal["GENDER"]["Female"] / $total);

	if($returndata) {
		return ["data" => $resultrow, "total" => $total, "questions" => array_keys($questiontotal), "genderweightmap" => $genweight, "agegenderweightmap" => $wm];
	}

	if($matrix){

		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=\"" . api_campaigns_setting_getsingle($campaignid, "name") . "-surveyreport.csv\"");

		// Sort the matric the way we want it
		ksort($martix, SORT_NATURAL);

		$a = $matrix["AGE"];
		unset($matrix["AGE"]);
		$matrix["AGE"] = $a;
		$a = $matrix["GENDER"];
		unset($matrix["GENDER"]);
		$matrix["GENDER"] = $a;

		if (isset($matrix["INCOME"])) {
			$a = $matrix["INCOME"];
			unset($matrix["INCOME"]);
			$matrix["INCOME"] = $a;
		}

		// Iterate over each QUESTION
		foreach($matrix as $question => $results){
			if(($question != "0_AMD") AND ($question != "0_PARTICIPATE")){

				print $question . "\n";
				print "Answer,Male,Male%,Female,Female%,Total,Actual%,GenderWeighted%,GenderWeightedDiff%,AgeGenderWeight%,AgeGenderDiff%\n";

				if($results){
					ksort($results);

					// Iterate over each ANSWER
					foreach($results as $answer => $count) {

						$malepc = ($count["Male"] / $questiontotal[$question]["Male"]) * 100;
						$femalepc = ($count["Female"] / $questiontotal[$question]["Female"]) * 100;
						$totalunweighted = $count["Male"] + $count["Female"];
						$actualpc = (($count["Male"] + $count["Female"]) / ($questiontotal[$question]["Male"] + $questiontotal[$question]["Female"])) * 100;
						$weightedpc = ($malepc * $wmgender["Male"]) + ($femalepc * $wmgender["Female"]);
						$diff = $weightedpc - $actualpc;

						if(($questiontotal[$question]["Male"] + $questiontotal[$question]["Female"]) < $total) {

							$agegenweight = 0;
							$agegendiff = 0;

						} else {

							$agegenweight = 0;

							// Iterate over each GENDER and AGE bracket and calculate a weighted score given the appropriate weighting agains thte number of responses
							foreach($weightingmap as $gender => $ages) foreach($ages as $age => $weight) {
								if(isset($weighteddata[$question][$answer][$gender][$age])) {
									$agegenweight += $weighteddata[$question][$answer][$gender][$age] * $wm[$gender][$age];
								}
							}

							$agegenweight = ($agegenweight / $total) * 100;
							$agegendiff = $agegenweight - $actualpc;
						}

						print $answer . "," . sprintf("%d", $count["Male"]) . "," . sprintf("%01.1f", $malepc) . "%," . sprintf("%d", $count["Female"]) . "," . sprintf("%01.1f", $femalepc) . "%," . $totalunweighted . "," . sprintf("%01.1f", $actualpc) . "%," . sprintf("%01.1f", $weightedpc) . "%," . sprintf("%01.1f", $diff) . "%," . sprintf("%01.1f", $agegenweight) . "%," . sprintf("%01.1f", $agegendiff) . "%\n";
					}
				}

				print "\n\n";
			}
		}

		print "\n\n==DATA==\n";

		print "targetkey,postcode,";
		foreach(array_keys($matrix) as $answer) print $answer . ",";

		print "GENDERWEIGHT,AGEGENDERWEIGHT\n";

		foreach($resultrow as $r){
		    print $r['targetkey'] . ',';
		    print ((isset($r['mergedata']['postcode'])) ? $r['mergedata']['postcode'] : '') . ',';

			foreach($matrix as $answer => $blah) {
				print $r[$answer] . ",";
			}
			print $genweight[$r["GENDER"]] . ",";
			print $wm[$r["GENDER"]][$r["AGE"]] . "\n";

		}

		exit;

	} else return api_error_raise("Sorry, I couldn't find any survey data to report on or it's not in the correct format.");

}

function api_survey_surveyresults_national($search) {

	if(empty($search)) {
		return api_error_raise("Sorry, that is not a valid search term");
	}

	$questions = [];
	$total = 0;

	// Map the surveyweight ID to that states population
	$states = [1130 => 5316819,
		1131 => 4148385,
		1132 => 3278855,
		1133 => 1247851,
		1134 => 1709691,
		1135 => 381298,
		1136 => 153717,
		1137 => 277558];

	$campaigns = api_campaigns_list_all(true, null, 20, array("search" => $search));

	if(count($campaigns) != 8) {
		return api_error_raise("Can't process national data as there isn't all states and territories present");
	}

	foreach ($campaigns as $campaignid => $name){

		if(!($results[$campaignid] = api_misc_surveyresults($campaignid, null, ["returndata" => true]))) {
			return api_error_raise("Unable to find results for the campaign '" . $name);
		}

		$results[$campaignid]["surveyweight"] = api_campaigns_setting_getsingle($campaignid, "surveyweight");

		if(in_array($results[$campaignid]["surveyweight"], $states)) {
			return api_error_raise("Cannot process the campaign '" . $name . "' as the survey weight isn't in the list of known states");
		}

		$results[$campaignid]["name"] = $name;

		$questions = array_unique(array_merge($questions, $results[$campaignid]["questions"]));
		$total += $results[$campaignid]["total"];

	}

	header("Content-type: application/octet-stream");
	header("Content-disposition: attachment; filename=\"" . $search . "-national-spssreport.csv\"");

	print "campaign,targetid,targetkey,ProspectId,";

	sort($questions);

	foreach($questions as $question) {
		print $question . ",";
	}

	print "GENDERWEIGHT,AGEGENDERWEIGHT,POPULATIONWEIGHT\n";

	foreach ($results as $campaignid => $data) { // for each state...

		// Work out how much we have to weight each state population = (StatePopulation / Total Population) / (StateSample / TotalSample)
		$statepopulationweight = ($states[$data["surveyweight"]] / 16514174) / ($data["total"] / $total);

		$mergedata = api_data_merge_get_alldata($campaignid);

		foreach ($data["data"] as $targetid => $row) { // for each response row
			print $data["name"] . "," . $targetid . "," . $row["targetkey"] . ",";
			print (isset($mergedata[$row["targetkey"]]["ProspectId"]) ? $mergedata[$row["targetkey"]]["ProspectId"] : "") . ",";
			foreach ($questions as $question) print $row[$question] . ",";
			print $data["genderweightmap"][$row["GENDER"]] . ","; // Print the gender weight
			print $data["agegenderweightmap"][$row["GENDER"]][$row["AGE"]] . ","; // Print the age/gender weight
			print $data["agegenderweightmap"][$row["GENDER"]][$row["AGE"]] * $statepopulationweight . ","; // Print the population/age/gender
			print "\n";
		}
	}

	exit;
}

function api_survey_weight_generatemap($id){

	if(!api_survey_weight_checkidexists($id)) return api_error_raise("Sorry, that is not a valid weighting map");

	$weightingmap = array("Male" => array(), "Female" => array());

	$sum = 0;

	$settings = api_survey_weight_setting_getall($id);

	$weightingmap["Male"]["18_34"] = $settings["male18to34"];
	$weightingmap["Male"]["35_50"] = $settings["male35to50"];
	$weightingmap["Male"]["51_65"] = $settings["male51to65"];
	$weightingmap["Male"]["65Plus"] = $settings["male65plus"];
	$weightingmap["Female"]["18_34"] = $settings["female18to34"];
	$weightingmap["Female"]["35_50"] = $settings["female35to50"];
	$weightingmap["Female"]["51_65"] = $settings["female51to65"];
	$weightingmap["Female"]["65Plus"] = $settings["female65plus"];

	foreach($weightingmap as $sex => $ages) foreach($ages as $age => $value) $sum = $sum + $value;

	foreach($weightingmap as $sex => $ages){

		$weightingmap[$sex]["18_34"] = $weightingmap[$sex]["18_34"] / $sum;
		$weightingmap[$sex]["35_50"] = $weightingmap[$sex]["35_50"] / $sum;
		$weightingmap[$sex]["51_65"] = $weightingmap[$sex]["51_65"] / $sum;
		$weightingmap[$sex]["65Plus"] = $weightingmap[$sex]["65Plus"] / $sum;

	}

	return $weightingmap;

}

// Add

function api_survey_weight_add($name){

	if(!preg_match("/^([a-z0-9\-_,\/\(\)\.' ]{3,75})$/i", $name)) return api_error_raise("Sorry, that is not a valid name");

	if(api_survey_weight_checknameexists($name)) return api_error_raise("Sorry, an item  with the name '" . $name . "' already exists");

	$lastid = api_keystore_increment("SURVEYWEIGHT", 0, "nextid");

	api_survey_weight_setting_set($lastid, "name", $name);

	return $lastid;

}

// Check if name already exists

function api_survey_weight_checknameexists($name) { return api_keystore_checkkeyexists("SURVEYWEIGHT", "name", $name); }

// Check if id exists

function api_survey_weight_checkidexists($id){

	if(!is_numeric($id)) return api_error_raise("Sorry, that is not a valid id");

	if(api_keystore_get("SURVEYWEIGHT", $id, "name") !== FALSE) return true;
	else return false;
}

// Delete

function api_survey_weight_delete($id){

	if(!api_survey_weight_checkidexists($id)) return api_error_raise("Sorry, that is not a valid id");

	if(api_keystore_checkkeyexists("CAMPAIGNS", "surveyweight", $id)) return api_error_raise("Sorry, cannot delete an item that is assigned to a campaign.");

	api_keystore_purge("SURVEYWEIGHT", $id);

	return true;

}


// List

function api_survey_weight_listall(){

	$names = api_keystore_getids("SURVEYWEIGHT", "name", true);

	if(empty($names) OR !is_array($names)) return array();

	natcasesort($names);

	return $names;

}


// Group settings

// Add or update setting

function api_survey_weight_setting_set($id, $setting, $value) { return api_keystore_set("SURVEYWEIGHT", $id, $setting, $value); }

// Delete setting

// Single

function api_survey_weight_setting_delete_single($id, $setting) { return api_keystore_delete("SURVEYWEIGHT", $id, $setting); }

// Get

// Single

function api_survey_weight_setting_getsingle($id, $setting) { return api_keystore_get("SURVEYWEIGHT", $id, $setting); }

// All

function api_survey_weight_setting_getall($id) { return api_keystore_getnamespace("SURVEYWEIGHT", $id); }
