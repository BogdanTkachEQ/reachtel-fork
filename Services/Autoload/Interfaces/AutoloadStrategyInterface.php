<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\Interfaces;

use Services\Autoload\AutoloadLogger;
use Services\Autoload\Exceptions\AutoloadStrategyException;

/**
 * Interface AutoloadStrategyInterface
 * @package Services\Autoload\Interfaces
 */
interface AutoloadStrategyInterface
{
    /**
     * @param $filePath
     * @return boolean
     * @throws AutoloadStrategyException
     */
    public function processFile($filePath);

    /**
     * @param AutoloadLogger $logger
     * @return void
     */
    public function setLogger(AutoloadLogger $logger);
}
