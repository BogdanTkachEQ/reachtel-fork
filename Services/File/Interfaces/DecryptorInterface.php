<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\File\Interfaces;

use Services\Exceptions\File\CryptoException;

/**
 * Interface EncryptorInterface
 */
interface DecryptorInterface extends CryptoInterface
{
    /**
     * @return string
     * @throws CryptoException
     */
    public function decrypt();
}
