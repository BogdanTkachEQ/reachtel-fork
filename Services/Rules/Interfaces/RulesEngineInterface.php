<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\Interfaces;

use Services\Exceptions\Rules\RulesException;

/**
 * interface RulesEngineInterface
 */
interface RulesEngineInterface
{
    /**
     * @return boolean
     * @throws RulesException
     */
    public function runRules();
}
