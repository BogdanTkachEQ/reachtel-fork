<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Archiver;

use Services\ActivityLogger;
use Services\Exceptions\Targets\TargetArchiveException;
use Services\Utils\ActivityLoggerActions;

/**
 * Class BulkTargetArchiver
 * Archives a given campaign's targets - breaking the work into smaller chunks
 */
class BulkTargetArchiver
{

    private $logs = [];
    /**
     * @var int
     */
    private $workingLimit;
    /**
     * @var ActivityLogger
     */
    private $logger;
    /**
     * @var bool
     */
    private $livePrintLog;

    public function __construct($workingLimit, ActivityLogger $logger, $livePrintLog = false)
    {
        $this->workingLimit = $workingLimit;
        $this->logger = $logger;
        $this->livePrintLog = $livePrintLog;
    }

    /**
     * Archives the campaign's targets.  Returns the number of targets archived.
     *
     * @param $campaignId
     * @return bool|int
     */
    public function archiveCampaign($campaignId, $deleteTargets = false)
    {
        if (!($targetCount = api_targets_count_campaign_total($campaignId))) {
            $this->addLog("Campaign {$campaignId} has no targets, skipping");
            return 0;
        }

        $this->logger->addLog(
            KEYSTORE_TYPE_CAMPAIGNS,
            ActivityLoggerActions::ACTION_CAMPAIGN_ARCHIVE_TARGETS,
            'Archive all targets for campaign ' . $campaignId,
            $campaignId
        );

        $this->addLog("Attempting to archive targets from campaign id: $campaignId");

        $runner = function ($offset) use ($campaignId) {
            $process = api_targets_archive(
                $campaignId,
                false,
                ArchiverEnum::SYSTEM(),
                $this->workingLimit,
                $offset
            );
            if (!$process) {
                $this->addLog("Failed to archive targets from $campaignId");
                throw new TargetArchiveException("Failed to archive targets from $campaignId");
            }
        };

        $this->runProcess($targetCount, $runner);
        if ($deleteTargets) {
            api_targets_delete_all($campaignId);
        }
        return $targetCount;
    }

    /**
     * @param $campaignId
     * @return bool|int
     */
    public function deArchiveCampaign($campaignId, $deleteArchivedTargets = false)
    {
        if (!($targetCount = api_targets_archive_count_campaign_total($campaignId))) {
            $this->addLog("Campaign {$campaignId} has no archived targets");
            return 0;
        }

        $this->logger->addLog(
            KEYSTORE_TYPE_CAMPAIGNS,
            ActivityLoggerActions::ACTION_CAMPAIGN_ARCHIVE_TARGETS,
            'Dearchive targets for campaign ' . $campaignId,
            $campaignId
        );

        $this->addLog("Attempting to de-archive targets from campaign id: $campaignId");

        $runner = function ($offset) use ($campaignId) {
            $process = api_targets_dearchive(
                $campaignId,
                false,
                ArchiverEnum::SYSTEM(),
                $this->workingLimit,
                $offset
            );
            if (!$process) {
                $this->addLog("Failed to dearchive targets from $campaignId");
                throw new TargetArchiveException("Failed to dearchive targets from $campaignId");
            }
        };

        $this->runProcess($targetCount, $runner);

        if ($deleteArchivedTargets) {
            api_targets_archive_delete_all($campaignId, ArchiverEnum::SYSTEM());
        }
        return $targetCount;
    }

    private function runProcess($targetCount, \Closure $process)
    {
        $chunks = ceil($targetCount / $this->workingLimit);
        for ($i = 0; $i < $chunks; $i++) {
            $offset = $i * $this->workingLimit;
            $log = "Processing chunk " . ($i + 1) . " / {$chunks} | " .
                "targets: " . ($offset + 1) . "-" . ($offset + $this->workingLimit) . " / $targetCount";
            $this->addLog($log);
            $process($offset);
        }
    }

    private function addLog($log)
    {
        $this->logs[] = $log;
        if ($this->livePrintLog) {
            echo $log . "\n";
        } else {
            api_misc_audit("Archiver", $log);
        }
    }

    public function getLog()
    {
        return $this->logs;
    }
}
