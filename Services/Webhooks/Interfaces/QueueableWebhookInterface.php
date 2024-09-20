<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks\Interfaces;

use Services\Exceptions\Webhooks\WebhookException;
use Services\Http\Request;

/**
 * Interface WebhookInterface
 */
interface QueueableWebhookInterface
{
    /**
     * @param array $hookAttributes
     * @return boolean
     * @throws WebhookException
     */
    public function runQueuedJob(array $hookAttributes);

    /**
     * @param Request $request
     * @return array
     * @throws WebhookException
     */
    public function getHookAttributesForQueueing(Request $request);
}
