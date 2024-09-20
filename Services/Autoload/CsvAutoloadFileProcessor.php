<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Exceptions\AutoloadFileProcessorException;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\File\CSV\Interfaces\CSV;

/**
 * Class CsvAutoloadFileProcessor
 */
class CsvAutoloadFileProcessor implements AutoloadFileProcessorInterface
{
    /** @var string */
    private $headerString;
    /**
     * @var CSV
     */
    private $csv;

    public function __construct(CSV $csv)
    {
        $this->csv = $csv;
    }

    /**
     * @param string $filePath
     * @return array
     * @throws AutoloadFileProcessorException
     */
    public function convertFileToArray($filePath)
    {
        if ($this->headerString) {
            $contents = file_get_contents($filePath);
            $contents = $this->headerString . "\n" . $contents;
            file_put_contents($filePath, $contents);
        }
        if (!$this->csv->parseFile($filePath)) {
            return false;
        }
        return $this->csv->getRowData();
    }

    /**
     * @param string $headerString
     * @return $this
     */
    public function setHeaderString($headerString)
    {
        $this->headerString = $headerString;
        return $this;
    }
}
