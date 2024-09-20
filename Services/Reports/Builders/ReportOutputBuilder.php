<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Builders;

use Services\Exceptions\Rules\RulesException;
use Services\File\Interfaces\EncryptorInterface;
use Services\Reports\ArrayRulesEngineDecorator;
use Services\Reports\Exceptions\NoDataGeneratedException;
use Services\Reports\Exceptions\ReportOutputBuilderException;
use Services\Reports\Interfaces\ArrayToFileConverterInterface;
use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class ReportOutputBuilder
 */
class ReportOutputBuilder
{
    /** @var boolean */
    private $hideHeader = false;

    /** @var array */
    private $data;

    /** @var array */
    private $outputColumns;

    /** @var ArrayToFileConverterInterface */
    private $arrayToFileConvertor;

    /** @var array */
    private $headerMap;

    /** @var ArrayRulesEngineDecorator */
    private $filterRulesEngine;

    /** @var null|EncryptorInterface */
    private $encryptor;

    /**
     * ReportOutputBuilder constructor.
     * @param ArrayToFileConverterInterface $arrayToFileConvertor
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ArrayToFileConverterInterface $arrayToFileConvertor,
        EncryptorInterface $encryptor = null
    ) {
        $this->arrayToFileConvertor = $arrayToFileConvertor;
        $this->encryptor = $encryptor;
    }

    /**
     * $data = [
     *     ['column1' => row1value1, 'column2' => row1value2],
     *     ['column1' => row2value1, 'column2' => row2value2]
     * ]
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param array $outputColumns
     * @return $this
     */
    public function setOutputColumns(array $outputColumns)
    {
        $this->outputColumns = $outputColumns;
        return $this;
    }

    /**
     * @param ArrayRulesEngineDecorator $rulesEngine
     * @return $this
     */
    public function setFilterRulesEngine(ArrayRulesEngineDecorator $rulesEngine)
    {
        $this->filterRulesEngine = $rulesEngine;
        return $this;
    }

    public function setHeaderMap(array $headerMap)
    {
        $this->headerMap = $headerMap;
        return $this;
    }

    /**
     * @param boolean $hideHeader
     * @return $this
     */
    public function hideHeader($hideHeader = false)
    {
        $this->hideHeader = $hideHeader;
        return $this;
    }

    /**
     * @return string $filePath
     * @throws ReportOutputBuilderException
     */
    public function build()
    {
        $this->runFilterOnData();
        $finalData = $this->outputColumns ? $this->createFinalDataFromOutputColumns() : $this->data;

        if (!$finalData) {
            throw new NoDataGeneratedException('No data generated');
        }

        if (!$this->hideHeader) {
            $firstRow = array_shift($finalData);
            $header = array_keys($firstRow);
            $header = $this->mapHeader($header);
            array_unshift($finalData, $firstRow);
            array_unshift($finalData, $header);
        }

        $tmpfname = tempnam(FILEPROCESS_TMP_LOCATION, "report");

        if ($this->arrayToFileConvertor->convertArrayToFile($finalData, $tmpfname)) {
            if ($this->encryptor) {
                $encrypted = $this->encryptor->setFile($tmpfname)->encrypt();
                file_put_contents($tmpfname, $encrypted);
            }
            return $tmpfname;
        }

        unlink($tmpfname);
        throw new ReportOutputBuilderException('An error occurred while converting to file');
    }

    /**
     * @return null
     */
    protected function runFilterOnData()
    {
        if (!$this->filterRulesEngine) {
            return null;
        }

        foreach ($this->data as $key => $item) {
            try {
                if (!$this->filterRulesEngine->setData($item)->runRules()) {
                    unset($this->data[$key]);
                }
            } catch (RulesException $exception) {
                // Leave this at the moment
                continue;
            }
        }
    }

    /**
     * @return array
     */
    protected function createFinalDataFromOutputColumns()
    {
        $finalData = [];
        foreach ($this->data as $data) {
            $modifiedData = [];
            foreach ($this->outputColumns as $column) {
                if ($column instanceof RowDataModifierInterface) {
                    $column->setRowData($data);
                    $modifiedData[$column->getHeaderName()] = $column->getModifiedData();
                    continue;
                }

                if (!array_key_exists($column, $data)) {
                    continue;
                }

                $modifiedData[$column] = $data[$column];
            }
            $finalData[] = $modifiedData;
        }

        return $finalData;
    }

    /**
     * @param array $header
     * @return array
     */
    protected function mapHeader(array $header)
    {
        if ($this->hideHeader || !$this->headerMap) {
            return $header;
        }

        foreach ($header as &$value) {
            if (isset($this->headerMap[$value])) {
                $value = $this->headerMap[$value];
            }
        }

        return $header;
    }
}
