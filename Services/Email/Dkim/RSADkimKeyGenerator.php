<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Services\Email\Dkim\Interfaces\DkimKeyGeneratorInterface;

/**
 * Class RSADkimKeyGenerator
 */
class RSADkimKeyGenerator implements DkimKeyGeneratorInterface
{

    const KEYSIZE = 2048;
    const KEYTYPE = OPENSSL_KEYTYPE_RSA;
    const DIGEST_ALG = "sha256";

    /**
     * @param DkimKeyFactory $factory
     * @return \Models\Email\Dkim\DkimKey
     * @throws \Services\Exceptions\Email\DkimException
     */
    public function createKey(DkimKeyFactory $factory)
    {
        $key = openssl_pkey_new(
            [
                'private_key_bits' => self::KEYSIZE,
                'private_key_type' => self::KEYTYPE,
                'digest_alg' => self::DIGEST_ALG
            ]
        );

        return $factory->createKeyFromResource($key);
    }

    /**
     * @return int
     */
    public function getKeyType()
    {
        return self::KEYTYPE;
    }
}
