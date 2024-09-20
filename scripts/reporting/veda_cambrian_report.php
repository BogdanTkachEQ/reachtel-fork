#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

$cron_id = 69;
$tags = api_cron_tags_get($cron_id);

$response_rows = ['resultid', 'campaignid', 'targetid', 'eventid', 'targetkey', 'timestamp', 'action', 'value'];
$target_rows = ['targetid', 'campaignid', 'targetkey', 'destination'];
$fr_tmp = "/tmp/veda-cambrian-report-response_data.csv";
$ft_tmp = "/tmp/veda-cambrian-report-targets.csv";
$fmd_tmp = "/tmp/veda-cambrian-report-merge_data.csv";

$pid = pcntl_fork();
if ($pid == -1) {
     die('could not fork');
} else if ($pid) {
     // we are the parent
     pcntl_wait($status); //Protect against Zombie children
} else {
$from = 'last sunday';
$to = 'yesterday';

$updated_tags = [];

// Adding dates to tags so that the report could be regenerated when required.
// The tag values for date will be defaulted to last sunday and yesterday so that
// we need not refer to the code for tag keys when running adhoc report.
if (!isset($tags['from']) || (isset($tags['from']) && $tags['from'] !== $from)) {
    $updated_tags['from'] = $from;
    $from = isset($tags['from']) ? $tags['from'] : $from;
}

if (!isset($tags['to']) || (isset($tags['to']) && $tags['to'] !== $to)) {
    $updated_tags['to'] = $to;
    $to = isset($tags['to']) ? $tags['to'] : $to;
}

if ($updated_tags) {
    print "Setting date tags to default value.\n";
    api_cron_tags_set($cron_id, $updated_tags);
}

$sql = "SELECT `response_data`.`resultid`, `response_data`.`campaignid`, `response_data`.`targetid`, `response_data`.`eventid`, `response_data`.`targetkey`, `response_data`.`timestamp`, `response_data`.`action`, `response_data`.`value`, `targets`.`destination` FROM `response_data`, `targets` WHERE `response_data`.`targetid` = `targets`.`targetid` AND `response_data`.`timestamp` > ? AND `response_data`.`timestamp` < ?";
$rd = api_db_query_read($sql, [date("Y-m-d 00:00:00", strtotime($from)), date("Y-m-d 23:59:59", strtotime($to))]);

$records = $rd->RecordCount();
print date("Y-m-d H:i:s") . " Found {$records} rows to dump\n";

$fr = fopen($fr_tmp, "w+");
fputcsv($fr, $response_rows);

$ft = fopen($ft_tmp, "w+");
fputcsv($ft, $target_rows);

$fmd = fopen($fmd_tmp, "w+");
fputcsv($fmd, ['campaignid', 'targetkey', 'element', 'value']);

$targets_found = $merge_data_found = [];
$response_rows = array_flip($response_rows);
$target_rows = array_flip($target_rows);
$rowcounter = 0;

while(!$rd->EOF) {
    $row = $rd->FetchRow();
    $response = array_intersect_key($row, $response_rows);
    fputcsv($fr, $response);

    if (empty($targets_found[(int)$response["campaignid"]]) || !isset($targets_found[(int)$response["campaignid"]][(int)$response['targetid']])) {
        $targets_found[(int)$response["campaignid"]][(int)$response['targetid']] = true;
        fputcsv($ft, array_intersect_key($row, $target_rows));

        if (empty($merge_data_found[$response['campaignid']]) || !isset($merge_data_found[(int)$response['campaignid']][$response['targetkey']])) {
            $merge_data_found[(int)$response['campaignid']][$response['targetkey']] = true;
            $merge_data = api_data_merge_get_all($response['campaignid'], $response['targetkey']);
            foreach($merge_data as $element => $value) {
                // campaignid, targetkey, element, value
                fputcsv($fmd, [$response['campaignid'], $response['targetkey'], $element, $value]);
            }
        }
    }

    $rowcounter++;

    if(!($rowcounter % ceil($records / 20))) {
        print date("Y-m-d H:i:s") . " " . ceil(($rowcounter / $records)*100) . "% complete\n";
    }
}

print date("Y-m-d H:i:s") . " Encrypting...\n";
unset($targets_found, $merge_data_found);
fclose($fr);
fclose($ft);
fclose($fmd);
exit;
}

if (empty($tags['pgp-keys'])) {
    print "No PGP keys found\n";
    exit;
}

$pgpkeys = array_map('trim', explode(",", $tags['pgp-keys']));

foreach($pgpkeys as $keyid) {
    if(!api_misc_pgp_importkey($keyid)) {
        print "Unable to load the PGP key with the id '{$keyid}'\n";
        exit;
    }
}

foreach(['response_data' => $fr_tmp, 'targets' => $ft_tmp, 'merge_data' => $fmd_tmp] as $name => $file) {

    $outputfilepath = "/tmp/";
    $outputfilename = "veda-cambrian-report-{$name}.gpg";

    $command = "gpg --no-permission-warning --yes --trust-model always --no-verbose --quiet --homedir " .  SAVE_LOCATION . "/pgp/ ";
    foreach($pgpkeys as $keyid) {
        $command .= " --recipient " . escapeshellarg($keyid);
    }
    $command .= " --output {$outputfilepath}{$outputfilename} --encrypt {$file}";

    $output = system($command, $return_value);

    if ($return_value !== 0) {
        echo "PGP failed!\n" . $output;
        exit;
    }

    $options = [
        "hostname"   => $tags['sftp-hostname'],
        "username"   => $tags['sftp-username'],
        "password"   => $tags['sftp-password'],
        "localfile"  => $outputfilepath . $outputfilename,
        "remotefile" => $tags['sftp-path'] . "/{$outputfilename}",
    ];

    $result = api_misc_sftp_put_safe($options);
    if (!$result) {
        echo "SFTP failed!\n";
        api_error_printiferror();
        exit;
    }
    echo "SFTP OK\n";

    unlink($outputfilepath . $outputfilename);
    unlink($file);
}

print "Completed.\n";
