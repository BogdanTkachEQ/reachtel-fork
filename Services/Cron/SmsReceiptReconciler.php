<?php

namespace Services\Cron;

use \DateTime;
use Services\Cron\SmsReceiptReconciler\AbstractSmsSentProvider;
use Services\ConfigReader;

class SmsReceiptReconciler
{
    /** @var int */
    private $updatedCount = 0;

    /** @var int */
    private $skippedCount = 0;

    /** @var int */
    private $totalUpdatedCount = 0;

    /** @var int */
    private $totalSkippedCount = 0;

    /** @var ConfigReader */
    private $config = null;

    /**
     * @param ConfigReader $config
     */
    public function __construct(ConfigReader $config = null)
    {
        if (!$config) {
            $config = ConfigReader::getInstance()->getConfig(ConfigReader::SMS_RECEIPT_RECONCILER_CONFIG_TYPE);
        }

        $this->config = $config;
    }

    /**
     * Check for and fix missing sms receipts
     *
     * @param SmsSentProviderInterface $provider
     *
     * @return SmsReceiptReconciler
     */
    public function process(AbstractSmsSentProvider $provider, DateTime $startDate, DateTime $endDate)
    {
        $this->updatedCount = 0;
        $this->skippedCount = 0;

        // Get all sms_out without receipts in last X days
        $unresolvedSms = $provider->getUnresolvedSms($startDate, $endDate);

        // Get a batch
        // array_splice removes `batchSize` elements from `$unresolvedSms`
        // so loop until there are none left
        while (count($unresolvedSms) > 0) {
            if (count($unresolvedSms) > $this->config['batchSize']) {
                $batchSms = array_splice($unresolvedSms, 0, $this->config['batchSize']);
            } else {
                // do the last few
                $batchSms = array_splice($unresolvedSms, 0);
            }

            // prepare the query
            $placeholders = '(' . implode(',', array_fill(0, count($batchSms), '?')) . ')';
            $batchIds = array_map(
                function ($sms) {
                    return $sms['supplierid'];
                },
                $batchSms
            );

            // Get status from sms_raw_receipts
            // Some have more than one, so take the latest (first since we're ordering by date)
            $sql = <<<EOF
SELECT s.id, s.supplier, s.supplierid, s.timestamp, s.status, s.code, s.supplierdate
FROM `sms_raw_receipts` s
INNER JOIN (
    SELECT
        supplierid,
        MAX(id) AS id
    FROM
        `sms_raw_receipts`
    WHERE supplierid in $placeholders
    GROUP BY supplierid
) s2 ON s.supplierid = s2.supplierid AND s.id = s2.id;
EOF;
            $rs = api_db_query_read($sql, $batchIds);

            if ($rs->RowCount() > 0) {
                $rows = $rs->getAssoc();
                foreach ($rows as $receipt) {
                    $provider->updateReceipt($receipt);
                    $this->updatedCount++;
                    $this->totalUpdatedCount++;
                }
            }

            $skipped = count($batchIds) - $rs->RowCount();

            if ($skipped > 0) {
                $this->skippedCount += $skipped;
                $this->totalSkippedCount += $skipped;
            }
        }

        return $this;
    }

    /**
     * Return interim status message
     *
     * @return string
     */
    public function getInterimStatusMessage()
    {
        return $this->getStatusMessage(false);
    }

    /**
     * @var bool $total
     *
     * @return string
     */
    public function getStatusMessage($total = true)
    {
        $updated = $total ? $this->totalUpdatedCount : $this->updatedCount;
        $skipped = $total ? $this->totalSkippedCount : $this->skippedCount;
        $totalPrefix = $total ? 'Total: ' : '';
        return sprintf('%s%d updated and %d skipped.', $totalPrefix, $updated, $skipped);
    }
}
