<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Utils;

use Services\Utils\TagTemplateParser;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class TagTemplateParserUnitTest
 */
class TagTemplateParserUnitTest extends AbstractPhpunitUnitTest
{
	/** @var TagTemplateParser */
	private $parser;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->parser = new TagTemplateParser();
	}

	/**
	 * @expectedException Services\Exceptions\Utils\TemplateParserException
	 * @expectedExceptionMessage Unable to parse template
	 * @return void
	 */
	public function testSetTemplateThrowsException() {
		$this->parser->setTemplate('Invalid Template');
	}

	/**
	 * @return void
	 */
	public function testSetTemplate() {
		$template = '{%disposition:name=column1,columns=[column1,column 2,column3],separator=comma%}';
		$this->assertInstanceOf(TagTemplateParser::class, $this->parser->setTemplate($template));

		$expectedData = [
			'name' => 'column1',
			'columns' => ['column1', 'column 2', 'column3'],
			'separator' => 'comma'
		];

		$this->assertEquals('disposition', $this->parser->getTemplateType());
		$this->assertSameEquals($expectedData, $this->parser->getAttributes());
	}

	/**
	 * @return array
	 */
	public function splitTemplateDataProvider() {
		return [
			[
				'{%equal:field=Q1,value=hello%},{%notlike:field=Q3,value=t,compare=[field,value]%},{%notequal:field:Q2,value=45 45%}',
				[
					'{%equal:field=Q1,value=hello%}',
					'{%notlike:field=Q3,value=t,compare=[field,value]%}',
					'{%notequal:field:Q2,value=45 45%}'
				]
			],
			[
				'UNIQUEID,DESTINATION,STATUS, SENT,DELIVERED, Customer Name, Customer number , Incident Id,NON_KEYWORD_RESPONSE, campaign',
				[
					'UNIQUEID',
					'DESTINATION',
					'STATUS',
					'SENT',
					'DELIVERED',
					'Customer Name',
					'Customer number',
					'Incident Id',
					'NON_KEYWORD_RESPONSE',
					'campaign'
				]
			],
			[
				'targetkey, column1, {%textformatter:name=textcolumn,column=column2,replacelinefeedby= ,maxlength=10,useellipsis=1%}, column3',
				[
					'targetkey',
					'column1',
					'{%textformatter:name=textcolumn,column=column2,replacelinefeedby= ,maxlength=10,useellipsis=1%}',
					'column3'
				]
			]
		];
	}

	/**
	 * @dataProvider splitTemplateDataProvider
	 * @param string $templateString
	 * @param array  $expected
	 * @return void
	 */
	public function testSplitTemplates($templateString, array $expected) {
		$templates = TagTemplateParser::splitTemplates($templateString);
		$this->assertSameEquals(
			$expected,
			$templates
		);
	}

	/**
	 * @return void
	 */
	public function testGetAttributes() {
		$this->parser->setTemplate('{%textformatter:name=textcolumn,column=column2,replacelinefeedby= ,maxlength=10,useellipsis=1%}');

		$this->assertSameEquals(
			[
				'name' => 'textcolumn',
				'column' => 'column2',
				'replacelinefeedby' => ' ',
				'maxlength' => '10',
				'useellipsis' => '1'
			],
			$this->parser->getAttributes()
		);
	}
}
