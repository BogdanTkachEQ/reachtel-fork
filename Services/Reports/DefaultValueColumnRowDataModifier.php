<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class DefaultValueColumnRowDataModifier
 */
class DefaultValueColumnRowDataModifier implements RowDataModifierInterface
{
    /** @var string */
    private $defaultValue;

    /** @var string */
    private $columnName;

    /** @var array */
    private $data = [];

    /**
     * @var string
     */
    private $valueColumn;

    /**
     * DefaultValueColumnRowDataModifier constructor.
     * @param string $columnName
     * @param string $value
     * @param string $valueColumn
     */
    public function __construct($columnName, $value, $valueColumn = null)
    {
        $this->columnName = $columnName;
        $this->defaultValue = $value;
        $this->valueColumn = $valueColumn;
    }

    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function setRowData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModifiedData()
    {
        if ($this->valueColumn && isset($this->data[$this->valueColumn]) && $this->data[$this->valueColumn]) {
            return $this->data[$this->valueColumn];
        }

        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getHeaderName()
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getValueColumn()
    {
        return $this->valueColumn;
    }
}
