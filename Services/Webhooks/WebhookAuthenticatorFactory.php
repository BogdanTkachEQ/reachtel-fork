<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Services\Authenticators\Oauth2AccessTokenAuthenticator;
use Services\Authenticators\SystemUserBasicHttpRequestAuthenticator;
use Services\Exceptions\Webhooks\WebhookException;
use Services\Utils\SecurityZone;
use Services\Webhooks\Interfaces\WebhookAuthenticatorInterface;
use Services\Utils\Webhooks\WebhookType;

/**
 * Class WebhookAuthenticatorFactory
 */
class WebhookAuthenticatorFactory
{
    /**
     * @param WebhookType $type
     * @return WebhookAuthenticatorInterface
     * @throws WebhookException
     */
    public function getAuthenticator(WebhookType $type)
    {
        switch ($type) {
            case WebhookType::YABBR_SMS_RECEIPT_HOOK():
                $authenticator = new SystemUserBasicHttpRequestAuthenticator(
                    YABBR_SMS_RECEIPT_USER,
                    YABBR_SMS_RECEIPT_PWD
                );
                return new YabbrSmsReceiptAuthenticator($authenticator);

            case WebhookType::SINCH_SMS_RECEIPT_HOOK():
                return new SinchWebhookAuthenticator(
                    new Oauth2AccessTokenAuthenticator(),
                    SecurityZone::SINCH_SMS_DR_SECURITY_ZONE()
                );

            case WebhookType::SINCH_INBOUND_SMS_HOOK():
                return new SinchWebhookAuthenticator(
                    new Oauth2AccessTokenAuthenticator(),
                    SecurityZone::SINCH_INBOUND_SMS_SECURITY_ZONE()
                );

            default:
                $message = 'Invalid webhook name received for authentication:' . $type->getValue();
                api_error_raise($message);
                throw new WebhookException($message);
        }
    }
}
