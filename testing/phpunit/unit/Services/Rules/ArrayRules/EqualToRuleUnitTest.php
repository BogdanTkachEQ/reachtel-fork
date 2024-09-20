<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules;

use Services\Rules\ArrayRules\EqualToRule;

/**
 * Class EqualToRuleUnitTest
 */
class EqualToRuleUnitTest extends AbstractComparisonRuleUnitTest
{
	/**
	 * @return EqualToRule
	 */
	protected function getRule() {
		return new EqualToRule();
	}

	/**
	 * @expectedException Services\Exceptions\Rules\RulesException
	 * @expectedExceptionMessage Field to compare is not set
	 * @return void
	 */
	public function testIsSatisfiedThrowsExceptionWhenFieldIsNotSet() {
		$rule = $this->getRule();
		$rule->setData(['test-data']);
		$rule->isSatisfied();
	}

	/**
	 * @return void
	 */
	public function testIsSatisfied() {
		$rule = $this->getRule();
		$data = ['a' => 123, 'b' => 'asdf'];
		$rule->setField('a');
		$rule->setValue('123');
		$rule->setData($data);
		$this->assertTrue($rule->isSatisfied());

		$rule->setField('b');
		$rule->setValue('test');

		$this->assertFalse($rule->isSatisfied());

		$rule->setField('c');
		$rule->setValue('');
		$this->assertTrue($rule->isSatisfied());
	}
}
