<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Exceptions\AutoloadFileProcessorException;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\Utils\XlsFunctions;

/**
 * Class XlsAutoloadFileProcessor
 */
class XlsAutoloadFileProcessor implements AutoloadFileProcessorInterface
{
    /**
     * @param string $filePath
     * @return array
     * @throws AutoloadFileProcessorException
     */
    public function convertFileToArray($filePath)
    {
        try {
            return XlsFunctions::xlsToArray($filePath);
        } catch (\Exception $e) {
            throw new AutoloadFileProcessorException($e->getMessage());
        }
    }
}
