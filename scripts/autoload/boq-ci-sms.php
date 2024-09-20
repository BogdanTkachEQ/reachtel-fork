<?php

use Services\Exceptions\Campaign\NoDataException;

require_once __DIR__ . "/../../api.php";

$timeZone = new DateTimeZone('Australia/Brisbane');
$date = new DateTime('now', $timeZone);

if (isHoliday($date)) {
    print 'Not running the autoload since it is a holiday';
    exit;
}

$cronId = getenv(CRON_ID_ENV_KEY);
$baseCampaignId = 171169;

$tags = api_cron_tags_get($cronId);

$holidayCheckBackDaysTagKey = 'holiday-check-back-days';
$sftpHostNameTagKey = 'sftp-host-name';
$sftpUsernameTagKey = 'sftp-username';
$sftpPwdTagKey = 'sftp-password';
$sftpPathTagKey = 'sftp-path';
$sftpErrorDestination = 'sftp-error-destination';
$campaignSendRateTagKey = 'campaign-send-rate';

$expectedTags = [
    $sftpHostNameTagKey,
    $sftpUsernameTagKey,
    $sftpPathTagKey,
    $sftpPwdTagKey
];

if (!$tags) {
    print "No tags set for cron. Sending error email.";
    sendMissingTagsErrorEmail($expectedTags);
    exit;
}

$missingTags = array_diff($expectedTags, array_keys($tags));

if ($missingTags) {
    print "There are some tags missing. Sending error email.";
    sendMissingTagsErrorEmail($missingTags);
    exit;
}

$holidayCheckBack = 6;
if (isset($tags[$holidayCheckBackDaysTagKey])) {
    if ($tags[$holidayCheckBackDaysTagKey] > 0 && $tags[$holidayCheckBackDaysTagKey] <= 6) {
        $holidayCheckBack = $tags[$holidayCheckBackDaysTagKey];
    } else {
        print $holidayCheckBackDaysTagKey . " has invalid value and so defaulting it to " . $holidayCheckBack . ".\n";
        api_misc_audit('INVALID_CRON_TAG_VALUE', 'Tag ' . $holidayCheckBackDaysTagKey . ' has been set to invalid value.');
    }
}

$holidayCheckBack = (isset($tags[$holidayCheckBackDaysTagKey]) &&
    $tags[$holidayCheckBackDaysTagKey] <= 6 &&
    $tags[$holidayCheckBackDaysTagKey] > 0) ?
    $tags[$holidayCheckBackDaysTagKey] :
    6;

$sftpArray = [
    'hostname' => $tags[$sftpHostNameTagKey],
    'username' => $tags[$sftpUsernameTagKey],
    'password' => $tags[$sftpPwdTagKey],
    'remotefile' => $tags[$sftpPathTagKey]
];

$errorDates = [];
$noDataDates = [];

$campaignName = 'BOQ-CI-SMS-' . $date->format('dFy') . '-LPFReminder';

print "Starting to fetch files\n";

// Find files for today and previous days if there were holidays previously, since all the files uploaded
// on previous holidays need to go to today's campaign
while ($holidayCheckBack >= 0) {
    $file = fetchFile($date, $sftpArray);
    if (!$file) {
        $errorDates[] = $date->format('d-m-Y');
    } else {
        if (!isset($campaignId)) {
            print "Creating campaign name: " . $campaignName . "\n";
            $campaignId = api_campaigns_add($campaignName, null, $baseCampaignId);
            if (!$campaignId) {
                unlink($file);
                print 'Failed while creating the campaign ' . $campaignName;
                $content = <<<EOF
Hello,
    There was a failure when creating campaign $campaignName.
EOF;
                sendErrorEmail($content);
                exit;
            }
        }

         $fileName = basename($file);

         print "Uploading targets from file for the date: ". $date->format('d-m-Y') . "\n";

         try {
             $result = api_targets_fileupload($campaignId, $file, $fileName, false, true, true);
             if (!is_array($result)) {
                 print "Failed uploading targets to campaign with file for the date " . $date->format('d-m-Y') . "\n";
                 $errorDates[] = $date->format('d-m-Y');
             }
         } catch (NoDataException $exception) {
             print "There was no data in the file to be uploaded.\n";
             $noDataDates[] = $date->format('d-m-Y');
         }

         print "Removing file " . $fileName . "\n";
         unlink($file);
    }

    if ($holidayCheckBack !== 0) {
        $date = $date->sub(new DateInterval('P1D'));
        if (!isHoliday($date)) {
            print $date->format('d-m-Y') . " is not holiday. Stopping search for files.\n";
            break;
        }
    }

    $holidayCheckBack--;
}

if (isset($campaignId)) {
    print "Activating campaign\n";
    api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_STATUS, CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE);

    if (isset($tags[$campaignSendRateTagKey])) {
        print "Setting SMS send rate to '{$tags[$campaignSendRateTagKey]}'\n";
        api_campaigns_setting_set($campaignId, 'sendrate', $tags[$campaignSendRateTagKey]);
    }
}

if ($errorDates || $noDataDates) {
    $content = <<<EOF
Hello,

There were failures with regards to the files uploaded.
EOF;

    if ($noDataDates) {
        $noDataDateString .= implode(', ', $noDataDates);
        $content .= <<<EOF
Files for following dates did not have any valid data to be uploaded: $noDataDateString        
EOF;
    }

    if ($errorDates) {
        $errorDatesString = implode(', ', $errorDates);
        $content .= <<<EOF
Files for following dates had some errors. Please check cron logs for more details: $errorDatesString        
EOF;
    }

    print "Sending error email for files that could not be processed or retrieved.\n";
    sendErrorEmail($content, isset($tags[$sftpErrorDestination]) ? $tags[$sftpErrorDestination] : null);
}

print "Done.";

function sendMissingTagsErrorEmail(array $missingTags) {
    $missingTagsString = implode(',', $missingTags);
    $content = <<<EOF
Hello,

Mandatory tags missing for the cron.
Missing Tags: $missingTagsString
The autoload process has failed.
EOF;

    return sendErrorEmail($content);
}

function sendErrorEmail($content, $destination = null) {
    $email = [
        'to' => $destination ?: 'ReachTEL Support <support@ReachTEL.com.au>',
        'cc' => 'ReachTEL Support <support@ReachTEL.com.au>',
        'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
        'subject' => '[ReachTEL] Auto-load error - boq-ci-sms',
    ];

    $email['content'] = $content;
    return api_email_template($email);
}

function isHoliday(DateTime $dateTime) {
    return ($dateTime->format('N') > 5) ||
        api_misc_ispublicholiday('AU', $dateTime->getTimestamp()) ||
        api_misc_ispublicholiday('QLD', $dateTime->getTimestamp());
}

function fetchFile(DateTime $dateTime, array $sftpData) {
    $path = '/tmp/';
    $fileName = 'EQUIFAX_' . $dateTime->format('Ymd') . '.csv.pgp';
    $localFileName = $path . $fileName;
    $sftpData['remotefile'] .= $fileName;
    $sftpData['localfile'] = $localFileName;

    if (api_misc_sftp_get($sftpData)) {
        $contents = file_get_contents($localFileName);
        if (!$contents) {
            unlink($localFileName);
            print "Content from file " . $fileName . " could not be retrieved.\n";
            return false;
        }
        $decrypted = api_misc_pgp_decrypt($contents);

        if (!$decrypted) {
            unlink($localFileName);
            print "PGP decrypt failed for " . $sftpData['localfile'];
            return false;
        }

        unlink($localFileName);
        $localFileName = str_replace('.pgp', '', $localFileName);
        file_put_contents($localFileName, $decrypted);
        return $localFileName;
    }

    print "Failed fetching file " . $fileName . "\n";
    return false;
}
