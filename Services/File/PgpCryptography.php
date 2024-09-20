<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\File;

use Services\Exceptions\File\CryptoException;
use Services\File\Interfaces\DecryptorInterface;
use Services\File\Interfaces\EncryptorInterface;

/**
 * Class PgpCryptography
 */
class PgpCryptography extends AbstractCryptography implements DecryptorInterface, EncryptorInterface
{
    /**
     * @return string
     */
    public function decrypt()
    {
        if (is_null($this->file)) {
            throw new CryptoException('File to decrypt not set');
        }

        $content = file_get_contents($this->file);
        $decrypted = api_misc_pgp_decrypt($content);

        if ($decrypted === false) {
            throw new CryptoException('Error happened when decrypting file');
        }

        return $decrypted;
    }

    /**
     * @return string
     * @throws CryptoException
     */
    public function encrypt()
    {
        if (is_null($this->file)) {
            throw new CryptoException('File to encrypt not set');
        }

        if (!$this->keys) {
            throw new CryptoException('Key to encrypt needs to be set');
        }

        $content = file_get_contents($this->file);
        $encrypted = api_misc_pgp_encrypt(['content' => $content, 'filename' => ''], implode(',', $this->keys));

        if ($encrypted === false) {
            throw new CryptoException('Error happened when encrypting file');
        }

        return $encrypted['content'];
    }
}
