<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Exceptions\AutoloadStrategyException;
use Services\Autoload\Interfaces\AutoloadStrategyInterface;

/**
 * Class AbstractAutoloadStrategy
 */
abstract class AbstractAutoloadStrategy implements AutoloadStrategyInterface
{
    /**
     * @var AutoloadLogger
     */
    protected $logger;

    /**
     * @return boolean
     */
    abstract protected function preProcessHook();

    /**
     * @return boolean
     */
    abstract protected function postProcessHook();

    /**
     * @param string $filePath
     * @return boolean
     * @throws AutoloadStrategyException
     */
    abstract protected function process($filePath);

    /**
     * @param string $filePath
     * @return boolean
     * @throws AutoloadStrategyException
     */
    public function processFile($filePath)
    {
        if (!$this->preProcessHook()) {
            return false;
        }

        if (!$this->process($filePath)) {
            return false;
        }

        return $this->postProcessHook();
    }

    /**
     * @param AutoloadLogger $logger
     */
    public function setLogger(AutoloadLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return AutoloadLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string $log
     * @return bool
     */
    protected function addToLogs($log)
    {
        if (is_null($this->logger)) {
            return false;
        }

        $this->logger->addLog($log);
        return true;
    }
}
