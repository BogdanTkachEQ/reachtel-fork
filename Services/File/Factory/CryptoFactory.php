<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\File\Factory;

use Services\File\AbstractCryptography;
use Services\File\PgpCryptography;

/**
 * Class CryptoFactory
 */
class CryptoFactory
{
    const PGP = 'pgp';

    /**
     * @param $item
     * @return AbstractCryptography
     * @throws \RuntimeException
     */
    public static function create($item)
    {
        switch ($item) {
            case self::PGP:
                return new PgpCryptography();

            default:
                throw new \RuntimeException('Invalid item received');
        }
    }
}
