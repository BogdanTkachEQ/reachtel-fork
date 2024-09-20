<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Autoload;

use Services\Autoload\AutoloadLogger;
use Services\Autoload\Exceptions\AutoloadStrategyException;
use Services\Autoload\Interfaces\AutoloadStrategyInterface;

/**
 * Class CompositeAutoloadStrategy
 */
class CompositeAutoloadStrategy implements AutoloadStrategyInterface
{
    /**
     * @var AutoloadStrategyInterface[]
     */
    private $strategies = [];

    /**
     * @param AutoloadStrategyInterface $strategy
     * @return CompositeAutoloadStrategy
     */
    public function add(AutoloadStrategyInterface $strategy)
    {
        $this->strategies[] = $strategy;
        return $this;
    }

    public function setLogger(AutoloadLogger $logger)
    {
        foreach ($this->strategies as $strategy) {
            $strategy->setLogger($logger);
        }
    }

    /**
     * @param $filePath
     * @return boolean
     * @throws AutoloadStrategyException
     */
    public function processFile($filePath)
    {
        foreach ($this->strategies as $strategy) {
            $strategy->processFile($filePath);
        }

        return true;
    }
}
