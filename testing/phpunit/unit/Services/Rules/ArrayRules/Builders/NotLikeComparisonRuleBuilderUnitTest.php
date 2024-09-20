<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\Builders\AbstractComparisonRuleBuilder;
use Services\Rules\ArrayRules\Builders\NotLikeComparisonRuleBuilder;
use Services\Rules\ArrayRules\NotLikeComparisonRule;

/**
 * Class NotLikeComparisonRuleBuilderUnitTest
 */
class NotLikeComparisonRuleBuilderUnitTest extends AbstractWildCardComparisonRuleBuilderUnitTest
{
	/**
	 * @return AbstractComparisonRuleBuilder
	 */
	protected function getRuleBuilder() {
		return new NotLikeComparisonRuleBuilder();
	}

	/**
	 * @return string
	 */
	protected function getRuleClass() {
		return NotLikeComparisonRule::class;
	}
}
