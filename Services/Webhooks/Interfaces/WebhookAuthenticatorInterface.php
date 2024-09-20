<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Webhooks\Interfaces;

use Services\Exceptions\Webhooks\WebhookAuthenticationException;
use Services\Http\Request;

/**
 * Interface WebhookAuthenticatorInterface
 */
interface WebhookAuthenticatorInterface
{
    /**
     * @param Request $request
     * @return boolean
     * @throws WebhookAuthenticationException
     */
    public function authenticate(Request $request);
}
