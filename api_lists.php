<?php

// Add

function api_lists_add($name){

	if(!preg_match("/^([a-z0-9\-_ ]{3,50})$/i", $name)) return api_error_raise("Sorry, that is not a valid name");

	if(api_lists_checknameexists($name)) return api_error_raise("Sorry, an item  with the name '" . $name . "' already exists");

	$lastid = api_keystore_increment("LISTS", 0, "nextid");

	api_lists_setting_set($lastid, "name", $name);
	api_lists_setting_set($lastid, "rows", 0);
	api_lists_setting_set($lastid, "created", time());
	api_lists_setting_set($lastid, "groupowner", api_users_setting_getsingle($_SESSION['userid'], "groupowner")	);

	return $lastid;

}

// Check if name already exists

function api_lists_checknameexists($name) { return api_keystore_checkkeyexists("LISTS", "name", $name); }

// Check if id exists

function api_lists_checkidexists($id){

	if(!is_numeric($id)) return false;

	if(api_keystore_get("LISTS", $id, "name") !== FALSE) return true;
	else return false;
}

// Delete

function api_lists_delete($id){

	if(!api_lists_checkidexists($id)) return api_error_raise("Sorry, that is not a valid id");

	$groups = api_security_groupaccess($_SESSION['userid']);

	if(!$groups["isadmin"] AND !in_array(api_lists_setting_getsingle($id, "groupowner"), $groups["groups"])) return api_error_raise("Sorry, you don't have permission to access that item");

	$filename = api_lists_setting_getsingle($id, "filename");

	if(($filename != "") AND file_exists(SAVE_LOCATION . LISTS_LOCATION . "/" . $filename)) unlink(SAVE_LOCATION . LISTS_LOCATION . "/" . $filename);

	api_keystore_purge("LISTS", $id);

	return true;

}

// Download

function api_lists_download($id){

	if(!api_lists_checkidexists($id)) return api_error_raise("Sorry, that file doesn't exist");

	$groups = api_security_groupaccess($_SESSION['userid']);

	if(!$groups["isadmin"] AND !in_array(api_lists_setting_getsingle($id, "groupowner"), $groups["groups"])) return api_error_raise("Sorry, you don't have permission to access that item");

	if(!file_exists(READ_LOCATION . LISTS_LOCATION . "/" . api_lists_setting_getsingle($id, "filename"))) return api_error_raise("Sorry, that list isn't available at the moment");

	header('Content-type: application/text-csv');
	header('Content-Disposition: attachment; filename="' . api_lists_setting_getsingle($id, "originalfilename") . '"');

	$handle = fopen(READ_LOCATION . LISTS_LOCATION . "/" . api_lists_setting_getsingle($id, "filename"), "r");

	while (!feof($handle)) print fread($handle, 1024);

	fclose($handle);
	exit;

}



// List

function api_lists_listall($userid, $short = false){

	$ids = api_keystore_getids("LISTS", "name", true);
	$rows = api_keystore_getids("LISTS", "rows", true);
	$groupowner = api_keystore_getids("LISTS", "groupowner", true);

	if(empty($ids) OR !is_array($ids)) return array();

	natcasesort($ids);

	$a = array();

	$groups = api_security_groupaccess($userid);

	foreach($ids as $id => $name){

		if(isset($groupowner[$id]) AND ($groups["isadmin"] OR in_array($groupowner[$id], $groups["groups"]))){

			if($short) $a[$id] = $name;
			else {
				$a[$id]["name"] = $name;
				if(isset($rows[$id])) $a[$id]["rows"] = $rows[$id];
			}
		}

	}

	return $a;

}


function api_lists_upload($id, $file){

	if(!api_lists_checkidexists($id)) return api_error_raise("Sorry, that is not a valid id");

	$uploadedfile = '/tmp/list-' . $id . ".csv";

	if(preg_match("/\.csv$/i", basename($file['name']))) $type = "csv";
	else return api_error_raise("The uploaded file is not a CSV file");

	if (move_uploaded_file($file['tmp_name'], $uploadedfile)) {

		$handle = fopen($uploadedfile, "r");
		$header = fgetcsv($handle, 1024, ",");

		$targetKeyPos = api_misc_array_search_in("targetkey", $header);
		if($targetKeyPos === FALSE) $targetKeyPos = api_misc_array_search_in("uniqueid", $header);
		if($targetKeyPos === FALSE) $targetKeyPos = api_misc_array_search_in("Id", $header);
		if($targetKeyPos === FALSE) $targetKeyPos = api_misc_array_search_in("ref", $header);

		$destinationPos = api_misc_array_search_in("destination", $header);
		if($destinationPos === FALSE) $destinationPos = api_misc_array_search_in("Cr Attg Phone", $header);
		if($destinationPos === FALSE) $destinationPos = api_misc_array_search_in("addr", $header);

		$destination2Pos = api_misc_array_search_in("destination2", $header);
		if($destination2Pos === FALSE) $destination2Pos = api_misc_array_search_in("altaddr", $header);

		$destination3Pos = api_misc_array_search_in("destination3", $header);

		if(($destinationPos === FALSE) AND ($destination2Pos === FALSE) AND ($destination3Pos === FALSE)) {

			unlink($uploadedfile);

			return api_error_raise("Cannot find a DESTINATION column");
		}


		$linecount = 0;

		while(!feof($handle)){
			$line = fgets($handle);
			$linecount++;
		}

		fclose($handle);


		if(($linecount > 1) AND (rename($uploadedfile, SAVE_LOCATION . LISTS_LOCATION . "/list-" . $id . ".csv"))){

			chmod(SAVE_LOCATION . LISTS_LOCATION . "/list-" . $id . ".csv", 0664);

			api_lists_setting_set($id, "filename", "list-" . $id . ".csv");
			api_lists_setting_set($id, "originalfilename", basename($file['name']));
			api_lists_setting_set($id, "rows", $linecount - 1);

			return $linecount - 1;

		} else return api_error_raise("Unable to delete the temporary file");


	}
}

function api_lists_merge($id, $campaignid){

	if(!api_lists_checkidexists($id)) return api_error_raise("Sorry, you don't have access to that list");

	$groups = api_security_groupaccess($_SESSION['userid']);

	if(!$groups["isadmin"] AND !in_array(api_lists_setting_getsingle($id, "groupowner"), $groups["groups"])) return api_error_raise("Sorry, you don't have permission to access that item");

	return api_targets_fileupload($campaignid, READ_LOCATION . LISTS_LOCATION . "/" . api_lists_setting_getsingle($id, "filename"), api_lists_setting_getsingle($id, "originalfilename"), false);

}

// List settings

  // Add or update setting

function api_lists_setting_set($id, $setting, $value) { return api_keystore_set("LISTS", $id, $setting, $value); }

  // Delete setting

    // Single

function api_lists_setting_delete_single($id, $setting) { return api_keystore_delete("LISTS", $id, $setting); }

  // Get

    // Single

function api_lists_setting_getsingle($id, $setting) { return api_keystore_get("LISTS", $id, $setting); }

    // All

function api_lists_setting_getall($id) { return api_keystore_getnamespace("LISTS", $id); }

?>
