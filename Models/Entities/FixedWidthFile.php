<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use Doctrine\Common\Collections\Collection;

/**
 * Class FixedWidthFile
 */
class FixedWidthFile
{
    /** @var integer */
    private $id;

    /** @var string */
    private $name;

    /** @var Collection FixedWidthFileSpecification[] */
    private $specifications;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection FixedWidthFileSpecification[]
     */
    public function getSpecifications()
    {
        return $this->specifications;
    }

    /**
     * @param Collection $specifications
     * @return $this
     */
    public function setSpecifications(Collection $specifications)
    {
        $this->specifications = $specifications;
        return $this;
    }
}
