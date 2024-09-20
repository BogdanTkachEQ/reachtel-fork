<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Authenticators;

/**
 * Class GoogleAuthUserService
 */
class GoogleAuthUserService
{
    const GOOGLE_AUTH_USER_SECRET_SETTING_KEY = 'googleauthsecret';

    /**
     * @param $userId
     * @return string | null
     */
    public function getSecret($userId)
    {
        return api_users_setting_getsingle($userId, self::GOOGLE_AUTH_USER_SECRET_SETTING_KEY) ? : null;
    }

    /**
     * @param integer $userId
     * @return boolean
     */
    public function removeSecret($userId)
    {
        return api_users_setting_delete_single($userId, self::GOOGLE_AUTH_USER_SECRET_SETTING_KEY);
    }

    /**
     * @param string  $secret
     * @param integer $userId
     * @return boolean
     */
    public function saveSecret($secret, $userId)
    {
        return api_users_setting_set($userId, self::GOOGLE_AUTH_USER_SECRET_SETTING_KEY, $secret);
    }

    /**
     * @param integer $userId
     * @return string
     */
    public function getUserName($userId)
    {
        return api_users_setting_getsingle($userId, USER_SETTING_USERNAME);
    }
}
