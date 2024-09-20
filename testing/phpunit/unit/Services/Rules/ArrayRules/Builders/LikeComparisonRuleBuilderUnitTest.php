<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\Builders\AbstractComparisonRuleBuilder;
use Services\Rules\ArrayRules\Builders\LikeComparisonRuleBuilder;
use Services\Rules\ArrayRules\LikeComparisonRule;

/**
 * Class LikeComparisonRuleBuilderUnitTest
 */
class LikeComparisonRuleBuilderUnitTest extends AbstractWildCardComparisonRuleBuilderUnitTest
{
	/**
	 * @return AbstractComparisonRuleBuilder
	 */
	protected function getRuleBuilder() {
		return new LikeComparisonRuleBuilder();
	}

	/**
	 * @return string
	 */
	protected function getRuleClass() {
		return LikeComparisonRule::class;
	}
}
