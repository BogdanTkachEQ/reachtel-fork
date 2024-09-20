<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use Doctrine\Common\Collections\Collection;

/**
 * Class Country
 */
class Country
{
    /** @var integer */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $shortName;

    /** @var Collection Region[] */
    private $regions;

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
     * @return Collection Region[]
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * @param Collection $regions
     * @return $this
     */
    public function setRegions(Collection $regions)
    {
        $this->regions = $regions;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @param string $shortName
     * @return $this
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
        return $this;
    }
}
