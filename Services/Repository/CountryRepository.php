<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Repository;

use Doctrine\ORM\EntityRepository;
use Models\Entities\Country;

/**
 * Class CountryRepository
 */
class CountryRepository extends EntityRepository
{
    /**
     * @param $shortName
     * @return Country | null
     */
    public function findByShortName($shortName)
    {
        $country = $this->findBy(
            [
                'shortName' => $shortName
            ],
            null,
            1
        );

        if (!$country) {
            return null;
        }

        return $country[0];
    }
}
