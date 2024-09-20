<?php

// Generate uniqueID

function api_misc_uniqueid($serial = "uniqueid") {

	return api_keystore_increment("SYSTEM", 0, $serial);
}

function api_misc_debug($value){ if(SPOOLER_DEBUG) print $value; }

// Page load times

function api_misc_loadtime_start(){

	global $pageLoadTimeStart;

	$pageLoadTimeStart = microtime();
	$startarray = explode(" ", $pageLoadTimeStart);
	$pageLoadTimeStart = $startarray[1] + $startarray[0];

}

function api_misc_loadtime_end(){

	global $pageLoadTimeStart;

	$endtime = microtime();
	$endarray = explode(" ", $endtime);
	$pageLoadTimeEnd = $endarray[1] + $endarray[0];
	$totaltime = $pageLoadTimeEnd - $pageLoadTimeStart;
	return round($totaltime,4)*1000;

}

function api_misc_getip() {

	if (!empty($_SERVER["HTTP_CLIENT_IP"]) AND api_misc_validip($_SERVER["HTTP_CLIENT_IP"])) {

		return $_SERVER["HTTP_CLIENT_IP"];

	}

	if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
	foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {

		if (api_misc_validip(trim($ip))) {

			return $ip;

		}

	}

	if (!empty($_SERVER["HTTP_X_FORWARDED"]) AND api_misc_validip($_SERVER["HTTP_X_FORWARDED"])) {

		return $_SERVER["HTTP_X_FORWARDED"];

	} elseif (!empty($_SERVER["HTTP_FORWARDED_FOR"]) AND api_misc_validip($_SERVER["HTTP_FORWARDED_FOR"])) {

		return $_SERVER["HTTP_FORWARDED_FOR"];

	} elseif (!empty($_SERVER["HTTP_FORWARDED"]) AND api_misc_validip($_SERVER["HTTP_FORWARDED"])) {

		return $_SERVER["HTTP_FORWARDED"];

	} elseif (!empty($_SERVER["HTTP_X_FORWARDED"]) AND api_misc_validip($_SERVER["HTTP_X_FORWARDED"])) {

		return $_SERVER["HTTP_X_FORWARDED"];

	} elseif(!empty($_SERVER["REMOTE_ADDR"])){

		return $_SERVER["REMOTE_ADDR"];

	} else return false;
}

function api_misc_validip($ip) {

	if (!empty($ip) && ip2long($ip)!=-1) {

		$reserved_ips = array (

			array('0.0.0.0','2.255.255.255'),

			array('10.0.0.0','10.255.255.255'),

			array('127.0.0.0','127.255.255.255'),

			array('169.254.0.0','169.254.255.255'),

			array('172.16.0.0','172.31.255.255'),

			array('192.0.2.0','192.0.2.255'),

			array('192.168.0.0','192.168.255.255'),

			array('255.255.255.0','255.255.255.255')

			);


		foreach ($reserved_ips as $r) {

			$min = ip2long($r[0]);

			$max = ip2long($r[1]);

			if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;

		}

		return true;

	} else {

		return false;

	}
}

function api_misc_ipgetrr($ip){

	$host = gethostbyaddr($ip);

	if (empty($host)) return false;
	else return $host;

}

function api_misc_array_search_in($needle, $array){

	if(empty($needle)) return false;

	if(!is_array($array)) return false;

	foreach($array as $key => $value) if(strtolower($needle) == strtolower($value)) return $key;

	return false;

}

function api_misc_audit($action, $value = null, $userid = null){

	if((!preg_match("/^[0-9]+$/", $userid)) AND ($userid != NULL)) return false;
	if(strlen($action) > 255) return false;
	if(strlen($value) > 1024) return false;
	$ip = api_misc_getip();

	$msg = "<22>Morpheus " . $_SERVER['SCRIPT_FILENAME'] . ":";

	if(!empty($ip)) $msg .= " IP: " . $ip . ";";
	if(!empty($userid)) $msg .=  " User ID: " . $userid . ";";
	if(!empty($action)) $msg .= " Action: " . $action . ";";
	if(!empty($action)) $msg .= " Value: " . $value . ";";

	syslog(LOG_WARNING, $msg);

	return true;
}

function api_misc_metrics_submit($metrics = array(), $options = array()){

	if(!is_array($metrics)) return api_error_raise("Sorry, that is not a valid metric");

	// DO NOT SEND METRICS FOR DEV OR TEST ENV. Same in StatsD::send()
	if (api_misc_is_test_environment()) {
		return true;
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, METRICS_LIBRATO_URL);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metrics));
	curl_setopt($ch, CURLOPT_TIMEOUT, SMS_TIMEOUT);
	curl_setopt($ch, CURLOPT_USERPWD, METRICS_LIBRATO_AUTHENTICATION);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	$result = curl_exec($ch);

	$info = curl_getinfo($ch);

	curl_close($ch);

	if($info["http_code"] == 200) return true;
	else return api_error_raise("Failed to submit metrics. HTTP response=" . $info["http_code"] . ";");

}

function api_misc_crypt($text, $key = CRYPTO_KEY, $iv = CRYPTO_IV) {

	$iv = base64_decode($iv);

	$td = mcrypt_module_open('rijndael-128', '', 'cbc', '');

	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	mcrypt_generic_init($td, $key, $iv);

	$encrypted_data = mcrypt_generic($td, strlen($text) . "|" . $text);

	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	return $encrypted_data;
}

function api_misc_decrypt($text, $key = CRYPTO_KEY, $iv = CRYPTO_IV) {

	$iv = base64_decode($iv);

	$td = mcrypt_module_open('rijndael-128', '', 'cbc', '');

	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	mcrypt_generic_init($td, $key, $iv);

	if(empty($text)) return false;

	$encrypted_data = @mdecrypt_generic($td, $text);

	if(strpos($encrypted_data, "|") === FALSE) return false;

	list($length, $padded_data) = explode('|', $encrypted_data, 2);
	$encrypted_data = substr($padded_data, 0, (integer)$length);

	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);

	return $encrypted_data;
}

function api_misc_crypt_safe($text, $key = CRYPTO_KEY, $iv = CRYPTO_IV) {
	return bin2hex(api_misc_crypt($text, $key, $iv));
}

function api_misc_decrypt_safe($text, $key = CRYPTO_KEY, $iv = CRYPTO_IV) {
	return api_misc_decrypt(@pack("H*",$text), $key, $iv);
}

function api_misc_crypt_base64($text, $key = CRYPTO_KEY, $iv = CRYPTO_IV) {
	return base64_encode(api_misc_crypt($text, $key, $iv));
}

function api_misc_decrypt_base64($text, $key = CRYPTO_KEY, $iv = CRYPTO_IV) {
	return api_misc_decrypt(base64_decode($text), $key, $iv);
}

function api_misc_encode($text) { return bin2hex($text); }

function api_misc_decode($text) { return pack("H*", $text); }

function api_misc_sizeformat($size){

	if(!is_numeric($size)) return "n/a";
	if($size < 1024) return sprintf("%01.0f", $size) . " B"; 		// Less than 1kB return bytes
	if($size < 51200) return sprintf("%01.1f", $size/1024) . " KB";		// Less than 50kB return kilobytes and one sig digit
	if($size < 1024768) return sprintf("%01.0f", $size/1024) . " KB";	// Less than 1mB return kilobytes and no sig digits
	if($size < 5242880) return sprintf("%01.1f", $size/1024768) . " MB";	// Less than 5mB return megabytes and one sig digit
	else return sprintf("%01.0f", $size/1024768) . " MB";			// Else return megabytes and no sig digits

}

function api_misc_timeformat($time){

	if(!is_numeric($time)) $time = strtotiome($time);

	if($time == false) return api_error_raise("Sorry, that is an invalid time");

	$difference = time() - $time;

	if($difference < 0) {
		$pre = "in ";
		$post = "";
		$difference * -1;
	} else {
		$pre = "";
		$post = " ago";

	}


	if ($difference == 0) return "now";
	elseif ($difference < 60) return $difference == 1 ? $pre . "a second" . $post : $pre . $difference . " seconds" . $post;
	elseif ($difference < 120) return $pre . "a minute" . $post;
	elseif ($difference < 2700) return $pre . floor($difference/60) . " minutes" . $post;
	elseif ($difference < 5400) return $pre . "an hour" . $post;
	elseif ($difference < 86400) return round($difference/3600) == 1 ? $pre . "an hour" . $post : $pre . round($difference/3600) . " hours" . $post;
	elseif ($difference < 172800) return $pre . "a day" . $post;
	elseif ($difference < 2592000) return $pre . floor($difference/86400) . " days" . $post;
	elseif ($difference < 31104000){
		$months = floor(($difference/86400)/30);
		return ($months <= 1) ? $pre . "a month" . $post : $pre . $months . " months" . $post;
	} else {
		$years = floor(($difference/86400)/365);
		return ($years <= 1) ? $pre . "a year" . $post : $pre . $years . " years" . $post;

	}

}

function api_misc_zipfile($array){

	$uniqueid = uniqid();

	$zip = new ZipArchive();

	if ($zip->open("/tmp/" . $uniqueid . ".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE)!==TRUE) return false;
	$zip->addFromString($array["filename"], $array["content"]);
	$zip->close();
	$zip = null;

	$contents = file_get_contents("/tmp/" . $uniqueid . ".zip");
	unlink("/tmp/" . $uniqueid . ".zip");

	return array("content" => $contents, "filename" => $array["filename"] . ".zip");
}

function api_misc_namesearch($names, $needle, $startonly = false, $searchkey = false){

	if(!is_array($names)) return api_error_raise("Sorry, I need something to search through");
	if(empty($needle)) return api_error_raise("Sorry, I need something to search for");
	if(is_array($needle)) return api_error_raise("Sorry, that is not a valid search string");

	$needle = str_replace("\\*", ".+", preg_quote($needle, "/"));

	if($startonly == TRUE) $startonly = "^";

	$matched = array();

	foreach($names as $key => $name) {

		if(!empty($searchkey) AND isset($name[$searchkey])) $search = $name[$searchkey];
		elseif(!empty($searchkey) AND !isset($name[$searchkey])) continue;
		else $search = $name;

		if(preg_match("/" . $startonly . $needle . "/i", $search)) $matched[$key] = $name;
	}

	krsort($matched);

	return $matched;

}

function api_misc_natcasesortbykey($array, $key = "name"){

	if(!is_array($array) OR empty($key)) {
		return api_error_raise("Sorry, I can't sort that list");
	}

	$tosort = array();
	$sorted = array();

	$cpt = 0;
	foreach($array as $k => $v) {
		if (is_array($v) && array_key_exists($key, $v)) {
			$tosort[$k] = $v[$key];
		} else {
			$tosort[$k] = null;
			$cpt++;
		}
	}

	// nothing to sort
	if (count($array) === $cpt) {
		return $array;
	}

	natcasesort($tosort);

	foreach($tosort as $k => $v) {
		$sorted[$k] = $array[$k];
	}

	return $sorted;

}


function api_misc_texttospeech($text, $voice = "ScanSoft Karen_Full_22kHz"){

	switch($voice) {
		case "ScanSoft Karen_Full_22kHz":
			$lang = "en-AU";
			$font = "Microsoft Server Speech Text to Speech Voice (en-AU, Catherine)";
			break;
		case "ScanSoft Lee_Full_22kHz":
			$lang = "en-AU";
			$font = "Microsoft Server Speech Text to Speech Voice (en-GB, George, Apollo)";
			break;
		case "Vocalizer Expressive Tian-tian Premium High 22kHz":
			$lang = "zh-CN";
			$font = "Microsoft Server Speech Text to Speech Voice (zh-TW, Yating, Apollo)";
			break;
		case "Vocalizer Expressive Sin-ji Premium High 22kHz":
			$lang = "zh-CN";
			$font = "Microsoft Server Speech Text to Speech Voice (zh-CN, Yaoyao, Apollo)";
			break;
		default:
			$lang = "en-AU";
			$font = "Microsoft Server Speech Text to Speech Voice (en-AU, Catherine)";
			break;
	}

	$AccessTokenUri = "https://australiaeast.api.cognitive.microsoft.com/sts/v1.0/issueToken";

	// use key 'http' even if you send the request to https://...
	$headers = [
		"Ocp-Apim-Subscription-Key: " . AZURE_SUBSCRIPTION_KEY,
		"Content-length: 0",
	];

	//get the Access Token
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $AccessTokenUri);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	$access_token = curl_exec($ch);

	if (!$access_token) {
		return api_error_raise("Problem with $AccessTokenUri, $php_errormsg");
	}

	$ttsServiceUri = "https://australiaeast.tts.speech.microsoft.com/cognitiveservices/v1";

	$doc = new DOMDocument();

	$root = $doc->createElement( "speak" );
	$root->setAttribute( "version" , "1.0" );
	$root->setAttribute( "xml:lang" , $lang );

	$voice = $doc->createElement( "voice" );
	$voice->setAttribute( "xml:lang" , $lang );
	$voice->setAttribute( "name" , $font );

	$text = $doc->createTextNode( $text );

	$prosody = $doc->createElement( "prosody" );
	$prosody->setAttribute( "rate", '-10.00%' ); // Slow it down a little

	$prosody->appendChild( $text );
	$voice->appendChild( $prosody );

	$root->appendChild( $voice );
	$doc->appendChild( $root );
	$data = $doc->saveXML();

	$headers = [
		"Content-type: application/ssml+xml",
		"X-Microsoft-OutputFormat: riff-16khz-16bit-mono-pcm",
		"Authorization: Bearer " . $access_token,
		"User-Agent: TTSPHP",
		"content-length: ".strlen($data),
	];

	//get the Access Token
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $ttsServiceUri);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

	return curl_exec($ch);

}

/**
 * @param array $options
 * @return false|array
 * @see https://cloud.google.com/speech-to-text/docs/reference/rest/v1/speech/recognize
 */
function api_misc_speechrecognition($options = []) {

	if (api_misc_is_test_environment()) {
		// Disable speech recognition for test environment
		return [
			'confidence' => (rand(7, 10) * 0.1), // between 0.7 and 1
			'utterance' => 'You are using the test environment'
		];
	}

	if(empty($options["filename"]) || !is_readable($options["filename"]) || !($audio = api_audio_information($options["filename"])) || ($audio["precision"] != "16-bit")) {
		return api_error_raise("Sorry, that is not a valid audio file");
	}

	$request = [
		'config' => [
			'encoding' => (!empty($options["encoding"])) ? $options['encoding'] : 'LINEAR16',
			'sampleRateHertz' => $audio["samplerate"],
			'languageCode' => (!empty($options["language"])) ? : 'en-AU',
		],
		'audio' => [
			'content' => base64_encode(file_get_contents($options['filename']))
		],
	];

	if(isset($options["phrasehints"]) && is_array($options["phrasehints"])) {
		$request["config"]["speechContexts"][]["phrases"] = $options["phrasehints"];
	}

	$ch = curl_init();

	$choptions = [
		CURLOPT_URL => 'https://speech.googleapis.com/v1/speech:recognize?key=' . SPEECHRECOGNITION_GOOGLE_KEY,
		CURLOPT_POSTFIELDS => json_encode($request),
		CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
		CURLOPT_REFERER => 'https://morpheus.reachtel.com.au',
		CURLOPT_TIMEOUT => 120,
		CURLOPT_RETURNTRANSFER => true,
	];

	if(defined("PROXY_EXTERNAL")) $choptions[CURLOPT_PROXY] = PROXY_EXTERNAL;

	curl_setopt_array($ch, $choptions);

	$response = curl_exec($ch);

	$info = curl_getinfo($ch);

	if(!empty($response) && ($json_response = json_decode($response)) && !empty($json_response->results[0]->alternatives[0]->confidence)) {
		return [
			'confidence' => $json_response->results[0]->alternatives[0]->confidence,
			'utterance' =>  $json_response->results[0]->alternatives[0]->transcript,
		];
	} else {
		return false;
	}
}

function api_misc_pgp_encrypt($file, $keys){

	$keyids = array();

	$keys = trim($keys);

	if(preg_match("/,/", $keys)){

		$keys = array_unique(explode(",", $keys));

		if(is_array($keys))
		foreach($keys as $key) {

			$key = trim($key);

			if(preg_match("/^[0-9a-z]{8}$/i", $key)) $keyids[] = "0x" . $key;
			elseif(preg_match("/^0x[0-9a-z]{8}$/i", $key)) $keyids[] = $key;
			else return api_error_raise("Sorry, that is not a valid PGP key fingerprint.");
		}

	} elseif(preg_match("/^[0-9a-z]{8}$/i", $keys)) $keyids[] = "0x" . $keys;
	elseif(preg_match("/^0x[0-9a-z]{8}$/i", $keys)) $keyids[] = $keys;
	else return api_error_raise("Sorry, that is not a valid PGP key fingerprint.");

	if(empty($keyids)) return api_error_raise("Sorry, there are no PGP key fingerprints to encrypt for");

	putenv('GNUPGHOME=' . SAVE_LOCATION . '/pgp');

	$gpg = new gnupg();

	foreach($keyids as $keyid){

// First check if we already have the key in our key chain

		$result = $gpg->keyinfo($keyid);

		if(isset($result[0]["subkeys"][0]["fingerprint"]) AND $gpg->addencryptkey($result[0]["subkeys"][0]["fingerprint"])) continue;

// If not, see if we can add it from a key server

		if(!api_misc_pgp_importkey($keyid)) return api_error_raise("Sorry, we weren't able to import the PGP keyid " . $keyid);

		$result = $gpg->keyinfo($keyid);

		if(isset($result[0]["subkeys"][0]["fingerprint"]) AND $gpg->addencryptkey($result[0]["subkeys"][0]["fingerprint"])) continue;

// We don't have the key in our key chain and we couldn't get it from a key server so fail

		return api_error_raise("Sorry, we weren't able to import the PGP keyid " . $keyid);

	}

	$file["content"] = $gpg->encrypt($file["content"]);
	$file["filename"] = $file["filename"] . ".pgp";

	if($file["content"] == false) return api_error_raise("Sorry, we couldn't PGP encrypt that content");
	else return $file;

}

function api_misc_pgp_decrypt($content){

	putenv('GNUPGHOME=' . SAVE_LOCATION . '/pgp');

	$gpg = new gnupg();

	$keys = trim(api_system_tags_get("pgp-systemkeys"));

	if(preg_match("/,/", $keys)){

		$keys = array_unique(explode(",", $keys));

		if(is_array($keys))
		foreach($keys as $key) {

			$key = trim($key);

			if(preg_match("/^[0-9a-z]{8}$/i", $key)) $keyids[] = "0x" . $key;
			elseif(preg_match("/^0x[0-9a-z]{8}$/i", $key)) $keyids[] = $key;
			else return api_error_raise("Sorry, that is not a valid PGP key fingerprint.");
		}

	} elseif(preg_match("/^[0-9a-z]{8}$/i", $keys)) $keyids[] = "0x" . $keys;
	elseif(preg_match("/^0x[0-9a-z]{8}$/i", $keys)) $keyids[] = $keys;
	else return api_error_raise("Sorry, that is not a valid PGP key fingerprint.");

	if(!is_array($keyids)) return api_error_raise("Sorry, we can't find any PGP keys to decrypt the content");

	foreach($keyids as $keyid){

		$result = $gpg->keyinfo($keyid);

		if(isset($result[0]["can_encrypt"]) AND $result[0]["can_encrypt"] AND isset($result[0]["subkeys"][0]["fingerprint"]) AND $gpg->adddecryptkey($result[0]["subkeys"][0]["fingerprint"], hash_hmac("sha256", $keyid, "morpheus::pgp"))) continue;

// If not, see if we can add it from the PGP directory

		if(!api_misc_pgp_importkey($keyid)) return api_error_raise("Sorry, we weren't able to import the PGP keyid " . $keyid);

		$result = $gpg->keyinfo($keyid);

		if(isset($result[0]["subkeys"][0]["fingerprint"]) AND $gpg->adddecryptkey($result[0]["subkeys"][0]["fingerprint"], hash_hmac("sha256", $keyid, "morpheus::pgp"))) continue;

// We don't have the key in our key chain and we couldn't get it from a key server so fail

		return api_error_raise("Sorry, we weren't able to import the PGP keyid " . $keyid);

	}

	$decrypted = $gpg->decrypt($content);

	if(!$decrypted) return api_error_raise("Sorry, we were unable to decrypt that content");
	else return $decrypted;

}

function api_misc_pgp_importkey($keyid, $keydata = null){

	if(empty($keyid)) return api_error_raise("Sorry, that is not a valid PGP key id");

	if(preg_match("/^[0-9A-F]{8}$/i", $keyid)) $keyid = "0x" . $keyid;

	if(!preg_match("/^0x[0-9A-F]{8}$/i", $keyid)) return api_error_raise("Sorry, that is not a valid PGP key id");

	putenv('GNUPGHOME=' . SAVE_LOCATION . '/pgp');

	$gpg = new gnupg();

	// Check if the key id is already in our key ring
	$result = $gpg->keyinfo($keyid);

	if(isset($result[0]["can_encrypt"]) && $result[0]["can_encrypt"] && isset($result[0]["subkeys"][0]["fingerprint"])) {
		return true;
	}

	if(!empty($keydata)) {

		$result = $gpg->import($keydata);

		if(!empty($result["fingerprint"])) return true;
		else return api_error_raise("Sorry, we couldn't import that PGP key");

	} elseif(is_readable(SAVE_LOCATION . "/pgp/" . $keyid . ".asc")){

		$keydata = file_get_contents(SAVE_LOCATION . "/pgp/" . $keyid . ".asc");

		if($keydata == false) return api_error_raise("Sorry, we couldn't read the PGP key file for " . $keyid);

		$result = $gpg->import($keydata);

		if(!empty($result["fingerprint"])) return true;
		else return api_error_raise("Sorry, we couldn't import that PGP key");

	} else {

		if(defined("PROXY_EXTERNAL")) $proxy = "--keyserver-options http-proxy=" . PROXY_EXTERNAL;
		else $proxy = "";

		$command = "gpg --no-permission-warning --homedir " . SAVE_LOCATION . "/pgp/ --no-verbose --quiet --yes --keyserver pool.sks-keyservers.net " . $proxy . " --recv-key " . $keyid . " 2>&1";

		$result = exec($command, $output, $retval);

		if($retval == 0) return true;
		else return api_error_raise("Sorry, we couldn't get that PGP key from a key server");
	}

}

function api_misc_competitions_getcompetitions(){

	$sql = "SELECT DISTINCT `competition` FROM `competition_entries`";
	$rs = api_db_query_read($sql);

	$competitions = array();

	while(!$rs->EOF){

		$competitions[] = $rs->Fields("competition");

		$rs->MoveNext();

	}

	return $competitions;

}

function api_misc_competitions_add($competition, $from, $content){

	if(empty($competition) OR (strlen($competition) > 100)) return api_error_raise("Sorry, the competition name must be between 1 and 100 characters");

	if(!is_numeric($from) OR (strlen($from) > 20)) return api_error_raise("Sorry, the entry number must be between 1 and 20 characters in length");

	$sql = "INSERT INTO `competition_entries` (`timestamp`, `competition`, `number`, `entry`) VALUES (NOW(), ?, ?, ?)";
	$rs = api_db_query_write($sql, array($competition, $from, $content));

	if($rs) return api_db_lastid();
	else return false;

}

function api_misc_competitions_get_count($competition, $startdate = null, $enddate = null, $number = null){

	if(!preg_match("/^[a-z0-9_\- ]{1,100}$/i", $competition)) return api_error_raise("Sorry, that is not a valid competition name");

	$sql = "SELECT COUNT(`entryid`) AS `count` FROM `competition_entries` WHERE `competition` = ?";

	$variables = array($competition);

	if(!empty($startdate) OR !empty($enddate)){

		$startdate = strtotime($startdate);
		$enddate = strtotime($enddate);

		if($startdate == false) return api_error_raise("Sorry, that is not a valid start date");
		elseif($enddate == false) return api_error_raise("Sorry, that is not a valid end date");
		elseif($startdate > $enddate) return api_error_raise("Sorry, the end date cannot be before the start date");

		$sql .= " AND `timestamp` > ? AND `timestamp` < ?";
		$variables = array_merge($variables, array(date("Y-m-d H:i:s", $startdate), date("Y-m-d H:i:s", $enddate)));

	}

	if(is_numeric($number)) {

		$sql .= " AND `number` = ?";
		$variables = array_merge($variables, array($number));

	}

	$rs = api_db_query_read($sql, $variables);


	if($rs->RecordCount() > 0) return $rs->Fields("count");
	else return 0;

}


function api_misc_competitions_getentries($competition, $startdate = null, $enddate = null){

	if(!preg_match("/^[a-z0-9_\- ]{1,100}$/i", $competition)) return api_error_raise("Sorry, that is not a valid competition name");

	$sql = "SELECT * FROM `competition_entries` WHERE `competition` = ?";

	$variables = array($competition);

	if(($startdate !== null) OR ($enddate !== null)){

		$startdate = strtotime($startdate . " 00:00:00");
		$enddate = strtotime($enddate . " 23:59:59");

		if($startdate == false) return api_error_raise("Sorry, that is not a valid start date");
		elseif($enddate == false) return api_error_raise("Sorry, that is not a valid end date");
		elseif($startdate > $enddate) return api_error_raise("Sorry, the end date cannot be before the start date");

		$sql .= " AND `timestamp` > ? AND `timestamp` < ?";
		$variables = array_merge($variables, array(date("Y-m-d H:i:s", $startdate), date("Y-m-d H:i:s", $enddate)));

	}

	$rs = api_db_query_read($sql, $variables);

	$entries = array();

	if($rs->RecordCount() > 0){

		$results = $rs->GetArray();

		foreach($results as $entry) $entries[] = array("timestamp" => $entry["timestamp"], "number" => $entry["number"], "entry" => $entry["entry"]);

		return $entries;

	} else return $entries;

}

function api_misc_profiling_save(){

	global $PROFILE_STARTED;

	if(!isset($PROFILE_STARTED)) return true;

	if(defined('PROFILE_USE')) $profileuse = PROFILE_USE;
	else $profileuse = "morpheus";

	$xhprof_data = xhprof_disable();

	if($xhprof_data == false) return false;

	include_once __DIR__ . "/lib/xhprof_lib/utils/xhprof_lib.php";
	include_once __DIR__ . "/lib/xhprof_lib/utils/xhprof_runs.php";

	$xhprof_runs = new XHProfRuns_Default();
	$run_id = $xhprof_runs->save_run($xhprof_data, $profileuse);

	unset($PROFILE_STARTED);

	return $run_id;

}

function api_misc_htmltopdf($html){


	if(empty($html)) return api_error_raise("Sorry, there is no content");

	$uniqueid = uniqid();

	$result = file_put_contents("/tmp/morpheus-htmltopdf-" . $uniqueid . ".html", $html);

	if(!$result) return api_error_raise("Unable to write temporary HTML file for conversion");

	if(defined("PROXY_EXTERNAL")) $proxy = "--proxy " . PROXY_EXTERNAL;
	else $proxy = "";

	exec(__DIR__  . "/bin/wkhtmltopdf " . $proxy . " /tmp/morpheus-htmltopdf-" . $uniqueid . ".html /tmp/morpheus-htmltopdf-" . $uniqueid . ".pdf", $output, $returnvar);

	if($returnvar) return api_error_raise("Fatal error converting HTML to PDF");

	$pdf = file_get_contents("/tmp/morpheus-htmltopdf-" . $uniqueid . ".pdf");

	unlink("/tmp/morpheus-htmltopdf-" . $uniqueid . ".html");
	unlink("/tmp/morpheus-htmltopdf-" . $uniqueid . ".pdf");

	return $pdf;
}

function api_misc_generatebpayref($number) {

	$number = preg_replace("/\D/", "", $number);

	if(!is_numeric($number)) return api_error_raise("Invalid reference number");

	if($number <= 0) return api_error_raise("Invalid reference number");

// Get the length of the seed number
	$length = strlen($number);

	$total = 0;

// For each character in seed number, sum the character multiplied by its one based array position (instead of normal PHP zero based numbering)
	for($i = 0; $i < $length; $i++) $total += $number{$i} * ($i + 1);

// The check digit is the result of the sum total from above mod 10
		$checkdigit = fmod($total, 10);

// Return the original seed plus the check digit
	return $number . $checkdigit;

}

function api_misc_randombytes($length = 10){

	if(!is_numeric($length)) return api_error_raise("Invalid length");
	else $genlength = ceil($length / 2);

	if($bytes = openssl_random_pseudo_bytes($genlength)) return substr(bin2hex($bytes), 0, $length);
	else return api_error_raise("Couldn't generate random bytes");
}

function api_misc_abnlookup($abn){

	$abn = preg_replace("/\D/i", "", trim($abn));

	if(!is_numeric($abn)) return api_error_raise("Sorry, that is not a valid ABN");
	else {

		$params = array('soap_version' => SOAP_1_1,
			'exceptions' => true,
			'trace' => 1,
			'cache_wsdl' => WSDL_CACHE_NONE);

		$client = new SoapClient("http://abr.business.gov.au/abrxmlsearch/ABRXMLSearch.asmx?WSDL", $params);

		$params = new stdClass();
		$params->searchString = $abn;
		$params->includeHistoricalDetails = "N";
		$params->authenticationGuid = ABR_LOOKUP_GUID;

		$response = $client->ABRSearchByABN($params);

		if(!empty($response->ABRPayloadSearchResults->response)){

			$node = $response->ABRPayloadSearchResults->response->businessEntity;

			return array("status" => ($node->ABN->isCurrentIndicator == "Y") ? true : false, "name" => $node->mainName->organisationName, "abn" => $node->ABN->identifierValue, "address" => array("state" => $node->mainBusinessPhysicalAddress->stateCode, "postcode" => $node->mainBusinessPhysicalAddress->postcode));

		} else return array("status" => false);
	}

}

function api_misc_xml_adopt($root, $new) {

	$node = $root->addChild($new->getName(), (string) htmlspecialchars($new));

	foreach($new->attributes() as $attr => $value) $node->addAttribute($attr, $value);

	foreach($new->children() as $ch) api_misc_xml_adopt($node, $ch);
}

function api_misc_sftp_put($options = array()){

	if(empty($options["hostname"])) return api_error_raise("Sorry, that is not a valid host name");

	if(empty($options["remotefile"])) return api_error_raise("Sorry, that is not a valid remote file name");
	if(empty($options["localfile"]) OR !file_exists($options["localfile"])) return api_error_raise("Sorry, that is not a valid local file");

	if(empty($options["port"])) $options["port"] = 22;

	$localfile = file_get_contents($options["localfile"]);

	if(!$localfile) return api_error_raise("Sorry, that is not a valid file");

	$connection = ssh2_connect($options["hostname"], $options["port"]);

	if(!$connection) return api_error_raise("Unable to connect to the server at '" . $options["hostname"] . "'");
	else api_misc_audit("SFTP_PUT_CONNECT", "Hostname: " . $options["hostname"] . "; Username: " . $options["username"]);

	if (
		(isset($options["hostname"]) && ($options["hostname"] == SFTP_REACHTEL_HOST_NAME) && isset($options["username"]) && ($options["username"] == SFTP_REACHTEL_USERNAME)) ||
		(isset($options["hostname"]) && ($options["hostname"] == SFTP_GLOBALSCAPE_HOST_NAME) && isset($options["username"]) && ($options["username"] == SFTP_GLOBALSCAPE_USERNAME))
	) {
		$options["pubkeyfile"] = SAVE_LOCATION . "/pgp/reachtel-sftp.pub";
		$options["privkeyfile"] = SAVE_LOCATION . "/pgp/reachtel-sftp.pem";
		$options["passphrase"] = SFTP_KEY_PASSPHRASE;

		if(isset($options["password"])) unset($options["password"]);
	}

	if(isset($options["pubkeyfile"]) AND isset($options["username"]) AND isset($options["privkeyfile"]) AND isset($options["passphrase"])) {
		if(!is_readable($options["pubkeyfile"])) return api_error_raise("Sorry, that public key file is not readable");
		elseif(!is_readable($options["privkeyfile"])) return api_error_raise("Sorry, that private key file is not readable");

		if(!@ssh2_auth_pubkey_file($connection, $options["username"], $options["pubkeyfile"], $options["privkeyfile"], $options["passphrase"])) return api_error_raise("Sorry, we couldn't authenticate using those details");
	}

	if(isset($options["username"]) AND isset($options["password"])) {
		if(!@ssh2_auth_password($connection, $options["username"], $options["password"])) return api_error_raise("Sorry, the supplied authentication details were rejected");
	}

	$sftp = ssh2_sftp($connection);

	if(!$sftp) return api_error_raise("Sorry, we couldn't initialise the SFTP connection");

	$stream = @fopen("ssh2.sftp://" . (int)$sftp . $options["remotefile"], 'w');

	if(!$stream) return api_error_raise("Sorry, the remote file is unavailable or we don't have permission to open it.");

	if(fwrite($stream, $localfile) === false) return api_error_raise("Sorry, we couldn't send that file");

	fclose($stream);

	return true;

}

/**
 * This will cache the files when sftp fails which will then be picked up by a job later to perform the upload
 * @param array $options
 * @return boolean
 */
function api_misc_sftp_put_safe(array $options) {
	if (api_misc_sftp_put($options)) {
		return true;
	}

	if (
		empty($options['hostname']) ||
		empty($options['localfile']) ||
		empty($options['remotefile'])
	) {
		return api_error_raise('Missing options for sftp put safe.');
	}

	if (empty($options['username']) || empty($options['password'])) {
		if (!in_array($options['hostname'], array_keys(api_misc_local_sftp_hostnames_default_username_map()))) {
			return api_error_raise('Missing options for sftp put safe.');
		}

		$options['username'] = !empty($options['username']) ?
			$options['username'] :
			api_misc_local_sftp_hostnames_default_username_map()[$options['hostname']];
	}

	$filename = api_misc_uniqueid();
	$pathinfo = pathinfo($options['localfile']);
	$filename .= (isset($pathinfo['extension'])) ? '.' . $pathinfo['extension'] : '';

	$localfile = api_misc_get_sftp_cache_absolute_path($filename);
	if (!$localfile) {
		return false;
	}
	if (!copy($options['localfile'], $localfile)) {
		api_error_raise('Error copying file to sftp cache directory');
		if (!copy($options['localfile'], '/tmp/' . $filename)) {
			return api_error_raise('Failed to cache sftp file in the tmp directory');
		}
	}

	$options['filename'] = $filename;

	if (!api_misc_sftp_cache_save_info($options)) {
		return api_error_raise('Something went wrong when saving sftp cache file details in db. File name: ' .  $filename);
	}

	api_error_raise('A file has been cached locally following sftp outage.');
	return true;
}

function api_misc_get_sftp_cache_absolute_path($filename) {
	$pathinfo = pathinfo($filename);
	if (isset($pathinfo['dirname']) && $pathinfo['dirname'] !== '.') {
		return api_error_raise('Invalid filename passed: ' .$filename);
	}

	return SAVE_LOCATION . '/' .SFTP_CACHE_LOCATION . '/' . $filename;
}

function api_misc_sftp_cache_save_info(array $options) {
	$options['password'] = !isset($options['password']) ? '' : api_misc_crypt_base64($options['password']);

	$sql = "INSERT INTO `sftp_cache` (`hostname`, `username`, `password`, `filename`, `remotefile`) VALUES (?,?,?,?,?)";
	return api_db_query_write(
		$sql,
		[
			$options['hostname'],
			$options['username'],
			$options['password'],
			$options['filename'],
			$options['remotefile'],
		]
	);
}

function api_misc_local_sftp_hostnames_default_username_map() {
	return [
		SFTP_REACHTEL_HOST_NAME => SFTP_REACHTEL_USERNAME,
		SFTP_GLOBALSCAPE_HOST_NAME => SFTP_GLOBALSCAPE_USERNAME
	];
}

function api_misc_sftp_get(array $options = []) {
	$options = _api_misc_sftp_config_validate($options);
	if ($options === false) {
		return false;
	}

	$sftp = _api_misc_sftp_get_connection($options);
	if ($sftp === false) {
		return false;
	}

	if(!@ssh2_sftp_stat($sftp, $options["remotefile"])) return api_error_raise("Sorry, that file doesn't exist");

	$contents = file_get_contents("ssh2.sftp://" . (int)$sftp . $options["remotefile"]);

	if($contents === false) return api_error_raise("Sorry, we couldn't fetch the remote file");

	file_put_contents($options["localfile"], $contents);

	return true;
}

/**
 * @param array $options
 * @return boolean
 */
function api_misc_sftp_remove(array $options = []) {
	if (empty($options["remotefile"])) {
		return api_error_raise("Sorry, that is not a valid remote file name");
	}

	// This is an extra validation to make sure that the file name and path does not have any thing dodgy.
	if (!preg_match('/^\/?([A-z0-9-_+]+\/)*([A-z0-9]+(\.[A-z0-9]+)?)$/', $options['remotefile'])) {
		return api_error_raise("Invalid remote file name received for sftp remove");
	}

	$options = _api_misc_sftp_config_validate_server_params($options);

	if ($options === false) {
		return false;
	}

	$sftp = _api_misc_sftp_get_connection($options);
	if ($sftp === false) {
		return false;
	}

	if(!@ssh2_sftp_unlink($sftp, $options["remotefile"])) return api_error_raise("SFTP unlink failed for the file:" . $options["remotefile"]);

	return true;
}

/**
 * Retrieve a large (> 2GB) file from SFTP with fopen
 *
 * @param array $options
 * @return bool
 */
function api_misc_sftp_get_large(array $options = []) {
	$options = _api_misc_sftp_config_validate($options);
	if ($options === false) {
		return false;
	}

	$sftp = _api_misc_sftp_get_connection($options);
	if ($sftp === false) {
		return false;
	}

	if(!@ssh2_sftp_stat($sftp, $options["remotefile"])) return api_error_raise("Sorry, that file doesn't exist");

	$fh_in = fopen("ssh2.sftp://" . (int)$sftp . $options["remotefile"], 'r');
	$fh_out = fopen($options['localfile'], 'w');

	while ($data = fread($fh_in, 1024)) {
		fwrite($fh_out, $data);
	}

	fclose($fh_in);
	fclose($fh_out);

	return true;
}

/**
 * Validate and augment sftp options ready for use
 *
 * @param array $options
 * @return array|bool
 */
function _api_misc_sftp_config_validate(array $options = [])
{
	$options = _api_misc_sftp_config_validate_server_params($options);

	if ($options === false) {
		return false;
	}

	if (empty($options["remotefile"])) {
		return api_error_raise("Sorry, that is not a valid remote file name");
	}

	if (empty($options["localfile"])) {
		return api_error_raise("Sorry, that is not a valid local file");
	}

	return $options;
}

/**
 * @param array $options
 * @return array|boolean
 */
function _api_misc_sftp_config_validate_server_params(array $options) {
	if (empty($options["hostname"])) {
		return api_error_raise("Sorry, that is not a valid host name");
	}

	if (empty($options["port"])) {
		$options["port"] = 22;
	}

	if (
		isset($options['hostname']) && isset($options['username']) &&
		(($options["hostname"] == SFTP_REACHTEL_HOST_NAME &&
				isset($options["username"]) && $options["username"] == SFTP_REACHTEL_USERNAME) ||
			($options["hostname"] == SFTP_GLOBALSCAPE_HOST_NAME &&
				isset($options["username"]) && $options["username"] == SFTP_GLOBALSCAPE_USERNAME))
	) {
		$options["pubkeyfile"] = SAVE_LOCATION . "/pgp/reachtel-sftp.pub";
		$options["privkeyfile"] = SAVE_LOCATION . "/pgp/reachtel-sftp.pem";
		$options["passphrase"] = SFTP_KEY_PASSPHRASE;

		if (isset($options["password"])) {
			unset($options["password"]);
		}
	}

	return $options;
}

/**
 * Check and Get SFTP connection
 *
 * @param array $options
 * @return resource|bool
 */
function _api_misc_sftp_get_connection(array $options = [])
{
	if (empty($options['hostname'])) {
		return api_error_raise('Sorry, that is not a valid host name');
	}

	if (empty($options['port'])) {
		return api_error_raise('Sorry, that is not a valid port');
	}

	if (empty($options['username'])) {
		return api_error_raise('Sorry, that is not a valid username');
	}

	$connection = ssh2_connect($options["hostname"], $options["port"]);

	if (!$connection) {
		return api_error_raise(
			sprintf(
				"Sorry, we couldn't connect to that server: %s@%s:%s",
				$options['username'],
				$options['hostname'],
				$options['port']
			)
		);
	} else {
		api_misc_audit(
			"SFTP_GET_CONNECT",
			"Hostname: " .
				$options["hostname"] .
				"; Username: " .
				$options["username"]
		);
	}

	if (
		isset($options["pubkeyfile"]) &&
		isset($options["username"]) &&
		isset($options["privkeyfile"]) &&
		isset($options["passphrase"])
	) {
		if (!is_readable($options["pubkeyfile"])) {
			return api_error_raise(
				"Sorry, that public key file is not readable"
			);
		} elseif (!is_readable($options["privkeyfile"])) {
			return api_error_raise(
				"Sorry, that private key file is not readable"
			);
		}

		if (
			!ssh2_auth_pubkey_file(
				$connection,
				$options["username"],
				$options["pubkeyfile"],
				$options["privkeyfile"],
				$options["passphrase"]
			)
		) {
			return api_error_raise(
				"Sorry, we couldn't authenticate using those details"
			);
		}
	}

	if (isset($options["username"]) && isset($options["password"])) {
		if (
			!@ssh2_auth_password(
				$connection,
				$options["username"],
				$options["password"]
			)
		) {
			return api_error_raise(
				"Sorry, we couldn't authenticate using those details"
			);
		}
	}

	$sftp = @ssh2_sftp($connection);

	if (!$sftp) {
		return api_error_raise(
			"Sorry, we couldn't initialise the SFTP connection"
		);
	}

	return $sftp;
}

function api_misc_sftp_list($options = array()){

	if(empty($options["hostname"])) return api_error_raise("Sorry, that is not a valid host name");

	if(empty($options["remotefile"])) return api_error_raise("Sorry, that is not a valid remote file name");

	if(empty($options["port"])) $options["port"] = 22;

	$connection = ssh2_connect($options["hostname"], $options["port"]);

	if(!$connection) return api_error_raise("Unable to connect to the server at '" . $options["hostname"] . "'");
	else api_misc_audit("SFTP_LIST_CONNECT", "Hostname: " . $options["hostname"] . "; Username: " . $options["username"]);

	if (
		(isset($options["hostname"]) && ($options["hostname"] == SFTP_REACHTEL_HOST_NAME) && isset($options["username"]) && ($options["username"] == SFTP_REACHTEL_USERNAME)) ||
		(isset($options["hostname"]) && ($options["hostname"] == SFTP_GLOBALSCAPE_HOST_NAME) && isset($options["username"]) && ($options["username"] == SFTP_GLOBALSCAPE_USERNAME))
	) {
		$options["pubkeyfile"] = SAVE_LOCATION . "/pgp/reachtel-sftp.pub";
		$options["privkeyfile"] = SAVE_LOCATION . "/pgp/reachtel-sftp.pem";
		$options["passphrase"] = SFTP_KEY_PASSPHRASE;

		if(isset($options["password"])) unset($options["password"]);
	}

	if(isset($options["pubkeyfile"]) AND isset($options["username"]) AND isset($options["privkeyfile"]) AND isset($options["passphrase"])) {
		if(!is_readable($options["pubkeyfile"])) return api_error_raise("Sorry, that public key file is not readable");
		elseif(!is_readable($options["privkeyfile"])) return api_error_raise("Sorry, that private key file is not readable");

		if(!@ssh2_auth_pubkey_file($connection, $options["username"], $options["pubkeyfile"], $options["privkeyfile"], $options["passphrase"])) return api_error_raise("Sorry, we couldn't authenticate using those details");
	}

	if(isset($options["username"]) AND isset($options["password"])) {
		if(!@ssh2_auth_password($connection, $options["username"], $options["password"])) return api_error_raise("Sorry, the supplied authentication details were rejected");
	}

	$sftp = ssh2_sftp($connection);

	if(!$sftp) return api_error_raise("Sorry, we couldn't initialise the SFTP connection");

	$dir = opendir("ssh2.sftp://" . (int)$sftp . $options["remotefile"]);

	if(!$dir) return api_error_raise("Sorry, the remote directory is unavailable or we don't have permission to open it.");

	$contents = array();

	while (false !== ($entry = readdir($dir))) {
        if ($entry != "." && $entry != "..") {
            $contents[] = $entry;
        }
    }

    closedir($dir);

	return $contents;

}

function api_misc_ispublicholiday($region = "AU", $timestamp = null){

	if($timestamp === null) $timestamp = time();

	if(!is_numeric($timestamp)) return api_error_raise("Sorry, that is not a valid timestamp");

	$holidays = api_system_tags_get($region . "-public-holidays");
	if ($holidays === false) {
		return api_error_raise("Sorry, there doesn't seem to be any public holidays listed for the region");
	}

	$holidays = explode(",", $holidays);

	if(empty($holidays)) return api_error_raise("Sorry, there doesn't seem to be any public holidays listed");

	$dayofmonth = date("j", $timestamp);
	$monthofyear = date("n", $timestamp);

	foreach($holidays as $holiday){

		$holiday = trim($holiday);

		if(empty($holiday)) continue;

		$time = strtotime($holiday);

		if($time == false) continue;

		if((date("j", $time) == $dayofmonth) AND (date("n", $time) == $monthofyear)) return true;

	}

	return false;
}

function api_misc_addbusinessdays($timestamp = null, $days = 1, $region = "AU"){

// This function takes a timestamp and adds sufficient $days which excludes natioanl public holidays and weekends

	if(!is_numeric($days)) return api_error_raise("Sorry, that is not a valid amount of days");

	if($timestamp == null) $timestamp = time();

	while($days > 0){

		$timestamp = $timestamp + 86400;

		if(!api_misc_ispublicholiday($region, $timestamp) AND (date("N", $timestamp) < 6)) $days--;
	}

	return $timestamp;
}

function api_misc_oldestevent($events){

	if(empty($events) OR !is_array($events)) return api_error_raise("Sorry, that is not a valid event array");

	$oldest = 0;

	foreach($events as $event) foreach($event as $items) if(isset($items["timestamp"]) && (strtotime($items["timestamp"]) >= $oldest)) $oldest = strtotime($items["timestamp"]);

	return $oldest;
}

function api_misc_hasevent($events, $key){

	if(!is_array($events)) return false;

	foreach($events as $event) if(is_array($event)) foreach($event as $item) if(!empty($item["value"]) AND ($item["value"] == $key)) return true;

	return false;

}

function api_misc_sanitize_upload_filename($filename) {

	$pathinfo = pathinfo($filename);

	if (!isset($pathinfo['extension']) || !$pathinfo['extension']) {
		return false;
	}

	// strip HTML and PHP tags and strip spaces
	$sanitized_filename = preg_replace('/[\r\n\t\s]+/i', '', strip_tags($pathinfo['filename']));
	// Remove anything which is not allowed
	$sanitized_filename = preg_replace('/([^\w\d\-_])+/i', '', $sanitized_filename);

	if (strpos($filename, DIRECTORY_SEPARATOR) !== false && isset($pathinfo['dirname'])) {
		$sanitized_filename = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $sanitized_filename;
	}

	return $sanitized_filename ? ($sanitized_filename . '.' .$pathinfo['extension']) : false;
}

/**
 * Is test environment
 *
 * @codeCoverageIgnore
 * @return boolean
 */
function api_misc_is_test_environment() {
	return (defined('APP_ENVIRONMENT') && in_array(APP_ENVIRONMENT, ['test', 'phpunit']));
}

function api_misc_linkshorten_shorten($url, $targetid = null) {

	if(!is_string($url)) {
		return api_error_raise("Sorry, that is not a valid URL to shorten");
	}

	// Trim off any white space
	$url = trim($url);

	// Check if we need to add the scheme to the url
	if(!preg_match("/^https?:\/\//i", $url)) {
		$url = "http://" . $url;
	}

	if(!filter_var($url, FILTER_VALIDATE_URL)) {
		return api_error_raise("Sorry, that is not a valid url");
	}

	// Check that the URL doesn't contain our short domain to stop recursion
	$parsedUrl = parse_url($url);

	if(($parsedUrl == false) || preg_match("/rch\.tl/i", $parsedUrl["host"])) {
		return api_error_raise("Sorry, that is not a valid url");
	}

	$sql = "INSERT INTO `linkshorten_urls` (`url`, `targetid`) VALUES (?, ?)";
	$rs = api_db_query_write($sql, array($url, $targetid));

	if ($rs !== false) {
		return api_misc_linkshorten_geturlfromid(api_db_lastid());
	} else {
		return false;
	}
}

/**
 * Takes a short URL code (like "b1Abd28k") and returns a
 *
 * @codeCoverageIgnore
 * @return integer
 */
function api_misc_linkshorten_expand($shortCode) {

	if(!preg_match("/^[0-9a-zA-Z]{1,10}$/", $shortCode)) {
		return api_error_raise("Sorry, that is not a valid short code");
	}

	if(!$id = api_misc_linkshorten_getidfromurl($shortCode)){
		return api_error_raise("Sorry, that is not a valid short code");
	}

	$sql = "SELECT `url`, `targetid` FROM `linkshorten_urls` WHERE `id` = ?";
	$rs = api_db_query_read($sql, array($id));

	if($rs && $rs->RecordCount()) {

		if($rs->Fields("targetid")) {

			$target = api_targets_getinfo($rs->Fields("targetid"));

			api_data_responses_add($target["campaignid"], 0, $target["targetid"], $target["targetkey"], "CLICK", $rs->Fields("url"));
			if(!empty($_SERVER["HTTP_USER_AGENT"])) api_data_responses_add($target["campaignid"], 0, $target["targetid"], $target["targetkey"], "CLICKCLIENT", $_SERVER["HTTP_USER_AGENT"]);
		}

		$sql = "UPDATE `linkshorten_urls` SET `referrals` = `referrals` + 1 WHERE `id` = ?";
		api_db_query_write($sql, array($id));

		return $rs->Fields("url");

	} else {
		return false;
	}

}

/**
 * Takes a short URL code (like "b1Abd28k") and returns the specific database record ID
 *
 * @codeCoverageIgnore
 * @return integer
 */
function api_misc_linkshorten_getidfromurl($shortCode) {

	if(empty($shortCode)) {
		return false;
	}

	$length = strlen(LINKSHORTEN_ALLOWEDCHARS);
	$size = strlen($shortCode) - 1;
	$shortCode = str_split($shortCode);
	$id = strpos(LINKSHORTEN_ALLOWEDCHARS, array_pop($shortCode));
	foreach($shortCode as $i => $char) {
		$id += strpos(LINKSHORTEN_ALLOWEDCHARS, $char) * pow($length, $size - $i);
	}

	return $id;
}

/**
 * Takes a database row ID and converts it to a short URL code (like "b1Abd28k")
 *
 * @codeCoverageIgnore
 * @return integer
 */
function api_misc_linkshorten_geturlfromid($id) {

	$base = LINKSHORTEN_ALLOWEDCHARS;

	$length = strlen($base);

	$base = str_split($base);

	$out = '';

	while($id > $length - 1) {
		$out = $base[fmod($id, $length)] . $out;
		$id = floor( $id / $length );
	}

	return $base[$id] . $out;
}

/**
 * Takes a message as a string, finds all the urls and automatically shortens them. We exclude shortening email addresses
 *
 * @codeCoverageIgnore
 * @return string
 */
function api_misc_linkshorten_findandreplace($text, $targetid = null) {

	/*

		This ugly looking regex is from the Android source code (specifically: android.text.util.Regex).
		http://grepcode.com/file/repository.grepcode.com/java/ext/com.google.android/android/2.0_r1/android/text/util/Regex.java#Regex.0WEB_URL_PATTERN
		http://stackoverflow.com/a/19696443

	*/
	$regex = '/((?:(http|https|Http|Https|rtsp|Rtsp):\/\/(?:(?:[a-zA-Z0-9\$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,64}(?:\:(?:[a-zA-Z0-9\$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,25})?\@)?)?((?:(?:[a-zA-Z0-9][a-zA-Z0-9\-]{0,64}\.)+(?:(?:aero|arpa|asia|a[cdefgilmnoqrstuwxz])|(?:biz|b[abdefghijmnorstvwyz])|(?:cat|com|me|coop|c[acdfghiklmnoruvxyz])|d[ejkmoz]|(?:edu|e[cegrstu])|f[ijkmor]|(?:gov|g[abdefghilmnpqrstuwy])|h[kmnrtu]|(?:info|int|i[delmnoqrst])|(?:jobs|j[emop])|k[eghimnrwyz]|l[abcikrstuvy]|(?:mil|mobi|museum|m[acdghklmnopqrstuvwxyz])|(?:name|net|n[acefgilopruz])|(?:org|om)|(?:pro|p[aefghklmnrstwy])|qa|r[eouw]|s[abcdeghijklmnortuvyz]|(?:tel|travel|t[cdfghjklmnoprtvwz])|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw]))|(?:(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9])\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])))(?:\:\d{1,5})?)(\/(?:(?:[a-zA-Z0-9\;\/\?\:\@\&\=\#\~\-\.\+\!\*\'\(\)\,\_])|(?:\%[a-fA-F0-9]{2}))*)?(?:\b|$)/i';

	if(preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE)) {

		foreach($matches[0] as $key => $match) {

			// The regex will match the domain part of an email address.
			// Check if we have accidentally hit this by checking if the preceding character was an @ symbol
			if(($match[1] > 0) && ($text[$match[1]-1] == "@")) {

				continue;
			}


			$shortenedLink = api_misc_linkshorten_shorten($match[0], $targetid);

			// Check if the link shorten process failed. If so, return the error
			if(!$shortenedLink) {
				return $shortenedLink;
			}

			// Find and replace the long link with the short link...but only once
			$text = preg_replace("/" . preg_quote($match[0], "/") . "/", "rchtl.com/r/" . $shortenedLink, $text, 1);

		}

	}

	// Return the text
	return $text;
}

function api_misc_is_cli() {
	return (php_sapi_name() === 'cli');
}

function api_misc_morpheus_logger_health_check($source = 'qos_check') {
	$group = 'loggerhealthcheck';
	$item = 'checktime';

	$socket = fsockopen('localhost', 4573, $errno, $errstr, 5);
	if (!$socket) {
		return api_error_raise('Unable to open a connection to morpheus logger');
	}

	$value = (new DateTime())->getTimestamp();
	$text = sprintf(
		"agi_request: /data_collector?source=%s&group=%s&item=%s&value=%s\n",
		$source,
		$group,
		$item,
		$value
	);
	if (!fwrite($socket, $text)) {
		fclose($socket);
		return api_error_raise('Morpheus logger health check failed. Unable to write to the port.');
	}
	fclose($socket);
	sleep(5);
	$sql = 'SELECT * FROM `data_collector` WHERE `group`=? AND `source`=? AND `item`=? AND `value`=? ORDER BY `dataid` DESC LIMIT 1';

	$rs = api_db_query_read($sql, [$group, $source, $item, $value]);

	if (!$rs) {
		return api_error_raise('DB failure during morpheus logger health check');
	}

	if (!$rs->RecordCount()) {
		return api_error_raise('Morpheus logger health check failed');
	}

	return true;
}
