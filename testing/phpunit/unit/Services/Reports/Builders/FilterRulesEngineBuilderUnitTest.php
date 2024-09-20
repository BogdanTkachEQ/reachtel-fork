<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Builders;

use Phake;
use Services\Reports\ArrayRulesEngineDecorator;
use Services\Reports\Builders\FilterRulesEngineBuilder;
use Services\Reports\FilterInputTagTemplateParser;
use Services\Rules\ArrayRules\AbstractArrayDataRule;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class FilterRulesEngineBuilderUnitTest
 */
class FilterRulesEngineBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/** @var FilterInputTagTemplateParser | \Phake_IMock */
	private $parser;

	/** @var ArrayRulesEngineDecorator | \Phake_IMock */
	private $rulesEngine;

	/** @var FilterRulesEngineBuilder */
	private $builder;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->parser = Phake::mock(FilterInputTagTemplateParser::class);
		$this->rulesEngine = Phake::mock(ArrayRulesEngineDecorator::class);

		$this->builder = new FilterRulesEngineBuilder(
			$this->parser,
			$this->rulesEngine
		);
	}

	/**
	 * @return void
	 */
	public function testBuildFilterRulesEngine() {
		$filterString = '{%equal:field=Q1,value=123%},{%like:field=Q,value=123,compare=[field,value]%}';
		$rule1 = Phake::mock(AbstractArrayDataRule::class);
		$rule2 = Phake::mock(AbstractArrayDataRule::class);
		Phake::when($this->parser)->getRuleFromTemplate('{%equal:field=Q1,value=123%}')->thenReturn($rule1);
		Phake::when($this->parser)
			->getRuleFromTemplate('{%like:field=Q,value=123,compare=[field,value]%}')
			->thenReturn($rule2);

		$this->assertSameEquals($this->rulesEngine, $this->builder->buildFilterRulesEngine($filterString));
		Phake::verify($this->rulesEngine)->addArrayDataRules($rule1);
		Phake::verify($this->rulesEngine)->addArrayDataRules($rule2);
	}
}
