<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\File;

use Services\File\Interfaces\CryptoInterface;

/**
 * Class CryptoService
 */
abstract class AbstractCryptography implements CryptoInterface
{
    /** @var string */
    protected $file;

    /** @var array */
    protected $keys = [];

    /**
     * @param string $file
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param array $keys
     * @return $this
     */
    public function setKeys(array $keys = [])
    {
        $this->keys = $keys;
        return $this;
    }
}
