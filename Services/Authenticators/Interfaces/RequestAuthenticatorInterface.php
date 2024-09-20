<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Authenticators\Interfaces;

use Services\Exceptions\Authenticators\AuthenticatorException;
use Services\Http\Request;

/**
 * Interface RequestAuthenticatorInterface
 */
interface RequestAuthenticatorInterface
{
    /**
     * @param Request $request
     * @throws AuthenticatorException
     * @return boolean
     */
    public function authenticate(Request $request);
}
