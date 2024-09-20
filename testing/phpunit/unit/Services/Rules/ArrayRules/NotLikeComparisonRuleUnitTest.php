<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules\ArrayRules;

use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\ArrayRules\NotLikeComparisonRule;

/**
 * Class NotLikeComparisonRuleUnitTest
 */
class NotLikeComparisonRuleUnitTest extends AbstractWildCardComparisonRuleUnitTest
{
	/**
	 * @return AbstractArrayDataRule
	 */
	protected function getRule() {
		return new NotLikeComparisonRule();
	}

	/**
	 * @return array
	 */
	public function isSatisfiedDataProvider() {
		return [
			'when field and value wild card is set with matches' => [
				['a1' => 'test', 'a2' => 'test', 'b1' => 'test'],
				false,
				'a',
				'te',
				true,
				true
			],
			'when field and value wild card is set without matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				true,
				'a',
				'st',
				true,
				true
			],
			'when value wild card is set with matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				false,
				'a2',
				'te',
				true,
				false
			],
			'when value wild card is set without matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				true,
				'a2',
				'123',
				true,
				false
			],
			'when field wild card is set with matches' => [
				['a1' => '123', 'a2' => '123', 'b1' => 'test'],
				false,
				'a',
				'123',
				false,
				true
			],
			'when field wild card is set without matches' => [
				['a1' => '123', 'a2' => 'test', 'b1' => 'test'],
				true,
				'a',
				'test123',
				false,
				true
			],
		];
	}
}
