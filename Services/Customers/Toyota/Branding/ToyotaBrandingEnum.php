<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Branding;

use MabeEnum\Enum;
use Services\Customers\Branding\BrandingInterface;

/**
 * Contains Toyota brands and their desired display names
 * see https://packagist.org/packages/marc-mabe/php-enum?query=enum#3.x-dev
 * regarding enums
 *
 * Class ToyotaBrandingEnum
 * @package Services\Customers\Toyota\Branding
 */
class ToyotaBrandingEnum extends Enum implements BrandingInterface
{
    const TOYOTA_FINANCE = "Toyota";
    const LEXUS_FINANCIAL_SERVICES = "Lexus";
    const MAZDA_FINANCE = "Mazda";
    const HINO_FINANCIAL_SERVICES = "Hino";
    const POWERTORQUE_FINANCE = "PowerTorque";
    const POWER_ALLIANCE_FINANCE = "Power Alliance";
    const SUZUKI_FINANCIAL_SERVICES = "Suzuki";

    /**
     * Performs a case insensitive search for the given sub brand
     * E.g Toyota Finance = ToyotaBrandingEnum::TOYOTA_FINANCE
     * toyota finance = ToyotaBrandingEnum::TOYOTA_FINANCE
     * toyota_finance = ToyotaBrandingEnum::TOYOTA_FINANCE
     *
     * @param $search
     * @return bool|ToyotaBrandingEnum
     */
    public static function search($search)
    {
        foreach (self::getNames() as $name) {
            if (strtolower(str_replace("_", " ", $name)) === strtolower(str_replace("_", " ", $search))) {
                return self::byName($name);
            }
        }
        return false;
    }

    /**
     * @return array|bool|float|int|string|null
     */
    public function getBrandName()
    {
        return $this->getValue();
    }
}
