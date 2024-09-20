<?php

require_once("Morpheus/api.php");
require_once('generic-inotify-functions.php');

/**
 * Mandatory Tags
 * $tags['filename-template'] = '{Date:Ym}{TOKEN}';
 * $tags['brand-column-name'] = "brand";
 * $tags['brand-campaign-name-template-map'] = 'brand1:{PREFIX:Brand_based}-{TOKEN}-{DATE:YMd}, brand2:{PREFIX:Brand_based}-{TOKEN}-{DATE:YMd}' // {TOKEN} will be replaced by brand name
 * $tags['file-destination-column'] = "Destination";
 * $tags['sftp-hostname'] = '1';
 * $tags['sftp-username'] = 1;
 * $tags['sftp-password'] = 1;
 * $tags['sftp-path'] = 1;
 * $tags['failure-notification-email'] = "phillip.berry@equifax.com";
 *
 * Optional Tags
 * $tags['file-date-column'] = "Call Date";
 * $tags['next-attempt-time'] = "11:00:00";
 * $tags['target-key-column'] = "Destination";
 * $tags['dedupe-campaigns'] = "1";
 * $tags['send-rate-modifier'] = "100";
 * $tags['send-rate-calculator'] = 'TR'; // If not specified defaults to Time remaining. TR = Time remaining, PBTR = Percent boost time remaining.
 * $tags['exclusion-column-{columnName}'] = 'value1, value2, value3'; // exclusion column tag can be used multiple times for all exclusions based on column values
 * $tags['public-holiday-exclusion-columns'] = 'value1, value2, value3';
 * $tags['decryptor'] = 'pgp';
 * $tags['decryptor-keys'] = 0x23453,0x545656;
 * $tags['encryptor'] = 'pgp';
 * $tags['encryptor-keys'] = 0X124578,0X78754;
 * $tags['filetype'] = "{type}"; // csv by default, xls or fixed width name eg: westpac_b2k_file
 * $tags['alternative-destination-fields'] = 'destination1,destination2,destination3'
 * $tags['activate-campaign'] = "1"; // 1 by default. 1 = true, 0 = false
 * $tags['csv-header'] = 'header1,header2,header3'; // Define header for csv files with out headers
 * $tags['badrecords-sftp-username']
 * $tags['badrecords-sftp-password']
 * $tags['badrecords-sftp-path']
 * $tags["badrecords-sftp-hostname"]
 */

use Models\Autoload\BrandBasedAutoloadDTO;
use Services\Autoload\Command\GenericLineProcessorCommand;
use Services\Autoload\File\DateTemplateFilenameHandler;
use Services\Autoload\GenericBrandBasedAutoloadStrategy;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\GenericCampaignCreator;
use Services\Campaign\Name\CompositeCampaignNameCollection;
use Services\Campaign\Name\TokenDateTemplateCampaignName;
use Services\Validators\CampaignNameValidator;

$cronId = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cronId);
$filename = $argv[1];

$timeZone = getTimeZone($cronId);

$expectedTags = [
    'sftp-hostname',
    'sftp-password',
    'sftp-username',
    'sftp-path',
    'failure-notification-email',
    'brand-column-name',
    'brand-campaign-name-template-map',
    'filename-template'
];

if (!$tags) {
    sendErrorEmail("No tags set for cron, required tags are: " . implode(", ", $expectedTags));
    exit(1);
}

$missingTags = array_diff($expectedTags, array_keys($tags));
if ($missingTags) {
    sendErrorEmail("There are required tags missing: " . implode(", ", $missingTags));
    exit(1);
}

$filenameHandler = new DateTemplateFilenameHandler($filename, $tags['filename-template']);
if(!$filenameHandler->getDate()) {
    sendErrorEmail("Could not determine the date from the filename '{$filename}' compared to the template '{$tags['filename-template']}'", $tags['failure-notification-email']);
    exit(1);
}

$brandCampaignTemplateMap = explode(',', $tags['brand-campaign-name-template-map']);
$campaignNamerCollection = new CompositeCampaignNameCollection();

foreach ($brandCampaignTemplateMap as $map) {
    list($brandName, $template) = explode(':', trim($map), 2);
     $campaignNamerCollection->add($brandName, new TokenDateTemplateCampaignName(
        $template,
        $brandName,
        $filenameHandler->getDate(),
        new CampaignNameValidator()
    ));
}

$dto = new BrandBasedAutoloadDTO();
$dto
    ->setBrandColumnName($tags['brand-column-name']);

$dto = buildDto($dto, $tags);
$fileProcessor = getFileProcessor($tags);
$exclusionEvaluator = getExclusionEvaluator($tags, $timeZone);

$command = new GenericLineProcessorCommand(
    $dto,
    $timeZone,
    $exclusionEvaluator
);

$sendRateCalc = getSendRateCalculator($tags);

$strategy = new GenericBrandBasedAutoloadStrategy(
    $campaignNamerCollection,
    $fileProcessor,
    new GenericCampaignCreator(new GenericCampaignCloner()),
    $dto,
    $sendRateCalc,
    $timeZone,
    $command
);

if (isset($tags['activate-campaign'])) {
    $strategy->setActivateCampaign((bool) $tags['activate-campaign']);
}

if (isset($tags['dedupe-campaigns'])) {
    $strategy->setDedupe(true);
}

$context = buildAutoloadContext($strategy, $tags, $timeZone, $filename);

print $context->flushLogs();
