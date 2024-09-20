<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\File\CSV\Interfaces\CSV;
use Services\Reports\Interfaces\ArrayToFileConverterInterface;

/**
 * Class CsvArrayToFileConverter
 */
class CsvArrayToFileConverter implements ArrayToFileConverterInterface
{
    /** @var CSV */
    private $csvParser;

    /**
     * CsvArrayToFileConverter constructor.
     * @param CSV $parser
     */
    public function __construct(CSV $parser)
    {
        $this->csvParser = $parser;
    }

    /**
     * @param array  $data
     * @param string $filePath
     * @return boolean
     */
    public function convertArrayToFile(array $data, $filePath)
    {
        foreach ($data as &$item) {
            $item = array_values($item);
        }

        $csvString = $this
            ->csvParser
            ->setNewLineChar("\n")
            ->setDelimiter(',')
            ->unparse($data);

        if (!$csvString) {
            return false;
        }

        return (file_put_contents($filePath, $csvString) !== false);
    }
}
