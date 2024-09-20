<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Models\Reports\DataSeparatorType;
use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class DispositionColumnRowDataModifier
 */
class DispositionColumnRowDataModifier implements RowDataModifierInterface
{
    /** @var string */
    private $headerName = '';

    /** @var array */
    private $columns = [];

    /** @var DataSeparatorType */
    private $separator;

    /** @var array */
    private $rowData = [];

    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function setRowData(array $data)
    {
        $this->rowData = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModifiedData()
    {
        if (!$this->rowData) {
            return [];
        }

        $modifiedData = [];
        foreach ($this->getColumns() as $column) {
            if (isset($this->rowData[$column])) {
                $modifiedData[] = $this->rowData[$column];
            }
        }

        return implode($this->getSeparator()->getValue(), $modifiedData);
    }

    /**
     * @param string $headerName
     * @return $this
     */
    public function setHeaderName($headerName)
    {
        $this->headerName = $headerName;
        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @param DataSeparatorType $separator
     * @return $this
     */
    public function setSeparator(DataSeparatorType $separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * @return string
     */
    public function getHeaderName()
    {
        return $this->headerName;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return DataSeparatorType
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @return array
     */
    public function getRowData()
    {
        return $this->rowData;
    }
}
