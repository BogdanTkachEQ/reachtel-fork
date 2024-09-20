<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use Models\Interfaces\FixedWidthFieldSpecificationInterface;

/**
 * Class FixedWidthFileSpecification
 */
class FixedWidthFieldSpecification implements FixedWidthFieldSpecificationInterface
{
    /** @var integer */
    private $id;

    /** @var string */
    private $fieldName;

    /** @var integer */
    private $startPosition;

    /** @var integer */
    private $length;

    /** @var FixedWidthFile */
    private $fixedWidthFile;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @return integer
     */
    public function getStartPosition()
    {
        return $this->startPosition;
    }

    /**
     * @param integer $startPosition
     * @return $this
     */
    public function setStartPosition($startPosition)
    {
        $this->startPosition = $startPosition;
        return $this;
    }

    /**
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param integer $length
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @return FixedWidthFile
     */
    public function getFixedWidthFile()
    {
        return $this->fixedWidthFile;
    }

    /**
     * @param FixedWidthFile $fixedWidthFile
     * @return $this
     */
    public function setFixedWidthFile(FixedWidthFile $fixedWidthFile)
    {
        $this->fixedWidthFile = $fixedWidthFile;
        return $this;
    }
}
