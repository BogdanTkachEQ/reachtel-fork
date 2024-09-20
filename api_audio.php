<?php
/**
 * Audios Functions
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

/**
 * Add or Update audio
 *
 * @param string $name
 * @return string|false
 */
function api_audio_add($name) {

	if (api_audio_checknameexists($name)) {
		return api_error_raise("Sorry, an file with the name '" . $name . "' already exists");
	}

	$lastid = api_keystore_increment("AUDIO", 0, "nextid");

	api_audio_setting_set($lastid, "name", $name);

	return $lastid;
}

/**
 * Check if audio filename already assigned
 *
 * @param string $name
 * @param array  $options
 * @return string|false
 */
function api_audio_checknameexists($name, array $options = []) {
	return api_keystore_checkkeyexists("AUDIO", "name", $name, $options);
}

/**
 * Check if audio id already exists
 *
 * @param mixed $audioid
 * @return string|false
 */
function api_audio_checkidexists($audioid) {

	if (!is_numeric($audioid)) {
		return api_error_raise("Sorry, that is not a valid audio id");
	}

	return api_audio_setting_getsingle($audioid, "name") !== false;
}

/**
 * Delete audio
 *
 * @param mixed $audioid
 * @return string|false
 */
function api_audio_delete($audioid) {

	if (!api_audio_checkidexists($audioid)) {
		return api_error_raise("Sorry, that audio id doesn't exist");
	}

	// Unlink file
	$name = api_audio_setting_getsingle($audioid, "name");
	$audio_path = SAVE_LOCATION . AUDIO_LOCATION . "/" . $name;
	if ($name && is_file($audio_path)) {
		unlink($audio_path);
	}

	api_keystore_purge("AUDIO", $audioid);

	foreach (api_voice_servers_listall_active() as $serverid => $name) {
		api_queue_add("filesync", array("paths" => array("audio"), "servers" => array($serverid => $name)));
	}

	return true;
}

/**
 * Check the constant bitrate (CBR)
 *
 * @param string $path
 *
 * @return boolean
 */
function api_audio_check_cbr($path) {
	if (!file_exists($path)) {
		return api_error_raise("Sorry, that audio file doesn't exist");
	}

	$infos = api_audio_information($path);

	return ($infos
		&& 1 === $infos['channels'] // 1 channel
		&& 8000 === $infos['samplerate'] // 8kHz sample rate
		&& '16-bit' === $infos['precision'] // 16-bit bps
	);
}

/**
 * Upload an audio file
 *
 * @param array $file
 *
 * @return integer
 */
function api_audio_fileupload(array $file) {

	if (!isset($file['error']) || $file['error']) {
		if (isset($file['error']) && $file['error'] == 2) {
			return api_error_raise("Sorry, that file is too big");
		}
		return api_error_raise("Sorry, an unspecified file upload error occurred");
	}

	$file_basename = basename($file['name']);
	$filename_message = htmlspecialchars($file_basename);

	if (preg_match("/\.(mp3|wav)$/i", $file_basename, $matches)) {
		$extension = $matches[1];
		$tmp_name = $file['tmp_name'];

		$sanitize_file = api_misc_sanitize_upload_filename($file_basename);
		if (!$sanitize_file) {
			return api_error_raise("Sorry, '$filename_message' can not be sanitized.");
		} elseif ($sanitize_file != $file_basename) {
			api_templates_notify('notice', "File '$filename_message' has been renamed to '" . htmlspecialchars($sanitize_file) . "'");
		}

		$final_path_file = substr(SAVE_LOCATION . AUDIO_LOCATION . "/" . api_misc_sanitize_upload_filename($file_basename), 0, -3) . 'wav';

		// if content isn't passed with file, ignore the transcription
		// (this is unnecessarily defensive as this function is only called in one place,
		// but better safe than sorry)
		$audio_content = '';
		if (array_key_exists('content', $file)) {
			// if file content was passed but is empty, call the transcribe
			$audio_content = $file['content'];
			if (!$audio_content) {
				// convert to flac and trim
				$trimmed_name = $file['tmp_name'] . '-TRIMMED.flac';
				passthru("sox -V1 -t {$extension} '{$tmp_name}' -b 16 -c 1 -r 8000 -t flac '{$trimmed_name}' trim 0 0:15");

				$options = [
					'filename' => $trimmed_name,
					'encoding' => 'FLAC',
				];
				$result = api_misc_speechrecognition($options);
				if ($result) {
					$audio_content = $result['utterance'] . ' (automatically transcribed)';
				}
				unlink($trimmed_name);
			}
		}

		$processed_name = $file['tmp_name'] . '-PROCESSED.wav';
		if (!api_audio_check_cbr($tmp_name)) {
			passthru("sox -V1 -t {$extension} '{$tmp_name}' -b 16 -c 1 -r 8000 -t wav '{$processed_name}'");
			rename($processed_name, $file['tmp_name']);
		}

		if (move_uploaded_file($file['tmp_name'], $final_path_file)) {
			$filename = basename($final_path_file);

			$audioid = api_audio_checknameexists($filename);
			if ($audioid == false) {
				$audioid = api_audio_add($filename);
			}

			chmod($final_path_file, 0664);

			api_audio_setting_set($audioid, "md5", md5_file($final_path_file));
			api_audio_setting_set($audioid, "size", filesize($final_path_file));
			api_audio_setting_set($audioid, "content", $audio_content);
		} else {
			return api_error_raise("Sorry, an unspecified error occurred while moving '$filename_message'.");
		}
	} else {
		return api_error_raise("Sorry, '$filename_message' is not a valid WAV or MP3 file");
	}

	return $audioid;
}

/**
 * Stream audio file contents
 *
 * @param mixed $id
 * @return void|false
 */
function api_audio_stream($id) {

	if (!api_audio_checkidexists($id)) {
		return api_error_raise("Sorry, that audio file doesn't exist");
	}

	$filename = api_audio_setting_getsingle($id, "name");
	$audio_path = READ_LOCATION . AUDIO_LOCATION . "/" . $filename;
	if (!is_file($audio_path)) {
		return api_error_raise("Sorry, that audio file doesn't exist");
	}

	$handle = fopen($audio_path, "r");

	if ($handle === false) {
		return api_error_raise("Sorry, that audio file doesn't exist");
	}

	header('Content-type: application/wav');
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
 * Get audio file information
 *
 * @param string $filename
 * @return array|false
 */
function api_audio_information($filename) {

	if (!is_readable($filename)) {
		return api_error_raise("Sorry, that audio file isn't readable");
	}

	// @see REACHTEL-689
	// Need to redirects STDERR to STDIN (using 2>&1) to catch SOX errors
	// Otherwise STDERR in printed at runtime, and is not in $result
	exec("soxi '" . escapeshellarg($filename) . "' 2>&1", $result);

	$out = implode("\n", $result);
	if (!$result || !preg_match("/Input File\s+:/", $out)) {
		return api_error_raise("Sorry, that is not a valid audio file: {$out}");
	}

	// Set some defaults
	$audio = [
		'channels' => 'unknown',
		'samplerate' => 'unknown',
		'precision' => 'unknown',
		'duration' => 'unknown',
		'filesize' => 'unknown',
		'bitrate' => 'unknown',
		'sampleencoding' => 'unknown',
	];

	foreach ($result as $line) {
		if (preg_match("/([^:]+) ?: ?(.+)/", $line, $matches)) {
			switch (trim($matches[1])) {
				case 'Channels':
					$audio['channels'] = (int)$matches[2];
					break;
				case 'Sample Rate':
					$audio['samplerate'] = (int)$matches[2];
					break;
				case 'Precision':
					$audio['precision'] = $matches[2];
					break;
				case 'Duration':
					$audio['duration'] = $matches[2];
					break;
				case 'File Size':
					$audio['filesize'] = $matches[2];
					break;
				case 'Bit Rate':
					$audio['bitrate'] = $matches[2];
					break;
				case 'Sample Encoding':
					$audio['sampleencoding'] = $matches[2];
					break;
			}
		}
	}
	return $audio;
}

/**
 * List all audios
 *
 * @param array $options
 * @return array
 */
function api_audio_listall(array $options = []) {

	$type = "AUDIO";

	if (isset($options["countonly"]) && $options["countonly"]) {
		$sql = "SELECT COUNT(`id`) as `count` FROM `key_store` WHERE `type` = ? AND `item` = ?";
	} else {
		$sql = "SELECT `id`, `value` FROM `key_store` WHERE `type` = ? AND `item` = ?";
	}

	$parameters = array($type, "name");

	if (isset($options["search"]) && !empty($options["search"])) {
		if (strpos($options["search"], "*") !== false) {
			$options["search"] = str_replace("\\*", ".+", preg_quote($options["search"], "/"));
		} else {
			$options["search"] = preg_quote($options["search"], "/");
		}

		if (empty($options["searchfields"])) {
			$sql .= " AND `value` REGEXP ?";
			array_push($parameters, $options["search"]);
		} elseif (is_array($options["searchfields"])) {
			$sql .= " AND (0 ";

			foreach ($options["searchfields"] as $searchfield) {
				$sql .= " OR `id` IN (SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` REGEXP ?)";
				array_push($parameters, $type, $searchfield, $options["search"]);
			}

			$sql .= ")";
		}
	}

	if (!isset($options["orderby"]) || !in_array($options["orderby"], array("id", "value", "length"))) {
		$options["orderby"] = "value";
	}

	if ($options["orderby"] == "length") { // This is suitable for finding a closest match
		$sql .= " ORDER BY LENGTH(`value`)";
	} else {
		$options["orderby"] = filter_var($options["orderby"], FILTER_SANITIZE_STRING); // filter_var is used to fix fortify issues
		$sql .= " ORDER BY `" . $options["orderby"] . "`";
	}

	if (!isset($options["order"]) || !in_array($options["order"], array("ASC", "DESC"))) {
		$options["order"] = "ASC";
	}

	$sql .= " " . $options["order"];

	if (!empty($options["limit"]) && (!isset($options["countonly"]) || (!$options["countonly"]))) {
		$sql .= " LIMIT ?";
		array_push($parameters, (int)$options["limit"]);

		if (isset($options["offset"]) && is_numeric($options["offset"])) {
			$sql .= " OFFSET ?";
			array_push($parameters, (int)$options["offset"]);
		}
	}

	$rs = api_db_query_read($sql, $parameters);

	if (isset($options["countonly"]) && $options["countonly"]) {
		return $rs->Fields("count");
	}

	if ($rs && ($rs->RecordCount() > 0)) {
		$results = $rs->GetAssoc();

		if (isset($options["short"]) && $options["short"]) {
			return array_keys($results);
		} else {
			foreach ($results as $id => $name) {
				$results[$id] = api_audio_setting_getall($id);
			}
			return $results;
		}
	} else {
		return array();
	}
}

/**
 * Add or update audio setting
 *
 * @param mixed  $audioid
 * @param string $setting
 * @param string $value
 * @return boolean
 */
function api_audio_setting_set($audioid, $setting, $value) {
	return api_keystore_set("AUDIO", $audioid, $setting, $value);
}

/**
 * Delete single audio setting
 *
 * @param mixed  $audioid
 * @param string $setting
 * @return boolean
 */
function api_audio_setting_delete_single($audioid, $setting) {
	return api_keystore_delete("AUDIO", $audioid, $setting);
}

/**
 * Get a single audio setting
 *
 * @param mixed  $audioid
 * @param string $setting
 * @return mixed
 */
function api_audio_setting_getsingle($audioid, $setting) {
	return api_keystore_get("AUDIO", $audioid, $setting);
}

/**
 * Get all audio settings
 *
 * @param mixed $audioid
 * @return mixed
 */
function api_audio_setting_getall($audioid) {
	return api_keystore_getnamespace("AUDIO", $audioid);
}

/**
 * Get a audio tag values
 *
 * @param integer $id
 * @param mixed   $tags
 * @return array|false
 */
function api_audio_tags_get($id, $tags = null) {

	if (!api_audio_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid id.");
	}

	return api_tags_get('AUDIO', $id, $tags);
}

/**
 * Set audio tag values
 *
 * @param integer $id
 * @param array   $tags
 * @param array   $encrypt_tags
 * @return boolean
 */
function api_audio_tags_set($id, array $tags, array $encrypt_tags = []) {

	if (!api_audio_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid id.");
	}

	return api_tags_set('AUDIO', $id, $tags, $encrypt_tags);
}

/**
 * Delete audio tags
 *
 * @param integer $id
 * @param array   $tags
 * @return boolean
 */
function api_audio_tags_delete($id, array $tags = []) {

	if (!api_audio_checkidexists($id)) {
		return api_error_raise("Sorry, that is not a valid id.");
	}

	return api_tags_delete('AUDIO', $id, $tags);
}
