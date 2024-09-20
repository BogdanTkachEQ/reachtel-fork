<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\Interfaces\Command;

use Services\Autoload\Exceptions\AutoloadLineProcessorCommandException;

/**
 * interface AutoloadLineProcessorCommandInterface
 */
interface AutoloadLineProcessorCommandInterface
{
    /**
     * @param array $line
     * @return boolean
     * @throws AutoloadLineProcessorCommandException
     * @throws \Exception
     */
    public function execute(array $line);
}
