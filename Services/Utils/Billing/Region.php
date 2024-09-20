<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Billing;

/**
 * Class Region
 */
class Region
{
    const REGION_AUSTRALIA = 1;
    const REGION_NEW_ZEALAND = 2;
    const REGION_SINGAPORE = 3;
    const REGION_GREAT_BRITAIN = 4;
    const REGION_PHILIPPINES = 5;
    const REGION_OTHER = 6;

    const REGION_AUSTRALIA_CODE = 'au';
    const REGION_NEW_ZEALAND_CODE = 'nz';
    const REGION_SINGAPORE_CODE = 'sg';
    const REGION_GREAT_BRITAIN_CODE = 'gb';
    const REGION_PHILIPPINES_CODE = 'ph';

    /**
     * @var array
     */
    private static $billingRegionIdCodeMap = [
        self::REGION_AUSTRALIA_CODE => self::REGION_AUSTRALIA,
        self::REGION_NEW_ZEALAND_CODE => self::REGION_NEW_ZEALAND,
        self::REGION_SINGAPORE_CODE => self::REGION_SINGAPORE,
        self::REGION_GREAT_BRITAIN_CODE => self::REGION_GREAT_BRITAIN,
        self::REGION_PHILIPPINES_CODE => self::REGION_PHILIPPINES
    ];

    /**
     * @param string $regionCode
     * @return integer
     */
    public static function getBillingRegionIdFromCode($regionCode)
    {
        if (isset(static::$billingRegionIdCodeMap[strtolower($regionCode)])) {
            return static::$billingRegionIdCodeMap[strtolower($regionCode)];
        }

        return self::REGION_OTHER;
    }
}
