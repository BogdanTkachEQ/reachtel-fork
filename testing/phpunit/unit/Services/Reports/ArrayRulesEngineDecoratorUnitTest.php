<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Phake;
use Services\Reports\ArrayRulesEngineDecorator;
use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\RulesEngine;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class ArrayRulesEngineDecoratorUnitTest
 */
class ArrayRulesEngineDecoratorUnitTest extends AbstractPhpunitUnitTest
{

	/** @var RulesEngine | \Phake_IMock */
	private $rulesEngine;

	/** @var ArrayRulesEngineDecorator */
	private $decorator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->rulesEngine = Phake::mock(RulesEngine::class);
		$this->decorator = new ArrayRulesEngineDecorator($this->rulesEngine);
	}

	/**
	 * @return void
	 */
	public function testSetData() {
		$rule1 = Phake::mock(AbstractArrayDataRule::class);
		$rule2 = Phake::mock(AbstractArrayDataRule::class);
		Phake::when($this->rulesEngine)->getRules()->thenReturn([$rule1, $rule2]);
		$data = ['test-data'];
		$this->assertSameEquals($this->decorator, $this->decorator->setData($data));

		Phake::verify($rule1)->setData($data);
		Phake::verify($rule2)->setData($data);
	}

	/**
	 * @return void
	 */
	public function testAddArrayDataRules() {
		$rule = Phake::mock(AbstractArrayDataRule::class);
		$this->assertSameEquals($this->decorator, $this->decorator->addArrayDataRules($rule));
		Phake::verify($this->rulesEngine)->addRule($rule);
	}

	/**
	 * @return void
	 */
	public function runRules() {
		Phake::when($this->rulesEngine)->runRules()->thenReturn(true);
		$this->assertTrue($this->decorator->runRules());

		Phake::when($this->rulesEngine)->runRules()->thenReturn(false);
		$this->assertTrue($this->decorator->runRules());
	}
}
