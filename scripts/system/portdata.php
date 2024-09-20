<?php

require_once("Morpheus/api.php");

$files = array("MNPR_1411.txt", "MNPR_1415.txt", "MNPR_1456.txt");

api_db_starttrans();

print "Dumping old records...";

$sql = "DELETE FROM `port_data`";
$rs = api_db_query_write($sql);

print "OK\n";

foreach($files as $filename){

	print "Importing " . $filename . "\n";

	print "Downloading file...";

	$options = array("hostname" => "sftp.reachtel.com.au",
		"username" => "reachtelautomation",
		"remotefile" => "/mnt/sftpusers/otw_np/upload/" . $filename,
		"localfile" => "/tmp/" . $filename);

	$response = api_misc_sftp_get($options);

	print "OK\n";

	$handle = fopen("/tmp/" . $filename, "r");

	if(!$handle) {

		print "Failed to open file " . $filename;
		exit;
	}

	$rows = array();

	print "Processing...\n";

	while(($buffer = fgets($handle, 4096)) !== false) {

		$buffer = trim($buffer);

		if(preg_match("/^00HDR([0-9]{4})([0-9]{12})(.{14})$/", $buffer, $matches)){

			// Heder row
			$losingcarrier = $matches[1];

			continue;

		} else if(preg_match("/^99TRL([0-9]{12})([0-9]{9})([0-9]{9})$/", $buffer, $matches)){

			// Trailer row
			continue;

		} else if(preg_match("/^01([0-9]{10})([0-9]{4})([0-9]{0,3})([AU])([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$/", $buffer, $matches)){

			// Data row

			$destination = "61" . substr($matches[1], 1);
			$timestamp = mktime($matches[8], $matches[9], 0, $matches[6], $matches[7], $matches[5]);
			$gainingcarrier = $matches[2];

			$rows[] = array("destination" => $destination, "timestamp" => $timestamp, "gainingcarrier" => $gainingcarrier, "losingcarrier" => $losingcarrier);

		} //else print "no match - " . $buffer . "\n";

		if(!(count($rows) % 1000) AND count($rows)){

			savedata($rows);

			$rows = array();
		}
	}

	print "OK\n";

	if(count($rows) > 0) savedata($rows);

	if(!feof($handle)) {

		print "Failed to read the file";
	}

	fclose($handle);

	unlink("/tmp/" . $filename);

}

print "Committing transaction...";

api_db_endtrans();

print "OK\n";

print "Done!\n\n";

function savedata($rows){

	$sql = "INSERT INTO `port_data` (`destination`, `timestamp`, `gainingcarrier`, `losingcarrier`) VALUES ";
	$variables = array();

	foreach($rows as $row => $data){

		$sql .= "(?, ?, ?, ?), ";
		array_push($variables, $data["destination"], date("Y-m-d H:i:s", $data["timestamp"]), $data["gainingcarrier"], $data["losingcarrier"]);

	}

	$sql = substr($sql, 0, -2);

	$sql .= " ON DUPLICATE KEY UPDATE `timestamp` = VALUES(`timestamp`)";

	api_db_query_write($sql, $variables);

}

?>
