<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules;

use Services\Rules\ArrayRules\AbstractArrayDataRule;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class AbstractArrayDataRuleUnitTest
 */
abstract class AbstractArrayDataRuleUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return AbstractArrayDataRule
	 */
	abstract protected function getRule();

	/**
	 * @return void
	 */
	public function testSetData() {
		$data = ['test-data'];
		$rule = $this->getRule();
		$this->assertSameEquals($rule, $rule->setData($data));
		$this->assertSameEquals($data, $rule->getData());
	}

	/**
	 * @expectedException Services\Exceptions\Rules\RulesException
	 * @expectedExceptionMessage Data not set
	 * @return void
	 */
	public function testIsSatisfiedThrowsExceptionWhenDataNotSet() {
		$rule = $this->getRule();
		$rule->isSatisfied();
	}
}
