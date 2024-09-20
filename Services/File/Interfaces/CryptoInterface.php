<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\File\Interfaces;

/**
 * Interface CryptoInterface
 */
interface CryptoInterface
{
    /**
     * @param string $file
     * @return $this
     */
    public function setFile($file);

    /**
     * @param array $keys
     * @return $this
     */
    public function setKeys(array $keys = []);
}
