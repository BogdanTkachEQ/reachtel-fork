<?php

require_once("Morpheus/api.php");

if(empty($argv[1])) {
    print "Invalid filename specified\n";
    exit;
}

$filename = $argv[1];
$environment = !empty($argv[2]) ? $argv[2] : "uat";

// If the filename ends in ".filepart" strip this from the file name
if(preg_match("/filepart$/i", $filename)) {
    $filename = substr($filename, 0, -9);
}

$path = "/tmp/";
$directory = (empty($environment) || $environment == "uat") ? "toReachtelTest" : "toReachtelProd";

// Sleep for 10 seconds to let the file settle down
sleep(10);

print "Downloading file...";

$options = array("hostname" => "sftp.reachtel.com.au",
    "username" => "reachtelautomation",
    "localfile" => $path . $filename,
    "remotefile"=> "/mnt/sftpusers/veda_01/upload/CollectionHouse/" . $directory . "/" . $filename);

if(!api_misc_sftp_get($options)){

    print "Failed to fetch file '" . $filename . "'\n";
    exit;

} else print "OK\n";

print "Creating campaign...";

if(preg_match("/^CollectionHouse\-(StGeorge|CFAL)\-(20\d\d)(\d\d)(\d\d)\.csv$/i", $filename, $matches)) {

    $basecampaignname = "CollectionHouse-" . $matches[1] . "-" . $environment . "-" . $matches[2] . $matches[3] . $matches[4];
    $basesearch = "CollectionHouse-" . $matches[1] . "-" . $environment . "-";

} else {

    unlink($path . $filename);

    print "Failed. The file name doesn't match the expected format.\n";
    exit;
}

$campaigns = [];
for ($cId = 1; $cId <= 3; $cId++) {
    $campaignname = "{$basecampaignname}-{$cId}";

    print "Creating campaign {$campaignname}...";
    $exists = api_campaigns_checknameexists($campaignname);

    if(is_numeric($exists)) {

        unlink($path . $filename);

        print "Failed. The campaign {$campaignname} already exists.\n";
        exit;

    }

    $previouscampaigns = api_campaigns_list_all(true, null, null, array("search" => "{$basesearch}20*-{$cId}"));
    $campaignid = api_campaigns_checkorcreate($campaignname, key($previouscampaigns));

    if(!is_numeric($campaignid)){

        unlink($path . $filename);

        print "Failed to create campaign {$campaignname}\n";
        exit;

    }

    $campaigns[$cId] = [
        'id' => $campaignid,
        'name' => $campaignname
    ];

    print " OK!\n";
}


print "\nUploading data...\n";


if (($handle = fopen($path . $filename, "r")) === FALSE) {

    print "Failed to open the file\n";
    exit;
}

$i = 0;

while (($data = fgetcsv($handle, 1024768, ",")) !== FALSE) {

    $i++;

    if($i == 1) {
        $header = $data;
    } else {

        // Skip blank rows
        if(empty($data[0])) continue;

        $row = array();

        foreach($header as $key => $value) {
            $row[$value] = (!empty($data[$key])) ? trim($data[$key]) : null;
        }

        $fields = [];
        foreach([1 => 'MobilePhoneNumber', 2 => 'HomePhoneNumber', 3 => 'WorkPhoneNumber'] as $priority => $field) {
            if(isset($row[$field]) && !empty($row[$field])) {
                $fields[$priority] = $field;
            }
        }

        if($fields) {
            $cId = count($fields);
            $campaign = $campaigns[$cId];
            $mergedata = $row;
            unset($mergedata["Ual"], $mergedata["MobilePhoneNumber"], $mergedata["HomePhoneNumber"], $mergedata["WorkPhoneNumber"]);
            foreach($fields as $priority => $field) {
                api_targets_add_single($campaign['id'], $row[$field], $row["Ual"], $priority, $mergedata);
            }
        }

    }


}

print "OK\n\n";

unlink($path . $filename);

foreach($campaigns  as $campaign) {

    print "Activating campaign {$campaign['name']}...";

    if(api_cron_tags_get(67, "auto-activate") == "true") {

        if(!api_campaigns_setting_set($campaign['id'], "status", "ACTIVE")) {

            print "Failed!\n";
            exit;

        }
        print "OK\n";
    } else {
        print "Skipped\n";
    }
}

print "Job done!\n";