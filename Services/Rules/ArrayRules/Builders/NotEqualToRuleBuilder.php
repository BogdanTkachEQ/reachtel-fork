<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\NotEqualToRule;

/**
 * Class NotEqualToRuleBuilder
 */
class NotEqualToRuleBuilder extends AbstractComparisonRuleBuilder
{
    /**
     * @return NotEqualToRule
     */
    protected function getRule()
    {
        return new NotEqualToRule();
    }
}
