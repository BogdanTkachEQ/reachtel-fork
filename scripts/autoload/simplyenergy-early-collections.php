<?php

require_once("Morpheus/api.php");

use Services\Autoload\AutoloadContext;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Customers\SimplyEnergy\Autoload\AutoloadStrategy;
use Services\File\CSV\CSVFactory;

$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);
$sftpData = [
    AutoloadContext::SFTP_HOSTNAME_KEY => $tags['sftp-hostname'],
    AutoloadContext::SFTP_USERNAME_KEY => $tags['sftp-username'],
    AutoloadContext::SFTP_PASSWORD_KEY => $tags['sftp-password'],
    AutoloadContext::SFTP_PATH_KEY => $tags['sftp-path']
];

if(!empty($argv[1])) $filename = "Outbound_IVR_Campaign_" . $argv[1] . ".xls";
else $filename = "Early_Stage_Collections_" . date("Ymd") . ".csv";
$csvParser = (new CSVFactory())->createBasicCSV();
$processor = new \Services\Autoload\CsvAutoloadFileProcessor($csvParser);
$campaignCreator = new CascadingCampaignCreatorFactory();
$strategy = new AutoloadStrategy($processor, null, $campaignCreator);
$publicHolidayRunController = new \Services\Validators\PublicHolidayRunController();
$context = new AutoloadContext($strategy, $sftpData, $publicHolidayRunController);
$context
    ->setFailureNotificationEmail($tags["sftp-failure-notification"])
    ->setFailureNotificationSubject('[ReachTEL] Auto-load error - SimplyEnergy Early collections - ' . $filename)
    ->process($filename);

print $context->flushLogs();
