<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim\Interfaces;

use Services\Email\Dkim\DkimKeyFactory;

/**
 * Interface DkimKeyGeneratorInterface
 */
interface DkimKeyGeneratorInterface
{
    public function createKey(DkimKeyFactory $factory);

    public function getKeyType();
}
