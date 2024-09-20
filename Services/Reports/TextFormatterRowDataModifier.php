<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Reports\Interfaces\RowDataModifierInterface;

/**
 * Class TextFormatterRowDataModifier
 */
class TextFormatterRowDataModifier implements RowDataModifierInterface
{
    /** @var string */
    private $lineFeedReplace;

    /** @var integer */
    private $maxLength;

    /** @var boolean */
    private $addEllipsis;

    /** @var string */
    private $name;

    /** @var string */
    private $column;

    /** @var array */
    private $data = [];

    /**
     * TextFormatterRowDataModifier constructor.
     * @param string $name
     * @param string $column
     */
    public function __construct($name, $column)
    {
        $this->name = $name;
        $this->column = $column;
    }

    /**
     * @param array $data
     * @return TextFormatterRowDataModifier
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
        if (!isset($this->data[$this->column])) {
            return null;
        }

        $text = $this->data[$this->column];

        if (!is_null($this->getLineFeedReplace())) {
            $text = preg_replace('/\r|\n/', $this->getLineFeedReplace(), $text);
        }

        if (!$this->getMaxLength() || (strlen($text) <= $this->getMaxLength())) {
            return $text;
        }

        $text = substr($text, 0, $this->getMaxLength());

        if ($this->isAddEllipsis()) {
            $text .= '...';
        }

        return $text;
    }

    /**
     * @return string
     */
    public function getHeaderName()
    {
        return $this->name;
    }

    /**
     * @param $lineFeedReplace
     * @return $this
     */
    public function setLineFeedReplace($lineFeedReplace)
    {
        $this->lineFeedReplace = $lineFeedReplace;
        return $this;
    }

    /**
     * @param integer $maxLength
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    public function addEllipsis($addEllipsis = false)
    {
        $this->addEllipsis = $addEllipsis;
        return $this;
    }

    /**
     * @return string
     */
    public function getLineFeedReplace()
    {
        return $this->lineFeedReplace;
    }

    /**
     * @return integer
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @return boolean
     */
    public function isAddEllipsis()
    {
        return $this->addEllipsis;
    }
}
