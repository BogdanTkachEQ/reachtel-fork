<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\Builders\EqualToRuleBuilder;
use Services\Rules\ArrayRules\EqualToRule;

/**
 * Class EqualToRuleBuilderUnitTest
 */
class EqualToRuleBuilderUnitTest extends AbstractComparisonRuleBuilderUnitTest
{
	/**
	 * @return EqualToRuleBuilder
	 */
	protected function getRuleBuilder() {
		return new EqualToRuleBuilder();
	}

	/**
	 * @return string
	 */
	protected function getRuleClass() {
		return EqualToRule::class;
	}
}
