<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Models\SmsDeliveryReceipt;
use Services\Exceptions\Webhooks\WebhookException;
use Services\Http\Request;
use Services\Sms\SmsReceiptProcessor;
use Services\Suppliers\SmsServiceFactory;
use Services\Suppliers\Yabbr\SmsFunctions;
use Services\Webhooks\Interfaces\QueueableWebhookInterface;

/**
 * Class YabbrSmsReceiptHook
 */
class YabbrSmsReceiptHook implements QueueableWebhookInterface
{
    /** @var SmsReceiptProcessor */
    private $receiptProcessor;

    /**
     * YabbrSmsReceiptHook constructor.
     * @param SmsReceiptProcessor $processor
     */
    public function __construct(SmsReceiptProcessor $processor)
    {
        $this->receiptProcessor = $processor;
    }

    /**
     * @param array $hookAttributes
     * @return boolean
     * @throws WebhookException
     */
    public function runQueuedJob(array $hookAttributes)
    {
        foreach ($hookAttributes as $message) {
            $status = SmsFunctions::getStatusFromReceipts($message['receipts']);
            $date = SmsFunctions::getStatusUpdateDateTimeFromReceipts($message['receipts']);
            $deliveryReceipt = new SmsDeliveryReceipt();
            $deliveryReceipt
                ->setSmsId($message['id'])
                ->setSupplierId(SmsServiceFactory::SMS_SUPPLIER_YABBR_ID)
                ->setStatus($status)
                ->setStatusUpdateDateTime($date);

            $this->receiptProcessor->saveReceipt($deliveryReceipt);
        }

        return true;
    }

    /**
     * @param Request $request
     * @return array
     * @throws WebhookException
     */
    public function getHookAttributesForQueueing(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        if (!isset($content['messages'])) {
            throw new WebhookException('Missing messages in yabbr sms receipt');
        }

        $messages = $content['messages'];

        $job = [];
        foreach ($messages as $message) {
            if (!isset($message['id']) || !isset($message['receipts'])) {
                continue;
            }
            // We do not have to store every thing that we receive
            $job[] = ['id' => $message['id'], 'receipts' => $message['receipts']];
        }

        return $job;
    }
}
