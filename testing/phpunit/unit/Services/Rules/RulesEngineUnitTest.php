<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Rules;

use Phake;
use Services\Rules\Interfaces\RulesInterface;
use Services\Rules\RulesEngine;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class RulesEngineUnitTest
 */
class RulesEngineUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testAddRule() {
		$rule1 = Phake::mock(RulesInterface::class);
		$rule2 = Phake::mock(RulesInterface::class);

		$engine = new RulesEngine();
		$return = $engine
			->addRule($rule1)
			->addRule($rule2);

		$this->assertSameEquals($return, $engine);
		$this->assertSameEquals([$rule1, $rule2], $engine->getRules());
	}

	/**
	 * @return array
	 */
	public function runRulesDataProvider() {
		return [
			'when both rules are satisfied' => [true, true, true],
			'when rule 1 is satisfied and rule 2 is not satisfied' => [true, false, false],
			'when rule 1 is not satisfied and rule 2 is satisfied' => [false, true, false],
		];
	}

	/**
	 * @dataProvider runRulesDataProvider
	 * @param boolean $rule1Satisfied
	 * @param boolean $rule2Satisfied
	 * @param boolean $expected
	 * @return void
	 */
	public function testRunRules($rule1Satisfied, $rule2Satisfied, $expected) {
		$engine = new RulesEngine();

		$rule1 = Phake::mock(RulesInterface::class);
		$rule2 = Phake::mock(RulesInterface::class);

		Phake::when($rule1)->isSatisfied()->thenReturn($rule1Satisfied);
		Phake::when($rule2)->isSatisfied()->thenReturn($rule2Satisfied);

		$engine
			->addRule($rule1)
			->addRule($rule2);

		$this->assertSameEquals($expected, $engine->runRules());
	}
}
