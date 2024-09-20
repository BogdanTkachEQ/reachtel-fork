<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\Interfaces;

use Services\Exceptions\Rules\RulesException;

/**
 * Interface RulesInterface
 */
interface RulesInterface
{
    /**
     * @return boolean
     * @throws RulesException
     */
    public function isSatisfied();
}
