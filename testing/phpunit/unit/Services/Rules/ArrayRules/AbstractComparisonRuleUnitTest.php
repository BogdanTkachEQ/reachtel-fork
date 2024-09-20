<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules;

use Services\Rules\ArrayRules\AbstractComparisonRule;

/**
 * Class AbstractComparisonRuleUnitTest
 */
abstract class AbstractComparisonRuleUnitTest extends AbstractArrayDataRuleUnitTest
{
	/**
	 * @return void
	 */
	public function testSetField() {
		/** @var AbstractComparisonRule $rule */
		$rule = $this->getRule();
		$field = 'test-field';

		$this->assertSameEquals($rule, $rule->setField($field));
		$this->assertSameEquals($field, $rule->getField());
	}

	/**
	 * @return void
	 */
	public function testSetValue() {
		/** @var AbstractComparisonRule $rule */
		$rule = $this->getRule();
		$value = 'test-value';

		$this->assertSameEquals($rule, $rule->setValue($value));
		$this->assertSameEquals($value, $rule->getValue());
	}
}
