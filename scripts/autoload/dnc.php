<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

require_once("Morpheus/api.php");

use Services\ActivityLogger;
use Services\Autoload\AutoloadContext;
use Services\Autoload\CsvAutoloadFileProcessor;
use Services\Autoload\DncAutoloadStrategy;
use Services\Autoload\XlsAutoloadFileProcessor;
use Services\File\CSV\CSVFactory;
use Services\Validators\DailyRunController;

$filename = $argv[1];

if (!preg_match('/^((un)?subscribe)[\_\-](phone|sms|email)[\_\-](\d)+\.(csv|xls)$/i', $filename, $matches)) {
    print "Invalid file name received";
    exit;
}

$isSubscription = (strtolower($matches[1]) === 'subscribe');
$groupId =$argv[2];
$type = $matches[3];
$listId = $matches[4];
$fileType = $matches[5];

if (strtolower($fileType) === 'csv') {
    $csvParser = (new CSVFactory())->createBasicCSV();
    $fileProcessor = new CsvAutoloadFileProcessor($csvParser);
} else {
    $fileProcessor = new XlsAutoloadFileProcessor();
}

$dncAutoloadStrategy = new DncAutoloadStrategy(
    $fileProcessor,
    ActivityLogger::getInstance(),
    $type,
    $groupId,
    $listId,
    $isSubscription
);

$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);

$context = new AutoloadContext(
    $dncAutoloadStrategy,
    [
        AutoloadContext::SFTP_HOSTNAME_KEY => $tags['sftp-hostname'],
        AutoloadContext::SFTP_USERNAME_KEY => $tags['sftp-username'],
        AutoloadContext::SFTP_PASSWORD_KEY => $tags['sftp-password'],
        AutoloadContext::SFTP_PATH_KEY => $tags['sftp-path'] . $groupId . '/'
    ],
    new DailyRunController()
);

$context
    ->setFailureNotificationEmail($tags["sftp-failure-notification"])
    ->setFailureNotificationSubject('[ReachTEL] DNC Autoload error. Filename: ' . $filename)
    ->process($filename);

print $context->flushLogs();
