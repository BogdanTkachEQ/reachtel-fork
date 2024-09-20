<?php

// Should run at 9:51 am Sydney time Monday to Friday

// For this autoload to work there should be cascading campaign templates set up with the names as below,
//MazdaFS-jFy-1-1to8
//LexusFS-jFy-1-1to8
//PowertorqueFS-jFy-1-1to8
//ToyotaFS-jFy-1-1to8
//HinoFS-jFy-1-1to8
//PowerAllianceFS-jFy-1-1to8
//SuzukiFS-jFy-1-1to8

  
//MazdaFS-jFy-1-SMSVoice
//LexusFS-jFy-1-SMSVoice
//PowertorqueFS-jFy-1-SMSVoice
//ToyotaFS-jFy-1-SMSVoice
//HinoFS-jFy-1-SMSVoice
//PowerAllianceFS-jFy-1-SMSVoice
//SuzukiFS-jFy-1-SMSVoice

require_once("Morpheus/api.php");
require_once("Morpheus/scripts/autoload/generic-inotify-functions.php");

use Models\Autoload\AutoloadDTO;
use Services\Autoload\Command\Customers\Toyota\LineProcessorCommand;
use Services\Autoload\XlsAutoloadFileProcessor;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\GenericCampaignCreator;
use Services\Customers\Toyota\Autoload\SmsAutoloadStrategy;
use Services\Customers\Toyota\Autoload\CompositeAutoloadStrategy;
use Services\Autoload\AutoloadContext;

$timeZone = new DateTimeZone('Australia/Sydney');
$date = new DateTime('now', $timeZone);

$tags = api_cron_tags_get(getenv(CRON_ID_ENV_KEY));

if(!empty($argv[1])) $filename = "SMS_Campaign_" . $argv[1] . ".xls";
else $filename = "SMS_Campaign_" . date("Ymd") . ".xls";


$brandCampaignMap = [
    'Mazda Finance' => 'MazdaFS-{{jFy}}-1-SMS1to8',
    'Lexus Financial Services' => 'LexusFS-{{jFy}}-1-SMS1to8',
    'PowerTorque Finance' => 'PowertorqueFS-{{jFy}}-1-SMS1to8',
    'Toyota Finance' => 'ToyotaFS-{{jFy}}-1-SMS1to8',
    'Hino Financial Services' => 'HinoFS-{{jFy}}-1-SMS1to8',
    'Power Alliance Finance' => 'PowerAllianceFS-{{jFy}}-1-SMS1to8',
    'Suzuki Financial Services' => 'SuzukiFS-{{jFy}}-1-SMS1to8',
];
$processor = new XlsAutoloadFileProcessor();
$campaignCreator = new GenericCampaignCreator(new GenericCampaignCloner());

$dto = new AutoloadDTO();
$dto
    ->setDestinationColumnName(SmsAutoloadStrategy::DESTINATION_COLUMN_NAME);

$exclusionEvaluator = getExclusionEvaluator($tags, $timeZone);
$command = new LineProcessorCommand($dto, $timeZone, $exclusionEvaluator);

$strategy1 = new SmsAutoloadStrategy($processor, $brandCampaignMap, $timeZone, $campaignCreator, $command);
$strategy1
    ->setMaxArrear(9);

$brandCampaignMap = [
    'Mazda Finance' => 'MazdaFS-{{jFy}}-1-SMSVoice',
    'Lexus Financial Services' => 'LexusFS-{{jFy}}-1-SMSVoice',
    'PowerTorque Finance' => 'PowertorqueFS-{{jFy}}-1-SMSVoice',
    'Toyota Finance' => 'ToyotaFS-{{jFy}}-1-SMSVoice',
    'Hino Financial Services' => 'HinoFS-{{jFy}}-1-SMSVoice',
    'Power Alliance Finance' => 'PowerAllianceFS-{{jFy}}-1-SMSVoice',
    'Suzuki Financial Services' => 'SuzukiFS-{{jFy}}-1-SMSVoice',
];
$strategy2 = new SmsAutoloadStrategy($processor, $brandCampaignMap, $timeZone, $campaignCreator, $command);
$strategy2
    ->setMinArrear(9)
    ->setMaxArrear(20);

$brandCampaignMap = [
    'Mazda Finance' => 'MazdaFS-{{jFy}}-1-SMSHardship',
    'Lexus Financial Services' => 'LexusFS-{{jFy}}-1-SMSHardship',
    'PowerTorque Finance' => 'PowertorqueFS-{{jFy}}-1-SMSHardship',
    'Toyota Finance' => 'ToyotaFS-{{jFy}}-1-SMSHardship',
    'Hino Financial Services' => 'HinoFS-{{jFy}}-1-SMSHardship',
    'Power Alliance Finance' => 'PowerAllianceFS-{{jFy}}-1-SMSHardship',
    'Suzuki Financial Services' => 'SuzukiFS-{{jFy}}-1-SMSHardship',
];

$strategy3 = new SmsAutoloadStrategy($processor, $brandCampaignMap, $timeZone, $campaignCreator, $command);
$strategy3
    ->setMinArrear(20);

$compositeStrategy = new CompositeAutoloadStrategy();
$compositeStrategy
    ->add($strategy1)
    ->add($strategy2)
    ->add($strategy3);

$publicHolidayRunController = new \Services\Validators\PublicHolidayRunController($date);
$weekendRunController = new \Services\Validators\WeekendRunController($date);
$compositeRuncontroller = new \Services\Validators\CompositeRunController();
$compositeRuncontroller
    ->addRunController($publicHolidayRunController)
    ->addRunController($weekendRunController);

$context = new AutoloadContext(
    $compositeStrategy,
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
