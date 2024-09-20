<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use InvalidArgumentException;
use Models\Email\Dkim\DkimKey;
use Services\Exceptions\Email\DkimException;

/**
 * Class DkimKeyFactory
 */
class DkimKeyFactory
{

    /**
     * @param resource $pkeyResource
     * @return DkimKey
     * @throws DkimException
     */
    public function createKeyFromResource($pkeyResource)
    {
        if (!is_resource($pkeyResource)) {
            throw new DkimException("Invalid resource supplied to DKIM factory");
        }
        openssl_pkey_export(openssl_pkey_get_private($pkeyResource), $strKey);
        try {
            return new DkimKey($strKey);
        } catch (InvalidArgumentException $e) {
            throw new DkimException($e->getMessage());
        }
    }

    /**
     * @param string $privateKey
     * @return DkimKey
     * @throws DkimException
     */
    public function createKey($privateKey)
    {
        try {
            return new DkimKey($privateKey);
        } catch (InvalidArgumentException $e) {
            throw new DkimException($e->getMessage());
        }
    }
}
