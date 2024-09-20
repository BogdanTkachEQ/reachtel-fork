<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Autoload;

/**
 * Class BrandBasedAutoloadDTO
 */
class BrandBasedAutoloadDTO extends AutoloadDTO
{
    /** @var string */
    private $brandColumnName;

    /**
     * @return string
     */
    public function getBrandColumnName()
    {
        return $this->brandColumnName;
    }

    /**
     * @param string $brandColumnName
     * @return $this
     */
    public function setBrandColumnName($brandColumnName)
    {
        $this->brandColumnName = $brandColumnName;
        return $this;
    }
}
