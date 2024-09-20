<?php

// Should run at 9:50 am Sydney time Monday to Friday

// For this autoload to work there should be cascading campaign templates set up with the names as below,
//MazdaFS-jFy-1-Voice
//LexusFS-jFy-1-Voice
//PowertorqueFS-jFy-1-Voice
//ToyotaFS-jFy-1-Voice
//HinoFS-jFy-1-Voice
//PowerAllianceFS-jFy-1-Voice
//SuzukiFS-jFy-1-Voice

require_once("Morpheus/api.php");
require_once("Morpheus/scripts/autoload/generic-inotify-functions.php");

use Models\Autoload\AutoloadDTO;
use Services\Autoload\Command\Customers\Toyota\LineProcessorCommand;
use Services\Autoload\XlsAutoloadFileProcessor;
use Services\Campaign\CascadingCampaignCreatorWrapper;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Customers\Toyota\Autoload\VoiceAutoloadStrategy;
use Services\Autoload\AutoloadContext;
use Services\Validators\CompositeRunController;
use Services\Validators\PublicHolidayRunController;
use Services\Validators\WeekendRunController;

$timeZone = new DateTimeZone('Australia/Sydney');
$date = new DateTime('now', $timeZone);

$tags = api_cron_tags_get(getenv(CRON_ID_ENV_KEY));

if(!empty($argv[1])) $filename = "Outbound_IVR_Campaign_" . $argv[1] . ".xls";
else $filename = "Outbound_IVR_Campaign_" . date("Ymd") . ".xls";


$brandCampaignMap = [
    'Mazda Finance' => 'MazdaFS-{{jFy}}-1-Voice',
    'Lexus Financial Services' => 'LexusFS-{{jFy}}-1-Voice',
    'PowerTorque Finance' => 'PowertorqueFS-{{jFy}}-1-Voice',
    'Toyota Finance' => 'ToyotaFS-{{jFy}}-1-Voice',
    'Hino Financial Services' => 'HinoFS-{{jFy}}-1-Voice',
    'Power Alliance Finance' => 'PowerAllianceFS-{{jFy}}-1-Voice',
    'Suzuki Financial Services' => 'SuzukiFS-{{jFy}}-1-Voice',
];

$dto = new AutoloadDTO();
$dto
    ->setDestinationColumnName(VoiceAutoloadStrategy::DESTINATION_COLUMN_NAME);

$exclusionEvaluator = getExclusionEvaluator($tags, $timeZone);
$command = new LineProcessorCommand($dto, $timeZone, $exclusionEvaluator);

$processor = new XlsAutoloadFileProcessor();
$campaignCreatorFactory = new CascadingCampaignCreatorFactory();
$voiceStrategy = new VoiceAutoloadStrategy(
    $processor,
    $brandCampaignMap,
    $timeZone,
    new CascadingCampaignCreatorWrapper($campaignCreatorFactory),
    $command
);

$publicHolidayRunController = new PublicHolidayRunController($date);
$weekendRunController = new WeekendRunController($date);
$compositeRuncontroller = new CompositeRunController();
$compositeRuncontroller
    ->addRunController($publicHolidayRunController)
    ->addRunController($weekendRunController);

$context = new AutoloadContext(
    $voiceStrategy,
    [
        AutoloadContext::SFTP_HOSTNAME_KEY => $tags['sftp-hostname'],
        AutoloadContext::SFTP_USERNAME_KEY => $tags['sftp-username'],
        AutoloadContext::SFTP_PASSWORD_KEY => $tags['sftp-password'],
        AutoloadContext::SFTP_PATH_KEY => $tags['sftp-path']
    ],
    $compositeRuncontroller
);

$context
    ->setFailureNotificationEmail($tags["sftp-failure-notification"])
    ->setFailureNotificationSubject('[ReachTEL] Auto-load error - ToyotaFS - ' . $filename)
    ->process($filename);

print $context->flushLogs();
