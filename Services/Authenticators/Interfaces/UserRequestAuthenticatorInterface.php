<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Authenticators\Interfaces;

/**
 * Interface UserAuthenticatorInterface
 */
interface UserRequestAuthenticatorInterface extends RequestAuthenticatorInterface
{
    /**
     * Returns Morpheus user id on succesful authentication
     * @return integer
     */
    public function getUserId();
}
