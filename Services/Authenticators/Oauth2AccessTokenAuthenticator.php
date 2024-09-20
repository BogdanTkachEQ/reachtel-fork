<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Authenticators;

use Services\Authenticators\Interfaces\UserRequestAuthenticatorInterface;
use Services\Exceptions\Authenticators\AuthenticatorException;
use Services\Http\Request;

/**
 * Class Oauth2AccessTokenAuthenticator
 */
class Oauth2AccessTokenAuthenticator implements UserRequestAuthenticatorInterface
{
    /** @var integer */
    private $userId;

    /**
     * @param Request $request
     * @return boolean
     */
    public function authenticate(Request $request)
    {
        $accessToken = $this->getAccessToken($request);

        if (!is_null($accessToken)) {
            return false;
        }

        $userId = api_session_token_check($accessToken);

        if (!$userId) {
            return false;
        }

        $this->userId = $userId;

        return true;
    }

    /**
     * Returns Morpheus user id on succesful authentication
     * @return integer
     */
    public function getUserId()
    {
        if (!$this->userId) {
            throw new AuthenticatorException('User not authenticated');
        }

        return $this->userId;
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getAccessToken(Request $request)
    {
        if (!$request->headers->has('Authorization')) {
            return null;
        }

        $authHeader = $request->headers->get('Authorization');

        if (preg_match("/^Bearer (.+)$/", $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
