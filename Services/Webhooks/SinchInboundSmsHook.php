<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Models\Sms;
use Services\Exceptions\Webhooks\WebhookException;
use Services\Http\Request;
use Services\Sms\InboundSmsProcessor;
use Services\Webhooks\Interfaces\QueueableWebhookInterface;

/**
 * Class SinchInboundSmsHook
 */
class SinchInboundSmsHook implements QueueableWebhookInterface
{
    /** @var InboundSmsProcessor */
    private $processor;

    public function __construct(InboundSmsProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param array $hookAttributes
     * @return boolean
     * @throws WebhookException
     */
    public function runQueuedJob(array $hookAttributes)
    {
        $sms = new Sms();
        $sms
            ->setFrom($hookAttributes['from'])
            ->setTo($hookAttributes['to'])
            ->setContent($hookAttributes['body'])
            ->setStatusUpdateTime(
                \DateTime::createFromFormat('Y-m-d\TH:i:s.uO', $hookAttributes['received_at']) ?: new \DateTime()
            );

        return $this->processor->saveSms($sms);
    }

    /**
     * @param Request $request
     * @return array
     * @throws WebhookException
     */
    public function getHookAttributesForQueueing(Request $request)
    {
        return json_decode($request->getContent(), true);
    }
}
