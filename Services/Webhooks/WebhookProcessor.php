<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Services\Exceptions\Webhooks\InvalidWebhookException;
use Services\Exceptions\Webhooks\WebhookAuthenticationException;
use Services\Exceptions\Webhooks\WebhookException;
use Services\Http\Request;
use Services\Utils\Webhooks\WebhookType;

/**
 * Class WebhookProcessor
 */
class WebhookProcessor
{
    const REQUEST_WEBHOOK_NAME_KEY = 'name';
    const QUEUE_NAME = 'webhook';
    const HOOK_ATTRIBUTES_KEY = 'hook_attr';

    /** @var WebhookFactory */
    private $webhookFactory;

    /**
     * WebhookProcessor constructor.
     * @param WebhookFactory              $webhookFactory
     */
    public function __construct(WebhookFactory $webhookFactory)
    {
        $this->webhookFactory = $webhookFactory;
    }

    /**
     * @param Request                     $request
     * @param WebhookAuthenticatorFactory $authFactory
     * @return boolean
     */
    public function processRequest(Request $request, WebhookAuthenticatorFactory $authFactory)
    {
        $name = $request->get(self::REQUEST_WEBHOOK_NAME_KEY);
        try {
            $type = WebhookType::byValue($name);
        } catch (\Exception $e) {
            throw new InvalidWebhookException('Invalid webhook name passed');
        }
        $authenticator = $authFactory->getAuthenticator($type);
        if (!$authenticator->authenticate($request)) {
            throw new WebhookAuthenticationException('Authentication failed');
        }

        $webhook = $this->webhookFactory->getWebhook($type);
        $hookAttributes = $webhook->getHookAttributesForQueueing($request);
        return $this->saveToQueue($hookAttributes, $name);
    }

    /**
     * @param array $job
     * @return boolean
     * @throws WebhookException
     */
    public function processQueuedJob(array $job)
    {
        $name = $job[self::REQUEST_WEBHOOK_NAME_KEY];
        return $this
            ->webhookFactory
            ->getWebhook(WebhookType::byValue($name))
            ->runQueuedJob($job[static::HOOK_ATTRIBUTES_KEY]);
    }

    /**
     * @param array  $attributes
     * @param string $webhookName
     * @return boolean
     */
    protected function saveToQueue(array $attributes, $webhookName)
    {
        $details = [
            static::REQUEST_WEBHOOK_NAME_KEY => $webhookName,
            static::HOOK_ATTRIBUTES_KEY => $attributes
        ];
        return api_queue_add(static::QUEUE_NAME, $details);
    }
}
