<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Doctrine\Connection;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Mysqli\MysqliException;
use Doctrine\DBAL\Driver\Mysqli\MysqliStatement;

/**
 * Class MysqliConnection
 */
class MysqliConnection implements Connection
{
    /**
     * Name of the option to set connection flags
     */
    const OPTION_FLAGS = 'flags';

    /**
     * @var \mysqli
     */
    private $connection;

    public function __construct(array $params, $username, $password, array $driverOptions = array())
    {
        global $DB_WRITE;

        if (!$DB_WRITE || !$DB_WRITE->IsConnected()) {
            api_db_write_connect();
        }

        $this->connection = $DB_WRITE->_connectionID;
    }

    /**
     * Retrieves mysqli native resource handle.
     *
     * Could be used if part of your application is not using DBAL.
     *
     * @return \mysqli
     */
    public function getWrappedResourceHandle()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerVersion()
    {
        $majorVersion = floor($this->connection->server_version / 10000);
        $minorVersion = floor(($this->connection->server_version - $majorVersion * 10000) / 100);
        $patchVersion = floor($this->connection->server_version - $majorVersion * 10000 - $minorVersion * 100);

        return $majorVersion . '.' . $minorVersion . '.' . $patchVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresQueryForServerVersion()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return new MysqliStatement($this->connection, $prepareString);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = \PDO::PARAM_STR)
    {
        return "'". $this->connection->escape_string($input) ."'";
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        if (false === $this->connection->query($statement)) {
            throw new MysqliException($this->connection->error, $this->connection->sqlstate, $this->connection->errno);
        }

        return $this->connection->affected_rows;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->connection->insert_id;
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->connection->query('START TRANSACTION');

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * {@inheritdoc}non-PHPdoc)
     */
    public function rollBack()
    {
        return $this->connection->rollback();
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->connection->errno;
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->connection->error;
    }

    /**
     * Pings the server and re-connects when `mysqli.reconnect = 1`
     *
     * @return bool
     */
    public function ping()
    {
        return $this->connection->ping();
    }
}
