<?php

namespace Services\Cron\SmsReceiptReconciler;

use \DateTime;

class SmsApiSentProvider extends AbstractSmsSentProvider
{
    /**
     * @inheritDoc
     */
    public function getUnresolvedSms(DateTime $startDate, DateTime $endDate)
    {
        $sql = <<<EOF
SELECT s.id, s.supplierid
FROM sms_out s
LEFT JOIN sms_out_status st on (s.id = st.id)
WHERE st.id IS NULL
AND s.timestamp >= ?
AND s.timestamp <= ?
AND supplierid != '';
EOF;
        $rs = api_db_query_read($sql, [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')]);

        return $rs->GetArray();
    }
}
