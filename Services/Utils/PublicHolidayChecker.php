<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

use Models\Entities\Country;
use Models\Entities\Region;

/**
 * Class PublicHolidayChecker
 */
class PublicHolidayChecker
{
    /**
     * @param \DateTime $dateTime
     * @param Region    $region
     * @return boolean
     */
    public function isPublicHoliday(\DateTime $dateTime, Region $region = null)
    {
        return $this->isPublicHolidayByCountry($dateTime, $region ? $region->getCountry() : null);
    }

    /**
     * @param \DateTime $dateTime
     * @param Country   $country
     * @return boolean
     */
    public function isPublicHolidayByCountry(\DateTime $dateTime, Country $country = null)
    {
        return $this
            ->isPublicHolidayByCountryShortCode($dateTime, $country ? $country->getShortName() : null);
    }

    /**
     * @param \DateTime $dateTime
     * @param string    $countryShortCode
     * @return boolean
     */
    public function isPublicHolidayByCountryShortCode(\DateTime $dateTime, $countryShortCode = null)
    {
        return api_misc_ispublicholiday($countryShortCode, $dateTime->getTimestamp());
    }
}
