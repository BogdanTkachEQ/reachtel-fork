<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\Interfaces;

use Services\Rules\ArrayRules\AbstractArrayDataRule;

/**
 * Interface ArrayDataRuleBuilderInterface
 */
interface ArrayDataRuleBuilderInterface extends RuleBuilderInterface
{
    /**
     * @param array $data
     * @return AbstractArrayDataRule
     */
    public function buildFromArray(array $data);
}
