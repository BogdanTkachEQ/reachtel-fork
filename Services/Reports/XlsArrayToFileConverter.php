<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Reports\Exceptions\FileConverterException;
use Services\Reports\Interfaces\ArrayToFileConverterInterface;
use Services\Utils\XlsFunctions;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class XlsArrayToFileConverter
 */
class XlsArrayToFileConverter implements ArrayToFileConverterInterface
{
    /**
     * @param array  $data
     * @param string $filePath
     * @return boolean
     * @throws FileConverterException
     */
    public function convertArrayToFile(array $data, $filePath)
    {
        try {
            XlsFunctions::arrayToXls($data, $filePath);
        } catch (Exception $exception) {
            throw new FileConverterException($exception->getMessage());
        }

        return true;
    }
}
