<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Interfaces;

use Services\Reports\Exceptions\FileConverterException;

/**
 * interface ArrayToFileConverterInterface
 */
interface ArrayToFileConverterInterface
{
    /**
     * @param array  $data
     * @param string $filePath
     * @return boolean
     * @throws FileConverterException
     */
    public function convertArrayToFile(array $data, $filePath);
}
