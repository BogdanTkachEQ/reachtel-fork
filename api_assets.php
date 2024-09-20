<?php
/**
 * Assets Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

/**
 * Add or Update asset
 *
 * @param string $name
 * @return string|false
 */
function api_asset_add($name) {

	if (api_asset_checknameexists($name)) {
		return api_error_raise("Sorry, a file with the name '" . $name . "' already exists");
	}

	$lastid = api_keystore_increment("ASSET", 0, "nextid");

	api_asset_setting_set($lastid, "name", $name);

	return $lastid;
}

/**
 * Check if asset filename already assigned
 *
 * @param string $name
 * @return string|false
 */
function api_asset_checknameexists($name) {
	return api_keystore_checkkeyexists("ASSET", "name", $name);
}

/**
 * Check if asset id already exists
 *
 * @param mixed $assetid
 * @return string|false
 */
function api_asset_checkidexists($assetid) {

	if (!is_numeric($assetid)) {
		return api_error_raise("Sorry, that is not a valid asset id");
	}

	return api_asset_setting_getsingle($assetid, "name") !== false;
}

/**
 * Delete asset
 *
 * @param mixed $assetid
 * @return string|false
 */
function api_asset_delete($assetid) {

	if (!api_asset_checkidexists($assetid)) {
		return api_error_raise("Sorry, that asset id doesn't exist");
	}

	// Unlink file
	$name = api_asset_setting_getsingle($assetid, "name");
	$asset_path = SAVE_LOCATION . ASSET_LOCATION . "/" . $name;
	if ($name && is_file($asset_path)) {
		unlink($asset_path);
	}

	api_keystore_purge("ASSET", $assetid);

	return true;
}

/**
 * Upload an asset file
 *
 * @param string $file
 * @return boolean
 */
function api_asset_fileupload($file) {
	if (!isset($file['error']) || $file['error']) {
		if (isset($file['error']) && $file['error'] == 2) {
			return api_error_raise("Sorry, that file is too big");
		}
		return api_error_raise("Sorry, an unspecified file upload error occurred");
	}

	$file_basename = basename($file['name']);
	$filename_message = htmlspecialchars($file_basename);

	if (preg_match("/\.(jpe?g|gif|png|pdf)$/i", $file_basename, $matches)) {
		$extension = $matches[1];

		$sanitize_file = api_misc_sanitize_upload_filename($file_basename);
		if (!$sanitize_file) {
			return api_error_raise("Sorry, '$filename_message' can not be sanitized.");
		} elseif ($sanitize_file != $file_basename) {
			api_templates_notify('notice', "File '$filename_message' has been renamed to '" . htmlspecialchars($sanitize_file) . "'");
		}

		$final_path_file = SAVE_LOCATION . ASSET_LOCATION . "/" . api_misc_sanitize_upload_filename($file_basename);

		if (move_uploaded_file($file['tmp_name'], $final_path_file)) {
			$file_basename = basename($final_path_file);

			$assetid = api_asset_checknameexists($file_basename);
			if ($assetid == false) {
				$assetid = api_asset_add($file_basename);
			}

			api_asset_setting_set($assetid, "md5", md5_file($final_path_file));
			api_asset_setting_set($assetid, "size", filesize($final_path_file));
			api_asset_setting_set($assetid, "type", strtolower($extension));

			if ($extension != 'pdf') {
				$imageinfo = getimagesize($final_path_file);

				if ($imageinfo !== false) {
					api_asset_setting_set($assetid, "width", $imageinfo[0]);
					api_asset_setting_set($assetid, "height", $imageinfo[1]);
					api_asset_setting_set($assetid, "mimetype", $imageinfo["mime"]);
				}
			}
		} else {
			return api_error_raise("Sorry, an unspecified error occurred while moving '$filename_message'.");
		}
	} else {
		return api_error_raise("Sorry, '$filename_message' has an invalid extension");
	}

	return $assetid;
}

/**
 * Stream asset file contents
 *
 * @param mixed $id
 * @return void|false
 */
function api_asset_stream($id) {

	if (!api_asset_checkidexists($id)) {
		return api_error_raise("Sorry, that asset file doesn't exist");
	}

	$filename = api_asset_setting_getsingle($id, "name");
	$asset_path = READ_LOCATION . ASSET_LOCATION . "/" . $filename;
	if (!is_file($asset_path)) {
		return api_error_raise("Sorry, that asset file doesn't exist");
	}

	$handle = fopen($asset_path, "r");

	if ($handle === false) {
		return api_error_raise("Sorry, that asset file doesn't exist");
	}

	header('Content-type: ' . api_email_filetype($filename));
	header('Content-Disposition: attachment; filename="' . $filename . '"');

	while (!feof($handle)) {
		print fread($handle, 1024);
	}

	fclose($handle);

	if (!api_misc_is_test_environment()) {
		exit; // @codeCoverageIgnore
	}
}

/**
 * List all assets
 *
 * @param integer $long
 * @return array
 */
function api_asset_listall($long = 0) {

	$names = api_keystore_getids("ASSET", "name", true);

	if (empty($names) || !is_array($names)) {
		return array();
	}

	if ($long) {
		$size = api_keystore_getids("ASSET", "size", true);
		$type = api_keystore_getids("ASSET", "type", true);
	}

	$assets = array();

	foreach ($names as $id => $name) {
		if ($long) {
			$assets[$id] = array("name" => $name, "size" => api_misc_sizeformat($size[$id]), "type" => $type[$id]);
		} else {
			$assets[$id] = $name;
		}
	}

	if ($long) {
		return api_misc_natcasesortbykey($assets, "name");
	}

	return $assets;
}

/**
 * Add or update asset setting
 *
 * @param mixed  $assetid
 * @param string $setting
 * @param mixed  $value
 * @return boolean
 */
function api_asset_setting_set($assetid, $setting, $value) {
	return api_keystore_set("ASSET", $assetid, $setting, $value);
}

/**
 * Delete single asset setting
 *
 * @param mixed  $assetid
 * @param string $setting
 * @return boolean
 */
function api_asset_setting_delete_single($assetid, $setting) {
	return api_keystore_delete("ASSET", $assetid, $setting);
}

/**
 * Get a single asset setting
 *
 * @param mixed  $assetid
 * @param string $setting
 * @return mixed
 */
function api_asset_setting_getsingle($assetid, $setting) {
	return api_keystore_get("ASSET", $assetid, $setting, true);
}

/**
 * Get all asset settings
 *
 * @param mixed $assetid
 * @return array|false
 */
function api_asset_setting_getall($assetid) {
	return api_keystore_getnamespace("ASSET", $assetid);
}
