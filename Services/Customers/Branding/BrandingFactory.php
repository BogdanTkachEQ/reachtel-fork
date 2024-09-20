<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Branding;

use Services\Customers\Toyota\Branding\ToyotaBrandingEnum;

/**
 * Class BrandingFactory
 * @package Services\Customers\Branding
 */
class BrandingFactory
{
    /**
     * @param $brand
     * @return boolean|ToyotaBrandingEnum
     */
    public function build($brand)
    {
        if (ToyotaBrandingEnum::hasName($brand)) {
            return ToyotaBrandingEnum::byName($brand);
        } elseif ($brand = ToyotaBrandingEnum::search($brand)) {
            return $brand;
        }
        return false;
    }
}
