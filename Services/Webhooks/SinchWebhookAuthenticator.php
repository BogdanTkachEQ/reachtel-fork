<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Services\Authenticators\Oauth2AccessTokenAuthenticator;
use Services\Http\Request;
use Services\Utils\SecurityZone;
use Services\Webhooks\Interfaces\WebhookAuthenticatorInterface;

/**
 * Class SinchWebhookAuthenticator
 */
class SinchWebhookAuthenticator implements WebhookAuthenticatorInterface
{
    /** @var Oauth2AccessTokenAuthenticator */
    private $authenticator;

    /** @var SecurityZone */
    private $securityZone;

    public function __construct(Oauth2AccessTokenAuthenticator $authenticator, SecurityZone $securityZone)
    {
        $this->authenticator = $authenticator;
        $this->securityZone = $securityZone;
    }

    /**
     * @param Request $request
     * @return boolean
     */
    public function authenticate(Request $request)
    {
        if (!$this->authenticator->authenticate($request)) {
            return false;
        }

        $userId = $this->authenticator->getUserId();
        return api_security_check($this->securityZone->getValue(), null, true, $userId);
    }

    /**
     * @return SecurityZone
     */
    public function getSecurityZone()
    {
        return $this->securityZone;
    }
}
