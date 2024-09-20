<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\AbstractComparisonRule;

/**
 * Class AbstractWildCardComparisonRuleBuilderUnitTest
 */
abstract class AbstractWildCardComparisonRuleBuilderUnitTest extends AbstractComparisonRuleBuilderUnitTest
{
	/**
	 * @return AbstractComparisonRule
	 */
	public function testBuildFromArray() {
		$data = $this->getTestDataForBuildFromArray();
		$rule = parent::testBuildFromArray();
		$this->assertSameEquals($data['fieldwildcard'], $rule->shouldDoFieldWildCardComparison());
		$this->assertSameEquals($data['valuewildcard'], $rule->shouldDoValueWildCardComparison());
		return $rule;
	}

	/**
	 * @return array
	 */
	protected function getTestDataForBuildFromArray() {
		return ['field' => 'test', 'value' => 'test-value', 'fieldwildcard' => true, 'valuewildcard' => false];
	}
}
