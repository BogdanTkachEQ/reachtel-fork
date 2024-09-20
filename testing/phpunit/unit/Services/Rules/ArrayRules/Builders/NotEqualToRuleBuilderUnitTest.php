<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\Builders\AbstractComparisonRuleBuilder;
use Services\Rules\ArrayRules\Builders\NotEqualToRuleBuilder;
use Services\Rules\ArrayRules\NotEqualToRule;

/**
 * Class NotEqualToRuleBuilderUnitTest
 */
class NotEqualToRuleBuilderUnitTest extends AbstractComparisonRuleBuilderUnitTest
{
	/**
	 * @return AbstractComparisonRuleBuilder
	 */
	protected function getRuleBuilder() {
		return new NotEqualToRuleBuilder();
	}

	/**
	 * @return string
	 */
	protected function getRuleClass() {
		return NotEqualToRule::class;
	}
}
