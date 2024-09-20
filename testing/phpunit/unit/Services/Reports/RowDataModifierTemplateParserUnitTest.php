<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Models\Reports\RowDataModifierType;
use Phake;
use Services\Exceptions\Utils\TemplateParserException;
use Services\Reports\Interfaces\RowDataModifierInterface;
use Services\Reports\RowDataModifierFactory;
use Services\Reports\RowDataModifierTemplateParser;
use Services\Utils\Interfaces\InputStringTemplateParser;
use Services\Utils\TagTemplateParser;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class RowDataModifierTemplateParserUnitTest
 */
class RowDataModifierTemplateParserUnitTest extends AbstractPhpunitUnitTest
{
	/** @var RowDataModifierFactory | \Phake_IMock */
	private $factory;

	/** @var RowDataModifierTemplateParser */
	private $parser;

	/** @var InputStringTemplateParser */
	private $inputStringParser;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->factory = Phake::mock(RowDataModifierFactory::class);
		$this->inputStringParser = Phake::mock(InputStringTemplateParser::class);
		$this->parser = new RowDataModifierTemplateParser($this->factory, $this->inputStringParser);
	}

	/**
	 * @expectedException Services\Reports\Exceptions\DataModifierTemplateParserInvalidTemplateException
	 * @expectedExceptionMessage Unable to parse template
	 * @return void
	 */
	public function testGetModifierFromTemplateThrowsException() {
		$template = 'invalid-template';
		Phake::when($this->inputStringParser)->setTemplate($template)->thenThrow(new TemplateParserException('Unable to parse template'));
		$this->parser->getModifierFromTemplate($template);
	}

	/**
	 * @return void
	 */
	public function testGetModifierFromTemplate() {
		$template = '{%disposition:name=column1,columns=[column1,column2,column3],separator=comma%}';
		Phake::when($this->inputStringParser)->getTemplateType()->thenReturn(RowDataModifierType::DISPOSITION);
		$modifier = Phake::mock(RowDataModifierInterface::class);
		$attributes = [
			'name' => 'column1',
			'columns' => ['column1', 'column2', 'column3'],
			'separator' => 'comma'
		];
		Phake::when($this->inputStringParser)->getAttributes()->thenReturn($attributes);
		Phake::when($this->factory)->create(RowDataModifierType::DISPOSITION(), Phake::capture($data))->thenReturn($modifier);
		$this->parser->getModifierFromTemplate($template);

		$this->assertSameEquals($attributes, $data);
	}
}
