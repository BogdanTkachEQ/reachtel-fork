<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\User;

/**
 * Class UserTypeEnum
 * @package Services\User
 */
class UserTypeEnum extends \MabeEnum\Enum
{
    const CLIENT = "client";
    const API = "api";
    const SYSTEM = "system";
    const ADMIN = "admin";

    public static function getDefault()
    {
        return self::CLIENT();
    }
}
