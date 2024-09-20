<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\NotLikeComparisonRule;

/**
 * Class NotLikeComparisonRuleBuilder
 */
class NotLikeComparisonRuleBuilder extends AbstractWildCardComparisonRuleBuilder
{
    /**
     * @return NotLikeComparisonRule
     */
    protected function getRule()
    {
        return new NotLikeComparisonRule();
    }
}
