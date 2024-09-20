<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Doctrine\Common\Collections\Collection;
use Models\Interfaces\FixedWidthFieldSpecificationInterface;
use Services\Autoload\Exceptions\AutoloadFileProcessorException;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;

/**
 * Class FixedWidthFileProcessor
 */
class FixedWidthFileProcessor implements AutoloadFileProcessorInterface
{
    /** @var FixedWidthFieldSpecificationInterface[] */
    private $fixedWidthFileSpecs;

    /** @var string */
    private $lineSeparator = '\R';

    /**
     * FixedWidthFileProcessor constructor.
     * @param Collection FixedWidthFieldSpecificationInterface[] $fixedWidthFileSpecs
     */
    public function __construct(Collection $fixedWidthFileSpecs)
    {
        $this->fixedWidthFileSpecs = $fixedWidthFileSpecs;
        if (!$this->fixedWidthFileSpecs->count()) {
            throw new \InvalidArgumentException('Empty specification passed');
        }
    }

    /**
     * @param string $separator
     * @return $this
     */
    public function setLineSeparator($separator)
    {
        $this->lineSeparator = $separator;
        return $this;
    }

    /**
     * @param string $filePath
     * @return array
     * @throws AutoloadFileProcessorException
     */
    public function convertFileToArray($filePath)
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException('File path not found');
        }

        $data = [];
        foreach (preg_split("/" . $this->lineSeparator . "/", $content) as $row) {
            if (!trim($row)) {
                continue;
            }
            $data[] = $this->processRow($row);
        }

        return $data;
    }

    /**
     * @param string $row
     * @return array
     */
    protected function processRow($row)
    {
        $item = [];
        foreach ($this->fixedWidthFileSpecs as $spec) {
            $value = substr($row, $spec->getStartPosition() - 1, $spec->getLength());

            // If the length does not match, the row might not be matching specs so return empty array
            if (strlen($value) !== $spec->getLength()) {
                return [];
            }

            $item[$spec->getFieldName()] = trim($value);
        }

        return $item;
    }
}
