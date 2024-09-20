<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Phake;
use Services\Autoload\Interfaces\LineExclusionRuleInterface;
use Services\Autoload\LineExclusionEvaluator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class LineExclusionEvaluatorUnitTest
 */
class LineExclusionEvaluatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var LineExclusionEvaluator */
	private $evaluator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->evaluator = new LineExclusionEvaluator();
	}

	/**
	 * @return void
	 */
	public function testAddRule() {
		$this->assertInstanceOf(
			LineExclusionEvaluator::class,
			$this->evaluator->addRule(Phake::mock(LineExclusionRuleInterface::class))
		);
	}

	/**
	 * @return void
	 */
	public function testEvaluate() {
		$rule1 = Phake::mock(LineExclusionRuleInterface::class);
		$rule2 = Phake::mock(LineExclusionRuleInterface::class);

		$line = ['a' => 1, 'b' => 2];
		$this
			->evaluator
			->addRule($rule1)
			->addRule($rule2);

		Phake::when($rule1)->shouldExclude($line)->thenReturn(false);
		Phake::when($rule1)->shouldExclude($line)->thenReturn(false);

		$this->assertSameEquals(false, $this->evaluator->evaluate($line));

		Phake::when($rule1)->shouldExclude($line)->thenReturn(true);

		$this->assertSameEquals(true, $this->evaluator->evaluate($line));
	}
}
