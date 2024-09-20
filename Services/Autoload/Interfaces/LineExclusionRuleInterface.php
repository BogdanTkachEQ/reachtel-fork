<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\Interfaces;

use Services\Autoload\Exceptions\LineExclusionException;

/**
 * interface LineExclusionRuleInterface
 */
interface LineExclusionRuleInterface
{
    /**
     * @param array $line
     * @return boolean
     * @throws LineExclusionException
     */
    public function shouldExclude(array $line);
}
