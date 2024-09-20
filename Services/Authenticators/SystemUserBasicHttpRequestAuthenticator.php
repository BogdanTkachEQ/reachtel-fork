<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Authenticators;

use Services\Authenticators\Interfaces\RequestAuthenticatorInterface;
use Services\Exceptions\Authenticators\AuthenticatorException;
use Services\Http\Request;

/**
 * Class BasicHttpAuthenticator
 */
class SystemUserBasicHttpRequestAuthenticator implements RequestAuthenticatorInterface
{
    /** @var String */
    protected $username;

    /** @var String */
    protected $password;

    /**
     * BasicHttpRequestAuthenticator constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param Request $request
     * @throws AuthenticatorException
     * @return boolean
     */
    public function authenticate(Request $request)
    {
        $user = $request->getUser();
        $password = $request->getPassword();

        if (!$user || !$password) {
            throw new AuthenticatorException('Required credentials not found');
        }

        if ($user === $this->username && $password === $this->password) {
            return true;
        }

        return false;
    }
}
