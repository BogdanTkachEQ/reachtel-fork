<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Billing;

/**
 * Class Channels
 */
class Channels
{
    const WEB_NAME = 'WEB';
    const API_NAME = 'API';
    const COLUMN_NAME_CODE = 'code';
    const COLUMN_NAME_ID = 'id';
    const COLUMN_NAME_NAME = 'name';

    /**
     * @var array
     */
    private static $channelMap;

    /**
     * @return array
     * @throws \Exception
     */
    public static function getChannelMap()
    {
        if (is_null(static::$channelMap)) {
            $sql = sprintf(
                'SELECT `%s`, `%s`, `%s` FROM `billing_channels`',
                self::COLUMN_NAME_NAME,
                self::COLUMN_NAME_ID,
                self::COLUMN_NAME_CODE
            );

            $rs = api_db_query_read($sql);

            if (!$rs || !$rs->RecordCount()) {
                $message = 'Unable to retrieve billing channels';
                api_error_raise($message);
                throw new \Exception($message);
            }

            static::$channelMap = $rs->GetAssoc();
        }

        return static::$channelMap;
    }

    /**
     * @param string $name
     * @return integer
     * @throws \Exception
     */
    public static function getChannelIdByName($name)
    {
        if (!isset(static::getChannelMap()[$name])) {
            throw new \Exception('Invalid channel name passed ' . $name);
        }

        return (int)static::getChannelMap()[$name][self::COLUMN_NAME_ID];
    }
}
