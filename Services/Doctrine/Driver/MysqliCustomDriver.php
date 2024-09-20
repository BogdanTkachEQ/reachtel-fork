<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Doctrine\Driver;

use Doctrine\DBAL\Driver\Mysqli\Driver;
use Services\Doctrine\Connection\MysqliConnection;

/**
 * Class MysqliCustomDriver
 */
class MysqliCustomDriver extends Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new MysqliConnection($params, $username, $password);
    }
}
