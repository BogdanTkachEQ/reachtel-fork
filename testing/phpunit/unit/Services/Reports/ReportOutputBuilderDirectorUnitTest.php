<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Phake;
use Services\Reports\ArrayRulesEngineDecorator;
use Services\Reports\Builders\FilterRulesEngineBuilder;
use Services\Reports\Builders\ReportOutputBuilder;
use Services\Reports\Exceptions\DataModifierTemplateParserInvalidTemplateException;
use Services\Reports\Interfaces\RowDataModifierInterface;
use Services\Reports\ReportOutputBuilderDirector;
use Services\Reports\RowDataModifierTemplateParser;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class ReportOutputBuilderDirectorUnitTest
 */
class ReportOutputBuilderDirectorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var ReportOutputBuilder | \Phake_IMock */
	private $builder;

	/** @var RowDataModifierTemplateParser | \Phake_IMock */
	private $parser;

	/** @var FilterRulesEngineBuilder | \Phake_IMock */
	private $rulesEngineBuilder;

	/** @var ReportOutputBuilderDirector | \Phake_IMock */
	private $director;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->builder = Phake::mock(ReportOutputBuilder::class);
		$this->parser = Phake::mock(RowDataModifierTemplateParser::class);
		$this->rulesEngineBuilder = Phake::mock(FilterRulesEngineBuilder::class);
		$this->director = new ReportOutputBuilderDirector(
			$this->builder,
			$this->parser,
			$this->rulesEngineBuilder
		);
	}

	/**
	 * @return void
	 */
	public function testGetBuilder() {
		$this->assertSameEquals($this->builder, $this->director->getBuilder());
	}

	/**
	 * @return void
	 */
	public function testSetOutputColumnString() {
		$outputColumns = 'col1,col2,col3';
		$modifier = Phake::mock(RowDataModifierInterface::class);
		Phake::when($this->parser)->getModifierFromTemplate('col2')->thenReturn($modifier);
		Phake::when($this->parser)
			->getModifierFromTemplate('col1')
			->thenThrow(new DataModifierTemplateParserInvalidTemplateException());
		Phake::when($this->parser)
			->getModifierFromTemplate('col3')
			->thenThrow(new DataModifierTemplateParserInvalidTemplateException());
		$this->assertSameEquals($this->director, $this->director->setOutputColumnString($outputColumns));
		Phake::verify($this->builder)->setOutputColumns(['col1', $modifier, 'col3']);
	}

	/**
	 * @return void
	 */
	public function testSetFilterString() {
		$filterString = 'test-filter-string';
		$rulesEngine = Phake::mock(ArrayRulesEngineDecorator::class);
		Phake::when($this->rulesEngineBuilder)->buildFilterRulesEngine($filterString)->thenReturn($rulesEngine);
		$this->assertSameEquals($this->director, $this->director->setFilterString($filterString));
		Phake::verify($this->builder)->setFilterRulesEngine($rulesEngine);
	}

	/**
	 * @return void
	 */
	public function testSetHeaderMapString() {
		$headerString = 'col1:header1, col2:header2, col3:header3';
		$this->assertSameEquals($this->director, $this->director->setHeaderMapString($headerString));
		Phake::verify($this->builder)->setHeaderMap(['col1' => 'header1', 'col2' => 'header2', 'col3' => 'header3']);
	}
}
