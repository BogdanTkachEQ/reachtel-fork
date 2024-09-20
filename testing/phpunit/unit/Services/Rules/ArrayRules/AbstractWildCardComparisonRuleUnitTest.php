<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules;

use Services\Rules\ArrayRules\AbstractWildCardComparisonRule;

/**
 * Class AbstractWildCardComparisonRuleUnitTest
 */
abstract class AbstractWildCardComparisonRuleUnitTest extends AbstractComparisonRuleUnitTest
{
	/**
	 * @return array
	 */
	abstract public function isSatisfiedDataProvider();

	/**
	 * @return array
	 */
	public function doFieldWildCardComparisonDataProvider() {
		return [
			'set to true' => [true, true],
			'set to false' => [false, false]
		];
	}

	/**
	 * @dataProvider doFieldWildCardComparisonDataProvider
	 * @param boolean $expected
	 * @param boolean $doFieldWildCardComparison
	 * @return void
	 */
	public function testDoFieldWildCardComparison($expected, $doFieldWildCardComparison) {
		/** @var AbstractWildCardComparisonRule $rule */
		$rule = $this->getRule();
		$this->assertSameEquals($rule, $rule->doFieldWildCardComparison($doFieldWildCardComparison));
		$this->assertSameEquals($expected, $rule->shouldDoFieldWildCardComparison());
	}

	/**
	 * @return array
	 */
	public function doValueWildCardComparisonDataProvider() {
		return [
			'set to true' => [true, true],
			'set to false' => [false, false]
		];
	}

	/**
	 * @dataProvider doValueWildCardComparisonDataProvider
	 * @param boolean $expected
	 * @param boolean $doValueWildCardComparison
	 * @return void
	 */
	public function testDoValueWildCardComparison($expected, $doValueWildCardComparison) {
		$rule = $this->getRule();
		$this->assertSameEquals($rule, $rule->doValueWildCardComparison($doValueWildCardComparison));
		$this->assertSameEquals($expected, $rule->shouldDoValueWildCardComparison());
	}

	/**
	 * @return void
	 */
	public function testIsSatisfiedReturnsFalseWhenValueNotSet() {
		$rule = $this->getRule();
		$rule->setData(['test-data']);
		$this->assertFalse($rule->isSatisfied());
	}

	/**
	 * @expectedException Services\Exceptions\Rules\RulesException
	 * @expectedExceptionMessage Field to compare is not set
	 * @return void
	 */
	public function testIsSatisfiedThrowsExceptionWhenFieldIsNotSet() {
		/** @var AbstractWildCardComparisonRule $rule */
		$rule = $this->getRule();
		$rule
			->setData(['test-data'])
			->setValue('test');

		$rule->isSatisfied();
	}

	/**
	 * @dataProvider isSatisfiedDataProvider
	 * @param array   $data
	 * @param boolean $expected
	 * @param string  $field
	 * @param mixed   $value
	 * @param boolean $valueWildCardComparison
	 * @param boolean $fieldWildCardComparison
	 * @return void
	 */
	public function testIsSatisfied(array $data, $expected, $field, $value, $valueWildCardComparison, $fieldWildCardComparison) {
		/** @var AbstractWildCardComparisonRule $rule */
		$rule = $this->getRule();

		$rule
			->setField($field)
			->setValue($value)
			->setData($data)
			->doValueWildCardComparison($valueWildCardComparison)
			->doFieldWildCardComparison($fieldWildCardComparison);

		$this->assertSameEquals($expected, $rule->isSatisfied());
	}
}
