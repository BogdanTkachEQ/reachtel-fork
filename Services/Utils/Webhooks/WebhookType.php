<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Webhooks;

use MabeEnum\Enum;

/**
 * Class WebhookType
 */
class WebhookType extends Enum
{
    const YABBR_SMS_RECEIPT_HOOK = 'yabbrsmsreceipt';
    const SINCH_SMS_RECEIPT_HOOK = 'sinchsmsreceipt';
    const SINCH_INBOUND_SMS_HOOK = 'sinchinboundsms';
}
