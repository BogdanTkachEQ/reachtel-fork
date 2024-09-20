<?php

require_once("Morpheus/api.php");

$url = "https://thenumberingsystem.com.au/download/InquiryFullDownload.zip";

if(file_exists("/tmp/InquiryFullDownload.csv")) unlink("/tmp/InquiryFullDownload.csv");

//open connection
$ch = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

//curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36");
curl_setopt($ch,CURLOPT_USERAGENT,"curl/".(curl_version()["version"]));

//execute request
$contents = curl_exec($ch);
$info = curl_getinfo($ch);

//close connection
curl_close($ch);

// Check if the request was a success
if(($contents === false) OR ($info["http_code"] != 200)){
	print "Failed to download ZIP archive";
	exit;
}

$uniqueid = uniqid();

// Dump the downloaded contents into a temporary folder
if(file_put_contents("/tmp/acma-numberranges-" . $uniqueid . ".zip", $contents) === false){
	print "Failed to save downloaded file";
	exit;
}

// Open the ZIP archive
$zip = new ZipArchive;
$res = $zip->open("/tmp/acma-numberranges-" . $uniqueid . ".zip");

if ($res !== TRUE) {
	print "Failed to open ZIP file";
	exit;
}

// Extract the file we want
if(!$zip->extractTo("/tmp/", "InquiryFullDownload.csv")){
	print "Failed to extract ZIP contents";
	exit;
}

// Open the CSV for reading
if(($handle = fopen("/tmp/InquiryFullDownload.csv", "r")) === FALSE){
	print "Failed to open CSV file";
	exit;
}

$row = 0;
$numbers = array();

// Read each line of the CSV
while (($data = fgetcsv($handle, 1025748, ",")) !== FALSE) {

	$row++;

	if($row == 1){

		if(($data[0] != "Service Type") OR ($data[3] != "From") OR ($data[4] != "To") OR ($data[6] != "Allocatee")){
			print "CSV data not in the correct format";
			exit;
		}

		continue;

	}

	if(in_array($data[0], array("Local service", "Digital mobile")) AND ($data[6] != "(SPARE)")) {

		$numbers[] = array("from" => "61" . substr($data[3], 1), "to" => "61" . substr($data[4], 1), "carrier" => $data[8]);

	} else continue;

}

fclose($handle);

if(count($numbers) == 0){
	print "Failed to locate any numbers to insert";
	exit;
}

api_db_starttrans();

$sql = "DELETE FROM `number_washing_ranges` WHERE `to` LIKE ?";
$rs = api_db_query_write($sql, array("61%"));

foreach($numbers as $pair){
	$sql = "INSERT INTO `number_washing_ranges` (`from`, `to`, `carrier`) VALUES (?, ?, ?)";
	$rs = api_db_query_write($sql, array($pair["from"], $pair["to"], $pair["carrier"]));
}

api_db_endtrans();

// Cleaning up
if(file_exists("/tmp/InquiryFullDownload.csv")) unlink("/tmp/InquiryFullDownload.csv");
if(file_exists("/tmp/acma-numberranges-" . $uniqueid . ".zip")) unlink("/tmp/acma-numberranges-" . $uniqueid . ".zip");

print "Finished - updated " . count($numbers) . " records";

?>