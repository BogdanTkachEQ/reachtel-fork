<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\Interfaces;

use Services\Autoload\Exceptions\AutoloadFileProcessorException;

/**
 * Interface AutoloadFileProcessorInterface
 */
interface AutoloadFileProcessorInterface
{
    /**
     * @param string $filePath
     * @return array
     * @throws AutoloadFileProcessorException
     */
    public function convertFileToArray($filePath);
}
