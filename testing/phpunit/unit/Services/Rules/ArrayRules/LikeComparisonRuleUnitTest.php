<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules;

use Services\Rules\ArrayRules\LikeComparisonRule;

/**
 * Class LikeComparisonRuleUnitTest
 */
class LikeComparisonRuleUnitTest extends AbstractWildCardComparisonRuleUnitTest
{
	/**
	 * @return LikeComparisonRule
	 */
	protected function getRule() {
		return new LikeComparisonRule();
	}

	/**
	 * @return array
	 */
	public function isSatisfiedDataProvider() {
		return [
			'when field and value wild card is set with matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				true,
				'a',
				'te',
				true,
				true
			],
			'when field and value wild card is set without matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				false,
				'a',
				'st',
				true,
				true
			],
			'when value wild card is set with matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				true,
				'a2',
				'te',
				true,
				false
			],
			'when value wild card is set without matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				false,
				'a2',
				'123',
				true,
				false
			],
			'when field wild card is set with matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				true,
				'a',
				'123',
				false,
				true
			],
			'when field wild card is set without matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				false,
				'a',
				'test123',
				false,
				true
			],
		];
	}
}
