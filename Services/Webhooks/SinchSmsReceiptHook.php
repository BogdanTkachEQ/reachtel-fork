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
use Services\Utils\Sms\SinchSmsReceiptStatus;
use Services\Webhooks\Interfaces\QueueableWebhookInterface;

/**
 * Class SinchSmsReceiptHook
 */
class SinchSmsReceiptHook implements QueueableWebhookInterface
{
    /**
     * @var SmsReceiptProcessor
     */
    private $receiptProcessor;

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
        $status = SinchSmsReceiptStatus::translate($hookAttributes['status']);
        $receipt = new SmsDeliveryReceipt();
        $receipt
            ->setStatus($status)
            ->setSupplierId(SmsServiceFactory::SMS_SUPPLIER_SINCH_ID)
            ->setSmsId($hookAttributes['id'])
            ->setStatusUpdateDateTime((new \DateTime())->setTimestamp($hookAttributes['date']));

        return $this->receiptProcessor->saveReceipt($receipt);
    }

    /**
     * @param Request $request
     * @return array
     * @throws WebhookException
     */
    public function getHookAttributesForQueueing(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        if (!isset($content['batch_id'])) {
            throw new WebhookException('Missing batch id in sinch sms receipt');
        }

        if (!isset($content['statuses'])) {
            throw new WebhookException('Missing statuses in sinch sms receipt');
        }

        // We will only have one message in batch and we do not have to save everything that we receive
        $job['id'] = $content['batch_id'];
        $job['status'] = $content['statuses'][0]['status'];
        $job['date'] = (new \DateTime())->getTimestamp();

        return $job;
    }
}
