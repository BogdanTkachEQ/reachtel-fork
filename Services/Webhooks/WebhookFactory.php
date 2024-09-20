<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Services\Exceptions\Webhooks\WebhookException;
use Services\Sms\InboundSmsProcessor;
use Services\Sms\SmsReceiptProcessor;
use Services\Webhooks\Interfaces\QueueableWebhookInterface;
use Services\Utils\Webhooks\WebhookType;

/**
 * Class WebhookFactory
 */
class WebhookFactory
{
    /**
     * @param WebhookType $type
     * @return QueueableWebhookInterface
     * @throws WebhookException
     */
    public function getWebhook(WebhookType $type)
    {
        switch ($type) {
            case WebhookType::YABBR_SMS_RECEIPT_HOOK():
                $processor = new SmsReceiptProcessor();
                $hook = new YabbrSmsReceiptHook($processor);
                break;

            case WebhookType::SINCH_SMS_RECEIPT_HOOK():
                $processor = new SmsReceiptProcessor();
                $hook = new SinchSmsReceiptHook($processor);
                break;

            case WebhookType::SINCH_INBOUND_SMS_HOOK():
                $processor = new InboundSmsProcessor();
                $hook = new SinchInboundSmsHook($processor);
                break;

            default:
                $message = 'invalid web hook name passed: ' . $type->getValue();
                api_error_raise($message);
                throw new WebhookException($message);
        }

        return $hook;
    }
}
