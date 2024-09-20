<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\LikeComparisonRule;

/**
 * Class LikeComparisonRuleBuilder
 */
class LikeComparisonRuleBuilder extends AbstractWildCardComparisonRuleBuilder
{
    /**
     * @return LikeComparisonRule
     */
    protected function getRule()
    {
        return new LikeComparisonRule();
    }
}
