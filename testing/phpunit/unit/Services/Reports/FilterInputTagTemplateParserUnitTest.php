<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Phake;
use Services\Reports\Adapters\ArrayRuleBuilderAdapterFactory;
use Services\Reports\FilterInputTagTemplateParser;
use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\ArrayRules\ArrayDataRuleBuilderFactory;
use Services\Rules\ArrayRules\Builders\EqualToRuleBuilder;
use Services\Rules\ArrayRules\RuleType;
use Services\Utils\Interfaces\InputStringTemplateParser;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class FilterInputTagTemplateParserUnitTest
 */
class FilterInputTagTemplateParserUnitTest extends AbstractPhpunitUnitTest
{
	/** @var InputStringTemplateParser | \Phake_IMock */
	private $templateParser;

	/** @var ArrayDataRuleBuilderFactory | \Phake_IMock */
	private $ruleFactory;

	/** @var ArrayRuleBuilderAdapterFactory | \Phake_IMock */
	private $ruleBuilderAdapterFactory;

	/** @var FilterInputTagTemplateParser */
	private $parser;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->templateParser = Phake::mock(InputStringTemplateParser::class);
		$this->ruleFactory = Phake::mock(ArrayDataRuleBuilderFactory::class);
		$this->ruleBuilderAdapterFactory = Phake::mock(ArrayRuleBuilderAdapterFactory::class);

		$this->parser = new FilterInputTagTemplateParser(
			$this->templateParser,
			$this->ruleFactory,
			$this->ruleBuilderAdapterFactory
		);
	}

	/**
	 * @return void
	 */
	public function testGetRuleFromTemplate() {
		$template = '{%test template%}';
		Phake::when($this->templateParser)->getTemplateType()->thenReturn(RuleType::EQUALTO);
		$builder = Phake::mock(EqualToRuleBuilder::class);
		Phake::when($this->ruleFactory)->create(Phake::anyParameters())->thenReturn($builder);
		Phake::when($this->ruleBuilderAdapterFactory)->create($builder)->thenReturn($builder);
		$attributes = [
			'field' => 'test',
			'value' => 123
		];
		Phake::when($this->templateParser)->getAttributes()->thenReturn($attributes);
		$rule = Phake::mock(AbstractArrayDataRule::class);
		Phake::when($builder)->buildFromArray($attributes)->thenReturn($rule);

		$this->assertSameEquals($rule, $this->parser->getRuleFromTemplate($template));
		Phake::verify($this->templateParser)->setTemplate($template);
		Phake::verify($this->ruleFactory)->create(Phake::capture($ruleType));
		$this->assertInstanceOf(RuleType::class, $ruleType);
		$this->assertTrue($ruleType->is(RuleType::EQUALTO));
		Phake::verify($builder)->buildFromArray($attributes);
	}
}
