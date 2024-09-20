<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

require_once("Morpheus/api.php");

$cronid = getenv('CRON_ID');
if (!$cronid) {
    die("ERROR: Invalid env var CRON_ID\n");
}

$tags = api_cron_tags_get($cronid);
$campaigns = [];

if (isset($tags['campaigns'])) {
    $campaigns = explode(',', $tags['campaigns']);
} else {
    if (isset($tags['run-date'])) {
        try {
            $start = new DateTime($tags['run-date']);
        } catch (Exception $e) {
            print "Invalid run date given: '" . $tags['run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
            exit;
        }
    } else {
        echo "A start date via tag 'run-date' must be given!";
        exit(1);
    }

    if (isset($tags['end-run-date'])) {
        try {
            $end = new DateTime($tags['end-run-date']);
        } catch (Exception $e) {
            print "Invalid end run date given:  '" . $tags['end-run-date'] . "' check https://www.php.net/manual/en/datetime.formats.php for valid formats.\n";
            exit;
        }
    } else {
        echo "An end run date via tag 'end-run-date' must be given!";
        exit(1);
    }

    print "Starting to remediate data in campaigns that ran between " . $tags['run-date'] . " and " . $tags['end-run-date'] . "\n";

    $campaigns = api_campaigns_get_campaigns_lastsend_during_period($start, $end);
}

if (isset($tags['campaign-types'])) {
    $campaignTypes = array_map('trim', explode(',', $tags['campaign-types']));
}

$validators = [];
$targetsRemoved = 0;

foreach ($campaigns as $campaignId) {
    api_db_ping();
    $type = api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_TYPE);
    if (
        isset($campaignTypes) &&
        !in_array($type, $campaignTypes)
    ) {
        continue;
    }

    if (!in_array($type, $validators)) {
        try {
            $validators[$type] = \Services\Container\ContainerAccessor::getContainer()
                ->get(\Services\Validators\Factory\CampaignTargetDataValidatorFactory::class)
                ->create(\Models\CampaignType::byValue($type));
        } catch (\Services\Exceptions\Validators\CampaignTargetDataValidatorFactoryException $exception) {
            $validators[$type] = null;
        }
    }

    if (is_null($validators[$type])) {
        continue;
    }

    /** @var \Services\Validators\Interfaces\CampaignTargetDataValidatorInterface $validator */
    $validator = $validators[$type];

    print "Data remediation for Campaign " . $campaignId . " started...\n";

    $targets = api_campaigns_get_all_targets($campaignId);

    foreach ($targets as $target) {
        api_db_ping();
        $mergeData = api_targets_get_merge_data($target['targetid']);
        $mergeDataArray = [];
        foreach ($mergeData as $data) {
            $mergeDataArray[$data['element']] = $data['value'];
        }
        $validator
            ->setMergeData($mergeDataArray)
            ->setTargetKey($target['targetkey']);

        if (isset($tags['remove-data-if-invalid-targetkey']) && $tags['remove-data-if-invalid-targetkey']) {
            try {
                $validator->isValid();
            } catch (\Services\Exceptions\Validators\InvalidTargetKeyException $exception) {
                api_db_ping();
                if (!api_campaigns_delete_target($campaignId, $target['targetkey'])) {
                    $message = 'Failed to remove target id: ' . $target['targetid'];
                    api_error_raise($message);
                    print $message . "\n";
                    continue;
                }
                $targetsRemoved++;
                continue;
            } catch (\Services\Exceptions\Validators\ValidatorRuntimeException $exception) {
                // do nothing
            }
        }

        $sanitizedMergeData = $validator->getSanitizedMergeData();

        foreach ($sanitizedMergeData as $element => $value) {
            $sql = 'UPDATE merge_data set `value` = ? WHERE `targetkey` = ? and `campaignid` = ? and `element` = ?';
            api_db_query_write($sql, [$value, $target['targetkey'], $campaignId, $element]);
        }
    }

    print "Compelted data remediation for campaign: " . $campaignId . "\n";
}

print "Total targets removed : " . $targetsRemoved . "\n";


if (!isset($tags['campaigns'])) {
    $newStart = clone $end;
    $newEnd = clone $end;
    $interval = $start->diff($end);
    $newEnd = $end->add($interval);

    api_cron_tags_set(
        $cronid,
        [
            'run-date' => $newStart->format('Y-m-d H:i:s'),
            'end-run-date' => $newEnd->format('Y-m-d H:i:s'),
        ]
    );

    print "Updated new run dates\n";
}

print "Job Done!!!!";
