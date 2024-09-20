<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

/**
 * Class AutoloadLogger
 * @package Services\Autoload
 */
class AutoloadLogger
{
    /**
     * @var array
     */
    private $logs = [];

    /**
     * @param string $log
     * @return AutoloadLogger
     */
    public function addLog($log)
    {
        $this->logs[] = $log;
        return $this;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @return string
     */
    public function flush()
    {
        return implode("\n", $this->getLogs());
    }
}
