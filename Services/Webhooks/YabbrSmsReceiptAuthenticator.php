<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks;

use Services\Exceptions\Authenticators\AuthenticatorException;
use Services\Authenticators\SystemUserBasicHttpRequestAuthenticator;
use Services\Exceptions\Webhooks\WebhookAuthenticationException;
use Services\Http\Request;
use Services\Webhooks\Interfaces\WebhookAuthenticatorInterface;

/**
 * Class YabbrSmsReceiptAuthenticator
 */
class YabbrSmsReceiptAuthenticator implements WebhookAuthenticatorInterface
{
    /** @var SystemUserBasicHttpRequestAuthenticator */
    private $authenticator;

    public function __construct(SystemUserBasicHttpRequestAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return boolean
     * @throws WebhookAuthenticationException
     */
    public function authenticate(Request $request)
    {
        try {
            return $this
                ->authenticator
                ->authenticate($request);
        } catch (AuthenticatorException $e) {
            throw new WebhookAuthenticationException($e->getMessage());
        }
    }
}
