<?php

require_once(__DIR__ . "/../../api.php");

// init globals for making it testable
global $sema_files, $campaigns, $tags, $filedate;

$cronid = 85;
$tags = api_cron_tags_get($cronid);
if (!isset($tags['groupid'])) $tags['groupid'] = 735;

function notify_success($info) {
    global $campaigns;
    global $tags;

    $email = [];
    $email["to"]          = $tags["reporting-destination"];
    $email["subject"]     = "[ReachTEL] Transurban Data File Load Report";
    $email["textcontent"] = <<<EOT
Hello,

We have received a data file and processing is now complete.

Filename: {$info['filename']}
File size: {$info['filesize']}
Total Records: {$info['recordcount']}
Imported Records: {$info['importcount']}
Campaigns Generated: {$info['campaigncount']}
Files Generated: {$info['semacount']}

Campaign Summary:
EOT;
    if ($campaigns) {
        foreach ($campaigns as $campaign) {
            if (isset($campaign['targets']['TOTAL']) && $campaign['targets']['TOTAL'] > 0) {
                $email["textcontent"] .= "\n{$campaign['name']} - {$campaign['targets']['READY']} ready of {$campaign['targets']['TOTAL']} added";
            }
        }
    } else {
        $email["textcontent"] .= "\nNo campaigns.";
    }

    $email["htmlcontent"] = nl2br($email["textcontent"]);
    $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

    api_email_template($email);
}

function notify_failure($info) {
    global $tags;

    $email = [];
    $email["to"]          = $tags["reporting-destination"];
    $email["subject"]     = "[ReachTEL] Transurban Data File Load Report";
    $email["textcontent"] = <<<EOT
Hello,

We have received a data file and it was unable to be processed.  If this is unexpected, please contact ReachTEL support.

Filename: {$info['filename']}
File size: {$info['filesize']}
EOT;
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    $email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";

    api_email_template($email);
}

function is_ves(array $row) {
    return in_array(strtoupper($row['REPORT_TYPE']), array('VES.1', 'DO NOT ACTION', 'SEND TO DCA'));
}

function is_retail(array $row) {
    return (! is_ves($row));
}

function customer_type(array $row) {
    if (is_ves($row)) {
        return 'VES';
    } elseif (is_retail($row)) {
        return 'RETAIL';
    } else {
        return null;
    }
}

function get_campaign_id($campaign) {
    global $campaigns, $tags, $filedate;

    if (isset($campaigns[$campaign]['id']) && is_numeric($campaigns[$campaign]['id'])) return $campaigns[$campaign]['id'];

    $campaignName = str_replace('.+', date('jFy', $filedate), $campaign);

    if (isset($tags['temporary']) && $tags['temporary'] == 'true') {
        $campaignName .= '-TEMP';
    }

    $previouscampaigns = api_campaigns_list_all(true, null, null, array("regex" => '^'.$campaign.'$'));

    foreach ($previouscampaigns as $previousid => $previouscampaign) {
        $groupid = api_campaigns_setting_getsingle($previousid, "groupowner");
        if ($groupid == $tags['groupid']) {
            $id = api_campaigns_checkorcreate($campaignName, $previousid);
            if (is_numeric($id)) {
                $campaigns[$campaign]['name'] = $campaignName;

                return ($campaigns[$campaign]['id'] = $id);
            }

            break;
        }
    }

    return false;
}

function try_destinations($campaign, array $row, array $columns) {
    $campaignid = get_campaign_id($campaign);
    if (!is_numeric($campaignid)) {
        return null;
    }

    foreach ($columns as $column) {
        if (empty($row[$column])) continue;

        $targetid = api_targets_add_single($campaignid, $row[$column], $row['LTI_ACCOUNT_ID'], null, $row);

        if (is_numeric($targetid)) {
            print "Added target for column '{$column}'.";
            return $targetid;
        }
    }

    return null;
}

function write_sema($customer_type, $letter, array $row) {
    global $sema_files, $filedate;

    $filename = '/tmp/LTIs-'.date('d.m.Y', $filedate).'-L'.$letter.'-'.($customer_type == 'VES' ? 'VES' : 'Retail').'.csv';
    $filename2 = '/tmp/Upload_MAIL_TPT_'.($customer_type == 'VES' ? '099' : '060').'_'.date('Y.m.d', $filedate).'.csv';

    if (! isset($sema_files[$filename])) {
        $sema_files[$filename] = fopen($filename, 'w');
        $header = array('ACCT ID', 'LPNSTATE', 'ASSET BALANCE', 'FIRST NAME', 'SURNAME', 'ADDRESS1', 'ADDRESS2', 'CITY', 'STATE', 'POSTAL');
        if (fputcsv($sema_files[$filename], $header) === false) {
            print "Failed to write CSV header: {$filename}";
            return;
        }
    }

    if (! isset($sema_files[$filename2])) {
        $sema_files[$filename2] = fopen($filename2, 'w');
        $header = array('PER_ID', 'ACCT_ID', 'CLASS', 'TYPE', 'SUBTYPE', 'COMMENTS', 'LOG', 'OBTAINED_BY', 'REFERRED_BY', 'CONTACT_DTTM', 'TODO', 'TODO_TYPE', 'ROLE', 'PRIORITY');

        if (fputcsv($sema_files[$filename2], $header) === false) {
            print "Failed to write CSV header: {$filename2}";
            return;
        }
    }

    switch ($customer_type) {
        case 'RETAIL':
            $data = array($row['LTI_ACCOUNT_ID'], $row['LTI_LPN'], '$'.$row['LTI_CURR_BALANCE'], $row['R_PRIM_FIRST_NAME'], $row['R_PRIM_LAST_NAME'], $row['R_MAILING_ADDRESS1'], $row['R_MAILING_ADDRESS2'], $row['R_MAILING_CITY'], $row['R_MAILING_STATE'], $row['R_MAILING_POSTCODE']);
            $data2 = array($row['R_PRIM_PERSON_ID'], $row['R_ACCOUNT_ID'], '', '', '', '', $row['LTI_ACCOUNT_ID'], '', '', date('Y-m-d H:i:s', $filedate), '', '', '', '');

            break;
        case 'VES':
            $data = array($row['LTI_ACCOUNT_ID'], $row['LTI_LPN'], '$'.$row['LTI_CURR_BALANCE'], $row['LTI_FIRST_NAME'], $row['LTI_LAST_NAME'], $row['LTI_ADDRESS1'], $row['LTI_ADDRESS2'], $row['LTI_CITY'], $row['LTI_STATE'], $row['LTI_POSTCODE']);
            $data2 = array($row['LTI_PER_ID'], $row['LTI_ACCOUNT_ID'], '', '', '', '', '', '', '', date('Y-m-d H:i:s', $filedate), '', '', '', '');

            break;
        case 'VESRETAIL':
            $data = array($row['LTI_ACCOUNT_ID'], $row['LTI_LPN'], '$'.$row['LTI_CURR_BALANCE'], $row['LTI_FIRST_NAME'], $row['LTI_LAST_NAME'], $row['LTI_ADDRESS1'], $row['LTI_ADDRESS2'], $row['LTI_CITY'], $row['LTI_STATE'], $row['LTI_POSTCODE']);
            $data2 = array($row['R_PRIM_PERSON_ID'], $row['R_ACCOUNT_ID'], '', '', '', '', $row['LTI_ACCOUNT_ID'], '', '', date('Y-m-d H:i:s', $filedate), '', '', '', '');

            break;
    }

    if (fputcsv($sema_files[$filename], $data) === false) {
        print "Failed to write CSV row: {$filename}. ";
    } else {
        print "Added in SEMA file {$filename}. ";
    }

    if (fputcsv($sema_files[$filename2], $data2) === false) {
        print "Failed to write CSV row: {$filename2}. ";
    } else {
        print "Added in Bulk file {$filename2}. ";
    }
}

function add_standard_fields($customer_type, &$row) {
    switch ($customer_type) {
        case 'RETAIL':
            $row['Debtor_FirstName'] = $row['R_PRIM_FIRST_NAME'];
            $row['Debtor_Surname'] = $row['R_PRIM_LAST_NAME'];
            $row['Debtor_FullName'] = $row['R_PRIM_FIRST_NAME'].' '.$row['R_PRIM_LAST_NAME'];
            $row['Debtor_Amount'] = $row['LTI_CURR_BALANCE'];
            $row['Debtor_Invoice'] = $row['LTI_ACCOUNT_ID'];
            $row['Debtor_LPN'] = $row['LTI_LPN'];

            break;
        case 'VES':
        case 'VESRETAIL':
            $row['Debtor_FirstName'] = $row['LTI_FIRST_NAME'];
            $row['Debtor_Surname'] = $row['LTI_LAST_NAME'];
            $row['Debtor_FullName'] = $row['LTI_FIRST_NAME'].' '.$row['LTI_LAST_NAME'];
            $row['Debtor_Amount'] = $row['LTI_CURR_BALANCE'];
            $row['Debtor_Invoice'] = $row['LTI_ACCOUNT_ID'];
            $row['Debtor_LPN'] = $row['LTI_LPN'];

            break;
    }
}

$sema_files = array();

$campaigns = [];

$lastRunDateTagName = 'last-run-date';
$todaysDate = date('d-m-Y');
$filedate = time();

$isAdhocRun = false;

if (! empty($tags['filename'])) {
    $filename = @trim($tags['filename']);
    api_cron_tags_delete($cronid, array('filename'));
    print "Got filename from tag: {$filename}\n";
    if (preg_match('/ReachTel_TPT_Dialler_Upload_(20[12][0-9]-[0-1][0-9]-[0-3][0-9])/', $filename, $m) === 1) {
        $filedate = strtotime($m[1]);
        print "Deduced date from filename: ".date('Y-m-d', $filedate)."\n";
    }
    $isAdhocRun = true;
} else {
    $lastRunDate = api_cron_tags_get($cronid, $lastRunDateTagName);
    if ($lastRunDate === $todaysDate) {
        print "The autoload has already done it's job for the day";
    }
    $filename = @trim($argv[1]);
}

if (empty($filename)) {
    print "Filename must be specified!\n";
    notify_failure([
        'filename' => '[not specified]',
        'filesize' => '[not applicable]',
    ]);
    exit;
}

if (empty($argv[2]) || $argv[2] != 'local') {
    print "Downloading file... ";

    $options = array(
        "hostname" => $tags["sftp-in-hostname"],
        "username" => $tags["sftp-in-username"],
        "password" => $tags["sftp-in-password"],
        "localfile" => '/tmp/'.$filename,
        "remotefile" => $tags["sftp-in-path"].$filename
    );

    if (! api_misc_sftp_get($options)) {
        $email["to"] = $tags["sftp-failure-notification"];
        $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["subject"] = "[ReachTEL] Auto-load error - Transurban - " . $filename;
        $email["textcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
        $email["htmlcontent"] = "Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" . $filename . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
        api_email_template($email);

        print "Failed to download file: {$filename}!\n";
        notify_failure([
            'filename' => $filename,
            'filesize' => '[not applicable]',
        ]);
        exit();
    }
    print "OK.\n";

    $filename = '/tmp/'.$filename;
    $filesize = @filesize($filename);
}

if (is_readable($filename) && preg_match("/pgp$/i", $filename)) {
    // Decrypt the PGP file
    print "Decrypting file {$filename}... ";

    $contents = file_get_contents($filename);
    $decrypted = api_misc_pgp_decrypt($contents);

    if (! $decrypted) {
        print "Failed to decrypt the file!\n";
        notify_failure([
            'filename' => $filename,
            'filesize' => '[not applicable]',
        ]);
        exit;
    }
    print "OK.\n";

    file_put_contents(str_replace(".pgp", "", $filename), $decrypted);
    unlink($filename);
    $filename = str_replace(".pgp", "", $filename);
}

print "Reading file {$filename}...\n";

if (empty($filesize)) {
    $filesize = '[unknown]';
}

if (($handle = fopen($filename, "r")) === FALSE) {
    print "Failed to open the file!\n";
    notify_failure([
        'filename' => $filename,
        'filesize' => $filesize,
    ]);
    exit;
}

if (($header = fgetcsv($handle, 1024768, ",")) === FALSE) {
    print "Unable to read header!\n";
    notify_failure([
        'filename' => $filename,
        'filesize' => $filesize,
    ]);
    exit;
}

$i = 0;
$importcount = 0;
$records = [];
while (($data = fgetcsv($handle, 1024768, ",")) !== FALSE) {
    $i++;

    $prefix = sprintf(
        "\nRow %'.06d | ",
        $i
    );

    // Skip blank rows
    if (empty($data[0])) {
        print $prefix."Empty row. ";
        continue;
    }

    $row = array();

    foreach($header as $key => $value) $row[$value] = (!empty($data[$key])) ? trim($data[$key]) : '';

    $customer_type = customer_type($row);
    if (!$customer_type) {
        print $prefix."Unknown customer type! ";
        continue;
    }

    $key = $row['LTI_AGE'].'-'.$customer_type;
    $prefix .= sprintf(
        "%s | ",
        str_pad($key, 10, ' ', STR_PAD_LEFT)
    );

    $targetid = null;

    switch ($key) {
        case '43-RETAIL':
        case '43-VES':
        case '43C-RETAIL':
        case '46-RETAIL':
        case '49-RETAIL':
        case '49-VES':
        case '53-RETAIL':
        case '54-VES':
        case '56-RETAIL':
        case '60-RETAIL':
        case '61-VES':
        case '63-RETAIL':
        case '67-RETAIL':
        case '68-VES':
        case '70-RETAIL':
        case '75-RETAIL':
        case '75-VES':
        case '78-RETAIL':
        case '81-RETAIL':
        case '82-VES':
        case '84-RETAIL':
        case '89-VES':
        case '89-RETAIL':
        case '93-VES':
        case '93-RETAIL':
        case '96-RETAIL':
        case '99-RETAIL':
        case '99-VES':
        case '101-RETAIL':
        case '105-RETAIL':
        case '105-VES':
        case '106-RETAIL':
        case '106-VES':
        case '110-RETAIL':
        case '116-RETAIL':
        case '122-RETAIL':
        case '122-VES':
        case '130-RETAIL':
        case '130-VES':
        case '135-RETAIL':
        case '135-VES':
        case '138-RETAIL':
        case '142-RETAIL':
        case '142-VES':
        case '146-RETAIL':
        case '146-VES':
        case '149-RETAIL':
        case '152-RETAIL':
        case '152-VES':
            // Add standard fields
            add_standard_fields($customer_type, $row);

            // Add record
            $records[$row['LTI_LPN']][$row['LTI_AGE']][$i] = $row;
            $importcount++;

            break;
        default:
            print $prefix."No action defined, row ignored. ";
    }
}
fclose($handle);
print "\n";
$recordcount = $i;

// Start from the oldest age for an LPN and if it does not work write sema. If that LPN has 43C it needs to be processed
// as well.
foreach ($records as $ages) {
    krsort($ages, SORT_NATURAL);

    // Move 43C to top, since it needs to be processed even though there are older debts than it.
    // If there are more special ages like 43C, we might need to add it to a tag and do it differently,
    // but still it is ugly.
    if (isset($ages['43C'])) {
        $specialAge = $ages['43C'];
        unset($ages['43C']);
        $ages = ['43C' => $specialAge] + $ages;
    }

    foreach ($ages as $rows) foreach ($rows as $i => $row) {
        $customer_type = customer_type($row);
        $key = $row['LTI_AGE'].'-'.$customer_type;

        print sprintf(
            "\nRow %'.06d | %s | ",
            $i,
            str_pad($key, 10, ' ', STR_PAD_LEFT)
        );

        switch ($key) {
            case '152-VES':
                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com11V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                //Write to sema file
                write_sema($customer_type, 2, $row);

                break;
            case '152-RETAIL':
                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com11R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                //Write to sema file
                write_sema($customer_type, 2, $row);

                break;
            case '149-RETAIL':
                $customer_type = 'VESRETAIL';
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-4',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '146-VES':
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-4',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '146-RETAIL':
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-4',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '142-VES':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom10-Com10V',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com10V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '142-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom10-Com10R',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com10R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '138-RETAIL':
                $customer_type = 'VESRETAIL';
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-4',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '135-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-4',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '135-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-4',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '130-VES':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom9-Com9V',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com9V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '130-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom9-Com9R',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com9R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '122-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '122-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '116-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSPromo2-Com8R',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com8R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '110-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSPromo1-Com7R',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com7R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '105-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '105-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '101-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-2',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '99-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-2',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '99-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-2',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '96-RETAIL':
                // VESRETAIL Age
                $customer_type = 'VESRETAIL';
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe3',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '93-VES':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe3',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '93-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe3',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '89-VES':
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSPayPlan-Com6V',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com6V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;
                break;
            case '89-RETAIL':
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSPayPlan-Com6R',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com6R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                break;
            case '84-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '82-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '81-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-3',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '78-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe2',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '75-VES':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe2',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com5V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 5, $row);

                break;
            case '75-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe2',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com5R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 5, $row);

                break;
            case '70-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-2',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '68-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-2',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '67-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-2',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '63-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-1',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com4R',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 4, $row);

                break;
            case '61-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-1',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com4V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 4, $row);

                break;
            case '60-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-1',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com4R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 4, $row);

                break;
            case '56-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-1',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '54-VES':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-1',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '53-RETAIL':
                // Try to find a number to send a robo call
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Voice-Message-1',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '49-VES':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe1',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '49-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMS-CallMe1',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                break;
            case '46-RETAIL':
                // VESRETAIL age
                $customer_type = 'VESRETAIL';

                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom1-Com3R',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                break;
            case '43C-RETAIL':
                // If target is added it needs to continue to the next age in the same LPN
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCRA-Com3-1',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 3;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com3-1',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 3;

                break;
            case '43-VES':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom1-Com3V',
                    $row,
                    array('LTI_MOBILE_NUMBER', 'LTI_PHONE_NUMBER', 'LTI_OTHER_NUMBER', 'LTI_OTHER_NUMBER1')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com3V',
                    $row,
                    array('LTI_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 3, $row);

                break;
            case '43-RETAIL':
                // Try to find a mobile number to send an SMS
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-SMSMail-SMSCom1-Com3R',
                    $row,
                    array('R_PRIM_MOB', 'R_PRIM_HOME_PHONE', 'R_PRIM_BUSPHONE', 'R_PRIM_OTHER_PHONE')
                );
                if ($targetid) continue 4;

                // Try to find an email address to send an email
                $targetid = try_destinations(
                    'Transurban-Linkt-.+-Email-Com3R',
                    $row,
                    array('R_PRIM_EMAIL')
                );
                if ($targetid) continue 4;

                // Write to SEMA file
                write_sema($customer_type, 3, $row);

                break;
        }
    }
}

print "\n\n";

$currentMonthCallmeCampaignName = 'Transurban-CallMe-' . date('Fy');
$currentMonthCallMeCampaignId = api_campaigns_nametoid($currentMonthCallmeCampaignName);
$currentMonthCallMeCampaignExistsAndIsActive = ($currentMonthCallMeCampaignId &&
    api_campaigns_setting_getsingle($currentMonthCallMeCampaignId, CAMPAIGN_SETTING_STATUS) === CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE);

print "Finalising campaigns...\n";
for ($i = 1; $i < 4; $i++) {
    if (! isset($campaigns['Transurban-Linkt-.+-Voice-Message-'.$i])) continue;

    for ($j = $i + 1; $j <= 4; $j++) {
        if (! isset($campaigns['Transurban-Linkt-.+-Voice-Message-'.$j])) continue;

        printf("Dedeuplicating %s against %s...", $campaigns['Transurban-Linkt-.+-Voice-Message-'.$i]['name'], $campaigns['Transurban-Linkt-.+-Voice-Message-'.$j]['name']);
        if (api_targets_dedupe($campaigns['Transurban-Linkt-.+-Voice-Message-'.$i]['id'], $campaigns['Transurban-Linkt-.+-Voice-Message-'.$j]['id']) === true) {
            print "succeeded.\n";
        } else {
            print "failed.\n";
        }
    }

    if ($currentMonthCallMeCampaignExistsAndIsActive) {
        printf("Dedeuplicating %s against %s...", $campaigns['Transurban-Linkt-.+-Voice-Message-'.$i]['name'], $currentMonthCallmeCampaignName);

        if (api_targets_dedupe($campaigns['Transurban-Linkt-.+-Voice-Message-'.$i]['id'], $currentMonthCallMeCampaignId) === true) {
            print "succeeded\n";
        } else {
            print "failed\n";
        }
    }
}

foreach ($campaigns as &$campaign) {
    print sprintf(
        "%s (%s): ",
        $campaign['name'],
        $campaign['id']
    );

    if (isset($tags['temporary']) && $tags['temporary'] == 'true') {
        $campaign['targets'] = api_data_target_status($campaign['id']);
        api_campaigns_delete($campaign['id']);
        print "Temporary - Deleted.\n";
        continue;
    }

    print "Deduplication ";
    if (api_targets_dedupe($campaign['id']) === true) {
        print "succeeded. ";
    } else {
        print "failed. ";
    }

    $campaign['targets'] = api_data_target_status($campaign['id']);
    if ($campaign['targets']['TOTAL'] == 0) {
        api_campaigns_delete($campaign['id']);
        print "Empty - Deleted.\n";
        continue;
    }

    print "Campaign ";
    if(isset($tags["autoactivate"]) && ($tags["autoactivate"] == "true")) {
        api_campaigns_setting_set($campaign['id'], "status", "ACTIVE");
    } else {
        print "not ";
    }
    print "activated.\n";
}
unset($campaign);
print "\n";

foreach ($sema_files as $sema_filename => $handle) {
    fclose($handle);

    if (! empty($tags['pgp-keys'])) {
        print "Trying to encrypt file: {$sema_filename} (".number_format(filesize($sema_filename))." bytes)...";
        $pgpfile['content'] = file_get_contents($pgpfile['filename'] = $sema_filename);
        $pgpfile = api_misc_pgp_encrypt($pgpfile, $tags['pgp-keys']);
        if ($pgpfile) {
            unlink($sema_filename);
            file_put_contents($sema_filename = $pgpfile['filename'], $pgpfile['content']);
        }
    }

    print "Uploading file: {$sema_filename} (".number_format(filesize($sema_filename))." bytes)...";

    $options = array(
        "hostname" => $tags["sftp-out-hostname"],
        "username" => $tags["sftp-out-username"],
        "password" => $tags["sftp-out-password"],
        "localfile" => $sema_filename,
        "remotefile" => $tags["sftp-out-path"].basename($sema_filename)
    );

    if (! api_misc_sftp_put($options)) {
        $email["to"] = $tags["sftp-failure-notification"];
        $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["subject"] = "[ReachTEL] File upload error - Transurban - " . basename($sema_filename);
        $email["textcontent"] = "Hello,\n\nThe following file could not be uploaded to the specified server:\n\n" . basename($sema_filename) . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
        $email["htmlcontent"] = "Hello,\n\nThe following file could not be uploaded to the specified server:\n\n" . basename($sema_filename) . "\n\nThe auto-load process has failed. Please advise ReachTEL Support if these files are expected at a later time.";
        api_email_template($email);

        print "Failed\n";
        continue;
    }
    print "OK\n";

    unlink($sema_filename);
}

notify_success([
    'filename' => basename($filename),
    'filesize' => $filesize,
    'recordcount' => $recordcount,
    'importcount' => $importcount,
    'campaigncount' => count($campaigns),
    'semacount' => count($sema_files),
]);

unlink($filename);

if (!$isAdhocRun) {
    print "Last run date tag updated.\n";
    api_cron_tags_set($cronid, [$lastRunDateTagName => $todaysDate]);
}
