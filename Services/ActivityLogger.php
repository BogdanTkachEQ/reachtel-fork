<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services;

/**
 * Class ActivityLogger
 */
class ActivityLogger
{
    const TABLE_NAME = 'activity_logs';
    const BLACK_LIST_ITEM_CONFIG_NAME = 'blacklisted_items';

    const LOG_FLUSH_THRESHOLD_DEFAULT = 1000;
    const VALUE_MAX_SIZE = 1024;

    /** @var ActivityLogger */
    private static $instance;

    /** @var array */
    private $logs;

    /** @var string */
    private $sql;

    /** @var array */
    private $parameters;

    /** @var ConfigReader */
    private $configReader;

    private $isLoggerActive;

    /**
     * ActivityLogger constructor.
     * @param ConfigReader $configReader
     */
    private function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
        $this->isLoggerActive = true;
        $this->resetLogs();
        register_shutdown_function([$this, 'flush']);
    }

    /**
     * @param ConfigReader $configReader     Used for testing purpose.
     * @param boolean      $forceNewInstance Used for testing purpose.
     * @return ActivityLogger
     */
    public static function getInstance(ConfigReader $configReader = null, $forceNewInstance = false)
    {
        if ($forceNewInstance || !self::$instance) {
            if (!$configReader) {
                $configReader = ConfigReader::getInstance();
            }
            self::$instance = new self($configReader);
        }

        return static::$instance;
    }

    /**
     * @param boolean $active
     * @return void
     */
    public function toggleLoggerActivation($active = true)
    {
        $this->isLoggerActive = (bool)$active;
    }

    /**
     * @param string  $type
     * @param string  $action
     * @param string  $value
     * @param integer $objectId
     * @param integer $userId
     * @param string  $item
     * @return ActivityLogger
     */
    public function addLog($type, $action, $value, $objectId, $userId = null, $item = null)
    {
        if (!$this->isLoggerActive || ($item && !$this->isValidLogItem($type, $item))) {
            return $this;
        }

        if (!$userId) {
            if (isset($_SESSION[SESSION_KEY_USERID])) {
                $userId = $_SESSION[SESSION_KEY_USERID];
            } elseif (isset($GLOBALS[SESSION_KEY_USERID])) {
                $userId = $GLOBALS[SESSION_KEY_USERID];
            }
        }

        api_misc_audit($action, '[type: ' . $type . ', objectid :' . $objectId . '] ' . $value, $userId);
        $this->logs[] = [
            $type,
            $action,
            substr($value, 0, self::VALUE_MAX_SIZE),
            $objectId,
            $userId,
            $this->getInvocation(),
            $item,
        ];

        $this->checkThresholdAndFlushLogs();

        return $this;
    }

    /**
     * @return string | null
     */
    private function getInvocation()
    {
        if (php_sapi_name() === 'cli' && isset($_SERVER['SCRIPT_NAME'])) {
            return $_SERVER['SCRIPT_NAME'];
        }

        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
    }

    /**
     * @return boolean
     */
    public function flush()
    {
        if (!$this->logs) {
            return true;
        }

        $this->buildSql();
        $this->resetLogs();

        return api_db_query_write($this->sql, $this->parameters) !== false;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Resets logs array
     * @return ActivityLogger
     */
    public function resetLogs()
    {
        $this->logs = [];
        return $this;
    }

    /**
     * @return void
     */
    private function checkThresholdAndFlushLogs()
    {
        if (count($this->getLogs()) >= $this->getLogFlushThreshold()) {
            $this->flush();
        }
    }

    /**
     * @return integer
     */
    private function getLogFlushThreshold()
    {
        return defined('LOG_FLUSH_THRESHOLD') ? LOG_FLUSH_THRESHOLD : self::LOG_FLUSH_THRESHOLD_DEFAULT;
    }

    /**
     * Builds the insert statement
     * @return void
     */
    private function buildSql()
    {
        $sql = sprintf(
            'INSERT INTO %s (`type`, `action`, `value`, `objectid`, `userid`, `from`, `item`) VALUES ',
            self::TABLE_NAME
        );

        $parameters = [];
        $placeholders = [];

        foreach ($this->getLogs() as $data) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?)';
            $parameters = array_merge($parameters, $data);
        }

        $sql .= implode(',', $placeholders);
        $this->sql = $sql;
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return $this->configReader->getConfig(ConfigReader::ACTIVITY_LOGGER_CONFIG_TYPE);
    }

    /**
     * @param string $type
     * @param string $item
     * @return boolean
     */
    private function isValidLogItem($type, $item)
    {
        $config = $this->getConfig();

        if (!isset($config[self::BLACK_LIST_ITEM_CONFIG_NAME])) {
            return true;
        }

        $blackLists = $config[self::BLACK_LIST_ITEM_CONFIG_NAME];

        if (isset($blackLists[$type]) && in_array($item, $blackLists[$type])) {
            return false;
        }

        return true;
    }
}
