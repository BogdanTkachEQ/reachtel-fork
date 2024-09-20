<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\EqualToRule;

/**
 * Class EqualToRuleBuilder
 */
class EqualToRuleBuilder extends AbstractComparisonRuleBuilder
{
    /**
     * @return EqualToRule
     */
    protected function getRule()
    {
        return new EqualToRule();
    }
}
