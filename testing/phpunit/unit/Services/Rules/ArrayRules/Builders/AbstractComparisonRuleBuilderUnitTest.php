<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\AbstractComparisonRule;
use Services\Rules\ArrayRules\Builders\AbstractComparisonRuleBuilder;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class AbstractComparisonRuleBuilderUnitTest
 */
abstract class AbstractComparisonRuleBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return AbstractComparisonRuleBuilder
	 */
	abstract protected function getRuleBuilder();

	/**
	 * @return string
	 */
	abstract protected function getRuleClass();

	/**
	 * @expectedException Services\Exceptions\Rules\RuleBuilderInvalidArgumentException
	 * @expectedExceptionMessage Data does not contain required field
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenFieldIsNotSet() {
		$builder = $this->getRuleBuilder();
		$builder->buildFromArray([]);
	}

	/**
	 * @expectedException Services\Exceptions\Rules\RuleBuilderInvalidArgumentException
	 * @expectedExceptionMessage Data does not contain required field
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenFieldIsSetButEmpty() {
		$builder = $this->getRuleBuilder();
		$builder->buildFromArray(['field' => '']);
	}

	/**
	 * @expectedException Services\Exceptions\Rules\RuleBuilderInvalidArgumentException
	 * @expectedExceptionMessage Data does not contain required field
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenValueIsNotSet() {
		$builder = $this->getRuleBuilder();
		$builder->buildFromArray(['field' => 'test']);
	}

	/**
	 * @return AbstractComparisonRule
	 */
	public function testBuildFromArray() {
		$data = $this->getTestDataForBuildFromArray();
		$builder = $this->getRuleBuilder();
		$rule = $builder->buildFromArray($data);
		$this->assertInstanceOf($this->getRuleClass(), $rule);
		$this->assertSameEquals($data['field'], $rule->getField());
		$this->assertSameEquals($data['value'], $rule->getValue());
		return $rule;
	}

	/**
	 * @return array
	 */
	protected function getTestDataForBuildFromArray() {
		return ['field' => 'test', 'value' => 'test-value'];
	}
}
