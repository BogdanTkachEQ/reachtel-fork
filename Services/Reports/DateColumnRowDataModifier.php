<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class DateColumnRowDataModifier
 */
class DateColumnRowDataModifier implements RowDataModifierInterface
{
    /** @var array */
    private $data;

    /** @var string */
    private $name;

    /** @var string */
    private $column;

    /** @var string */
    private $format;

    /**
     * DateColumnRowDataModifier constructor.
     * @param string $name
     * @param string $column
     * @param string $format
     */
    public function __construct($name, $column, $format)
    {
        $this->name = $name;
        $this->column = $column;
        $this->format = $format;
    }

    /**
     * @param array $data
     * @return $this
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
        if (!isset($this->data[$this->column]) || !$this->data[$this->column]) {
            return null;
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->data[$this->column]);
        if (!$dateTime) {
            return $this->data[$this->column];
        }

        return $dateTime->format($this->format);
    }

    /**
     * @return string
     */
    public function getHeaderName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
}
