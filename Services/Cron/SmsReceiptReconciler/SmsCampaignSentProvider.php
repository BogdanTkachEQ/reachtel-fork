<?php

namespace Services\Cron\SmsReceiptReconciler;

use \DateTime;

class SmsCampaignSentProvider extends AbstractSmsSentProvider
{
    /**
     * @inheritDoc
     */
    public function getUnresolvedSms(DateTime $startDate, DateTime $endDate)
    {
        $sql = <<<EOF
SELECT s.eventid as id, s.supplieruid as supplierid
FROM sms_sent s
LEFT JOIN sms_status st on (s.eventid = st.eventid)
WHERE st.eventid IS NULL
AND s.timestamp >= ?
AND s.timestamp <= ?
AND supplieruid != '';
EOF;
        $rs = api_db_query_read($sql, [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')]);

        return $rs->GetArray();
    }
}
