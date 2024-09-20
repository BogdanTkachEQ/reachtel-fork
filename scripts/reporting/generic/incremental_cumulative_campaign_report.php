#!/usr/bin/php
<?php

/**
 *
 * Tags:
 * group-id
 * campaign-type
 * campaign-prefix
 * campaign-name-wildcard // Use % as wildcard operator eg: AcmeInc-32March2020-SecondPart => AcmeInc-%-SecondPart
 * campaign-ids
 * total-campaigns
 * reporting-destination
 * filename-prefix
 * sftp-out-hostname
 * sftp-out-username
 * sftp-out-password
 * sftp-out-path
 * sftp-failure-notification
 * failure-notification-email
 * run-date
 * end-run-date
 * campaign-creation-from
 * campaign-creation-to
 * output-columns //eg: targetkey, column1, column2;
 * output-columns // with disposition columns: targetkey, {%disposition:name=column1,columns=[column1,column2,column3],separator=comma%}
 * output-columns // with default value columns: targetkey, column1, {%defaultvalue:name=column2,value=value2,valuecolumn=column2%}, column3
 * // valuecolumn parameter above is non mandatory. If specified, it will check if there is an actual column in the report that has a value,
 * //then use that value or fallback to default value configured.
 *
 * output-columns // with date formatter: targetkey, column1, {%dateformatter:name=datecolumn,column=column2,format=d-m-Y H:i:s%}, column3
 * output-columns // with text formatter: targetkey, column1, {%textformatter:name=textcolumn,column=column2,replacelinefeedby= ,maxlength=10,useellipsis=1%}, column3
 * header-map //eg: column1:map1,column2:map2
 * hide-header //eg: 1 or 0
 * filetype // csv, xls (Default is csv) TODO: Add fixed width handler
 * print-output
 * filter //{%equal:field=Q1,value=123%},{%like:field=Q,value=123,compare=[field,value]%},{%notequal:field=Q2,value=123%},{%notlike:field=P,value=123,compare=[field,value]%}
 * pgpkeys 0X124578,0X78754
 * filename-date-format // if left empty will remove date from file
 */

use Models\CampaignType;
use Services\Container\ContainerAccessor;
use Services\Reports\Builders\FilterRulesEngineBuilder;
use Services\File\Factory\CryptoFactory;
use Services\Reports\ArrayRulesEngineDecorator;
use Services\Reports\Builders\ReportOutputBuilder;
use Services\Reports\CsvArrayToFileConverter;
use Services\Reports\Exceptions\NoDataGeneratedException;
use Services\Reports\ReportOutputBuilderDirector;
use Services\Reports\RowDataModifierTemplateParser;
use Services\Reports\XlsArrayToFileConverter;

require_once("Morpheus/api.php");

$cronId = getenv('CRON_ID');
$tags = api_cron_tags_get($cronId);

if ((!isset($tags["reporting-destination"]) && !isset($tags["sftp-hostname"])) || !isset($tags['campaign-type'])) {
    print __FILE__." requires tags 'reporting-destination',  'sftp-hostname', 'campaign-type'";
    exit;
}

if (!isset($tags['group-id']) && !isset($tags['campaign-prefix']) && !isset($tags['campaign-ids']) && !isset($tags['campaign-name-wildcard'])) {
	print __FILE__.' requires at least one of: group-id, campaign-prefix or campaign-ids tags to be set';
	exit;
}

$campaignTypes = new \MabeEnum\EnumSet(\Models\CampaignType::class);
$campaignType = strtolower(trim($tags['campaign-type']));
if (!CampaignType::hasValue($campaignType)) {
    print "The given campaign type '" . $campaignType . "' is invalid - try one of " . implode(", ", CampaignType::getValues())."\n";
    exit;
} else {
    $campaignType = CampaignType::byValue($campaignType);
    $campaignTypes->attach($campaignType);
}

if (isset($tags["sftp-hostname"])) {
    foreach(['sftp-hostname', 'sftp-username', 'sftp-password', 'sftp-path', 'failure-notification-email'] as $tagname) {
        if(!isset($tags[$tagname])) {
            print "Please define a '{$tagname}' tag\n";
            exit;
        }
    }
}

$groupId = null;
if (isset($tags['group-id'])) {
    $groupId = $tags['group-id'];
    if (!api_groups_checkidexists($groupId)){
        print "That group id is invalid";
        exit;
    }
}

if (isset($tags['campaign-prefix']) || isset($tags['campaign-name-wildcard'])) {
    if (isset($tags['campaign-prefix'])) {
        $regex = '^' . preg_quote($tags['campaign-prefix']) . '.*$';
    } else {
        $regex = '^' . str_replace('%', '.*', preg_quote($tags['campaign-name-wildcard'])) . '$';
    }

    $totalCampaigns = isset($tags['total-campaigns']) ? $tags['total-campaigns'] : 10;
    $campaigns = api_campaigns_list_all(
        null,
        null,
        $totalCampaigns,
            [
                'regex' => $regex,
                'campaigntypes' => $campaignTypes->getValues(),
                'groupid' => $groupId
            ]
    );
} elseif (isset($tags['campaign-ids'])) {
    $campaigns = explode(',', $tags['campaign-ids']);
    if ($groupId) {
        $invalidCampaigns = array_diff($campaigns, api_groups_get_all_campaignids($groupId, $campaignTypes));
        if(!empty($invalidCampaigns)) {
            print "The given campaign ids were invalid " . implode(",", $invalidCampaigns) ;
            print " - they don't belong to the given group id or are the wrong type\n";
            exit;
        }
    }
} elseif($groupId) {
	$campaigns = api_groups_get_all_campaignids($groupId, $campaignTypes);
} else {
    print "No group-id, campaign-prefix or campaign-ids specified. Exiting...\n";
    exit;
}

$options = [];
if (isset($tags['run-date'])) {
    try {
        $start = new DateTime($tags['run-date']);
    } catch (Exception $e) {
        print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
        exit;
    }

    if (isset($tags['end-run-date'])) {
        try {
            $end = new DateTime($tags['end-run-date']);
        } catch (Exception $e) {
            print "Invalid end run date given:  '" . $tags['end-run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
            exit;
        }
    } else {
        $end = clone $start;
        $end->add(new DateInterval('P1D'));
    }

    $options['start'] = $start->format('Y-m-d H:i:s');
    $options['end'] = $end->format('Y-m-d H:i:s');
}

$options['extra_columns'] = [
    [
        'header' => 'campaign',
        'value' => function($campaignId) {
            return api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_NAME);
        }
    ],
    [
        'header' => 'created',
        'value' => function($campaignId) {
            return date('Y-m-d H:i:s', api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_CREATED_TIME));
        }
    ]
];

if (isset($tags['campaign-creation-from'])) {
    try {
        $creationDateFrom = new DateTime($tags['campaign-creation-from']);
    } catch (Exception $e) {
        print "Invalid campaign-creation-from date given: '" . $tags['campaign-creation-from'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
        exit;
    }

    try {
        $creationDateTo = new DateTime(isset($tags['campaign-creation-to']) ? $tags['campaign-creation-to'] : '');
    } catch (Exception $e) {
        print "Invalid campaign-creation-to date given: '" . $tags['campaign-creation-to'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
        exit;
    }
}


$requiredSettings = [
    CAMPAIGN_SETTING_GROUP_OWNER,
    CAMPAIGN_SETTING_TYPE,
];

if (isset($creationDateFrom)) {
    $requiredSettings = array_merge($requiredSettings, [
        CAMPAIGN_SETTING_CREATED_TIME
    ]);
}

// Validate campaigns
foreach ($campaigns as $i => $campaignid) {
	$settings = api_campaigns_setting_get_multi_byitem(
		$campaignid,
		$requiredSettings
	);

	if (
		isset($creationDateFrom) &&
		(
			!isset($settings[CAMPAIGN_SETTING_CREATED_TIME]) ||
			$settings[CAMPAIGN_SETTING_CREATED_TIME] < $creationDateFrom->getTimestamp() ||
			$settings[CAMPAIGN_SETTING_CREATED_TIME] >= $creationDateTo->getTimestamp()
		)
	) {
		unset($campaigns[$i]);
		continue;
	}

	if ($campaignType->getValue() !== $settings[CAMPAIGN_SETTING_TYPE]) {
		print "Exiting since campaigns are of different types {$settings[CAMPAIGN_SETTING_TYPE]} than the given type.";
		exit;
	}

	if (isset($groupOwner) && $groupOwner !== $settings[CAMPAIGN_SETTING_GROUP_OWNER]) {
		print "Exiting since campaigns have different group owners.";
		exit;
	}
	$groupOwner = $settings[CAMPAIGN_SETTING_GROUP_OWNER];
}

if ($campaignType->is(CampaignType::PHONE())) {
	$options['get_last_attempted_time'] = true;
}

if ($campaignType->is(CampaignType::SMS())) {
	$options['return_sms_content'] = true;
}

$report = api_campaigns_report_cumulative_array($campaigns, $campaignType->getValue(), $options);

if ($report === false) {
    print "Failed to create report. This could be because none of the campaigns passed have any data to generate report.\n";
    exit;
}

array_shift($report);

if (!isset($tags['filetype'])) {
	$tags['filetype'] = 'csv';
}

switch ($tags['filetype']) {
	case 'xls' :
		$converter = new XlsArrayToFileConverter();
		break;

	case 'csv':
	default:
		$converter = ContainerAccessor::getContainer()->get(CsvArrayToFileConverter::class);
		break;
}

$decorator = ContainerAccessor::getContainer()->get(ArrayRulesEngineDecorator::class);

if (isset($tags['pgpkeys'])) {
    $encryptor = CryptoFactory::create(CryptoFactory::PGP);
    $encryptor->setKeys(explode(',', $tags['pgpkeys']));
} else {
    $encryptor = null;
}

$outputBuilder = new ReportOutputBuilder(
    $converter,
	$encryptor
);

$director = new ReportOutputBuilderDirector(
    $outputBuilder,
    ContainerAccessor::getContainer()->get(RowDataModifierTemplateParser::class),
    ContainerAccessor::getContainer()->get(FilterRulesEngineBuilder::class)
);

if (isset($tags['header-map'])) {
    $director->setHeaderMapString($tags['header-map']);
}

if (isset($tags['output-columns'])) {
    $director->setOutputColumnString($tags['output-columns']);
}

if (isset($tags['filter'])) {
    try {
        $director->setFilterString($tags['filter']);
    } catch (Exception $exception) {
        print "Exception occured when reading report filters: " . $exception->getMessage();
        failure_notification($tags['failure-notification-email'], "Cumulative report failure", "Report builder ended up in failure. Message: Error occured when reading report filters" . $exception->getMessage());
        exit;
    }
}

try {
    $filePath = $director
        ->getBuilder()
        ->hideHeader(isset($tags['hide-header']) && $tags['hide-header'])
        ->setData($report)
        ->build();
} catch (NoDataGeneratedException $exception) {
    print "No data generated";
    exit;
} catch (Exception $exception) {
    print "Exception occured when running report builder. " . $exception->getMessage();
    failure_notification($tags['failure-notification-email'], "Cumulative report failure", "Report builder ended up in failure. Message: " . $exception->getMessage());
    exit;
}

$filenamePrefix = isset($tags['filename-prefix']) ? $tags['filename-prefix'] : 'cumulative_campaign_report';

$filenameDate = isset($tags['filename-date-format']) ? date($tags['filename-date-format']) : date("Ymd");

$filename = $filenamePrefix . $filenameDate . "." . $tags['filetype'];

if (isset($tags['pgpkeys'])) {
    $filename .= '.pgp';
}

if (!isset($tags['pgpkeys']) && isset($tags['print-output']) && $tags['filetype'] === 'csv') {
    print file_get_contents($filePath)."\n";
}

if (isset($tags["reporting-destination"])) {
    $email["to"] = $tags["reporting-destination"];
    $email["subject"] = "[ReachTEL] Cumulative campaign report";
    $email["textcontent"] = "Hello,\n\nPlease find attached the ReachTEL cumulative campaign report" . ".\n\n";
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";

    $email["attachments"][] = array("content" => file_get_contents($filePath), "filename" => $filename);

    api_email_template($email);
}

if (isset($tags["sftp-hostname"])) {

    print "Connecting...\n";

    $options = [
        "hostname" => $tags["sftp-hostname"],
        "username" => $tags["sftp-username"],
        "password" => $tags["sftp-password"],
        "localfile" => $filePath,
        "remotefile" => $tags["sftp-path"] . $filename
    ];

    $result = api_misc_sftp_put_safe($options);
    unlink($filePath);

    if (!$result) {
        failure_notification(
            $tags['failure-notification-email'],
            "SFTP Failure",
            "Failed to upload cumulative campaign report to sFTP server: {$filename}. The file could not be cached for upload at a later time. Please contact support as the report will have to be re-run."
        );

        print "File upload did not work and could not be cached.";
        exit;
    }

    if (api_error_checkforerror()) {
        // There are errors even though sftp cache worked.
        failure_notification(
            $tags['failure-notification-email'],
            "SFTP Failure",
            "Failed to upload cumulative campaign report to sFTP server: {$filename}. The file would have been cached for upload at a later time. Please contact support to upload the cached file."
        );

        print "Upload failed but the file would have been cached. Please check the cache.";
        exit;
    }

    print "Upload to sFTP seems to have worked:\n";
    exit;
}

unlink($filePath);

function failure_notification($to, $subject, $error) {
    $email["to"] = $to;
    $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
    $email["subject"] = "[ReachTEL] Cumulative campaign report - {$subject}";
    $email["textcontent"] = "Hello,\n\nThere was an error with this report: $error\n";
    $email["htmlcontent"] = nl2br($email["textcontent"]);
    api_email_template($email);
    print "$error";
}
