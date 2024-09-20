<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Authenticators;

use Google\Authenticator\GoogleAuthenticator;

/**
 * Class GoogleMultiFactorAuthenticator
 */
class GoogleMultiFactorAuthenticator
{
    /** @var GoogleAuthenticator */
    private $googleAuth;

    /** @var GoogleAuthUserService */
    private $googleAuthUserService;

    /**
     * GoogleMultiFactorAuthenticator constructor.
     * @param GoogleAuthenticator   $authenticator
     * @param GoogleAuthUserService $authUserService
     */
    public function __construct(GoogleAuthenticator $authenticator, GoogleAuthUserService $authUserService)
    {
        $this->googleAuth = $authenticator;
        $this->googleAuthUserService = $authUserService;
    }

    /**
     * @param integer $userId
     * @param integer $code
     * @return boolean
     */
    public function checkCode($userId, $code)
    {
        if (!$this->isGoogleAuthEnabledForUser($userId)) {
            return false;
        }
        $secret = $this->googleAuthUserService->getSecret($userId);
        return  $this->googleAuth->checkCode($secret, $code);
    }

    /**
     * @param integer $userId
     * @return string
     */
    public function createQR($userId)
    {
        if (!$this->isGoogleAuthEnabledForUser($userId)) {
            throw new \RuntimeException('Google auth is not enabled for the user');
        }

        $secret = $this->googleAuthUserService->getSecret($userId);

        $username = $this->googleAuthUserService->getUserName($userId);
        $hostName = defined('APP_HOST_NAME') ? APP_HOST_NAME : 'morpheus.reachtel.com.au';
        return $this->googleAuth->getUrl($username, $hostName, $secret);
    }

    /**
     * @param integer $userId
     * @return boolean
     */
    public function isGoogleAuthEnabledForUser($userId)
    {
        return $this->googleAuthUserService->getSecret($userId) !== null;
    }

    /**
     * @param integer $userId
     * @return boolean
     */
    public function disableGoogleAuthForUser($userId)
    {
        if (!$this->isGoogleAuthEnabledForUser($userId)) {
            return true;
        }

        return $this->googleAuthUserService->removeSecret($userId);
    }

    /**
     * @param integer $userId
     * @return boolean
     */
    public function enableGoogleAuthForUser($userId)
    {
        if ($this->isGoogleAuthEnabledForUser($userId)) {
            return true;
        }

        return (bool) $this->generateSecretForUser($userId);
    }

    /**
     * @param integer $userId
     * @return boolean
     */
    protected function generateSecretForUser($userId)
    {
        $secret = $this->googleAuth->generateSecret();
        return $this->googleAuthUserService->saveSecret($secret, $userId);
    }
}
