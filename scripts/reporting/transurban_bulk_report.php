#!/usr/bin/php
<?php

require_once("Morpheus/api.php");

function is_ves(array $row) {
	return in_array(strtoupper($row['REPORT_TYPE']), array('VES.1', 'DO NOT ACTION', 'SEND TO DCA'));
}

$groupId = 735;
$cronId = 86;
$tags = api_cron_tags_get($cronId);

if (!$tags || !isset($tags['campaign-tag-filter'])) {
    fwrite(
        STDOUT,
        "[ERROR] Cron tag 'campaign-tag-filter' not found\n"
    );
    exit;
}

if (isset($tags['date'])) {
	$datestring = $tags['date'];
	api_cron_tags_set($cronId, ['date' => 'today']);
} else {
	$datestring = 'today';
}

$startTime = strtotime($datestring);
$endTime = min($startTime + (60 * 60 * 24) - 1, time());

if ($datestring != 'today') {
	print "Running report for {$datestring}...\n";
	if ($startTime > time()) {
		print "Cannot run future dated reports!\n";
		exit;
	}
}

$header = array('PER_ID', 'ACCT_ID', 'CLASS', 'TYPE', 'SUBTYPE', 'COMMENTS', 'LOG', 'OBTAINED_BY', 'REFERRED_BY', 'CONTACT_DTTM', 'TODO', 'TODO_TYPE', 'ROLE', 'PRIORITY');

$reports = [];
fwrite(STDOUT, "Fetching campaigns data ...");
foreach(api_campaigns_list_all(true) as $campaignId => $campaignName) {
    $settings = api_campaigns_setting_getall($campaignId);

    if(empty($settings['created'])
       // today's campaigns only
       || $settings['created'] < $startTime
       || $settings['created'] > $endTime
       // Transurban group id only
       || $settings['groupowner'] != $groupId
       // ... and campaigns with filter tag only
       || !api_campaigns_tags_get($campaignId, $tags['campaign-tag-filter'])) {
            continue;
    }

    $res = [];
    switch ($type = $settings['type']) {
        case 'sms':
            $sql = "SELECT t.*, cr.timestamp
                    FROM `targets` AS t
                    INNER JOIN `call_results` AS cr ON (
                        t.`campaignid` = cr.`campaignid`
                        AND t.`targetid` = cr.`targetid`
                        AND cr.`value` = ?
                    )
                    WHERE t.`campaignid` = ?
                    GROUP BY t.`targetid`";
            $rs = api_db_query_read($sql, [
                'SENT', // call_results value
                $campaignId,
            ]);
            $res = $rs->GetArray();
            break;

        case 'email':
            $sql = "SELECT t.*, MIN(rd.timestamp) AS timestamp
                    FROM `targets` AS t
                    INNER JOIN `response_data` AS rd ON (
                        t.`campaignid` = rd.`campaignid`
                        AND t.`targetid` = rd.`targetid`
                    )
                    WHERE t.`campaignid` = ?
                    GROUP BY t.`targetid`
                    HAVING GROUP_CONCAT(rd.action) NOT LIKE ?";
            $rs = api_db_query_read($sql, [
                $campaignId,
                '%HARDBOUNCE%', // target status
            ]);
            $res = $rs->GetArray();
            break;

        case 'phone':
            $sql = "SELECT t.*, MIN(rd.timestamp) AS timestamp
                    FROM `targets` AS t
                    INNER JOIN `call_results` AS rd ON (
                        t.`campaignid` = rd.`campaignid`
                        AND t.`targetid` = rd.`targetid`
                    )
                    WHERE t.`campaignid` = ? 
                    AND rd.value != ?
                    GROUP BY t.`targetid`";
            $rs = api_db_query_read($sql, [
                $campaignId,
                'GENERATED',
            ]);
            $res = $rs->GetArray();
            break;
    }

    foreach($res as $target) {
        $data = api_data_merge_get_all($campaignId, $target['targetkey']);

        if (!$data) {
            fwrite(
                STDOUT,
                "\n[ERROR] Campaign {$campaignName} (id={$campaignId}) " .
                "target {$target['targetkey']} has no merge data!"
            );
            continue;
        }

        $row = array_fill_keys($header, '');
        $row['CONTACT_DTTM'] = $target['timestamp'];
        if (is_ves($data)) {
            $row['PER_ID'] = $data['LTI_PER_ID'];
            $row['ACCT_ID'] = $data['LTI_ACCOUNT_ID'];
            $reports[$type]['ves'][] = $row;
        } else {
            $row['PER_ID'] = $data['R_PRIM_PERSON_ID'];
            $row['ACCT_ID'] = $data['R_ACCOUNT_ID'];
            $row['LOG'] = $data['LTI_ACCOUNT_ID'];
            $reports[$type]['retail'][] = $row;
        }
    }

    $rs->_close();
}

if (!$reports) {
    fwrite(
        STDOUT,
        "[ERROR] Did not find any data!\n"
    );
    exit;
}

fwrite(STDOUT, " [OK]\n");

foreach($reports as $cType => $report_type) {
    foreach($report_type as $type => $data) {
        fwrite(STDOUT, "Generating {$cType} {$type} report:\n");

        $filepath =  sprintf(
            '%s/Upload_%s_TPT_%s_%s.csv',
            sys_get_temp_dir(),
            strtoupper(($cType == 'phone' ? 'DIALLER' : $cType)),
            ($type == 'ves' ? '099' : '060'),
            date('Y.m.d', $startTime)
        );

        fwrite(STDOUT, "  - Row(s): " . count($data) . "\n");
        fwrite(STDOUT, "  - File path: {$filepath}\n");
        fwrite(STDOUT, "  - Creating CSV file... ");

        if (!($handle = fopen($filepath, 'w'))) {
            fwrite(STDOUT, "[FAILED]\n");
            continue;
        }
        fputcsv($handle, array_keys(current($data)));
        foreach ($data as $line) {
            fputcsv($handle, $line);
        }
        fclose($handle);
        fwrite(STDOUT, "[OK]\n");

        if (! empty($tags['pgp-keys'])) {
            print "Trying to encrypt file: {$filepath}...";
            $pgpfile['content'] = file_get_contents($pgpfile['filename'] = $filepath);
            $pgpfile = api_misc_pgp_encrypt($pgpfile, $tags['pgp-keys']);
            if ($pgpfile) {
                unlink($filepath);
                file_put_contents($filepath = $pgpfile['filename'], $pgpfile['content']);
            }
        }

        fwrite(STDOUT, "  - Uploading to FTP... ");
        $options = [
            "hostname" => $tags["sftp-hostname"],
            "username" => $tags["sftp-username"],
            "password" => $tags["sftp-password"],
            "localfile" => $filepath,
            "remotefile" => $tags["sftp-path"] . basename($filepath)
        ];
        $ftp = api_misc_sftp_put_safe($options);
        unlink($filepath);
        if(!$ftp) {
            fwrite(STDOUT, "[FAILED]\n");
            continue;
        }

        fwrite(STDOUT, "[OK]\n");
    }
}
