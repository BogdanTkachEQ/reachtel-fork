<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Services\Reports\TextFormatterRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class TextFormatterRowDataModifierUnitTest
 */
class TextFormatterRowDataModifierUnitTest extends AbstractPhpunitUnitTest
{
	/** @var string */
	private $columnName;

	/** @var string */
	private $headerName;

	/** @var TextFormatterRowDataModifier */
	private $modifier;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->columnName = 'test-column';
		$this->headerName = 'test-name';

		$this->modifier = new TextFormatterRowDataModifier($this->headerName, $this->columnName);
	}

	/**
	 * @return void
	 */
	public function testSetRowData() {
		$data = ['column1' => 'value1', 'column2' => 'value2'];
		$this->assertInstanceOf(
			TextFormatterRowDataModifier::class,
			$this->modifier->setRowData($data)
		);
	}

	/**
	 * @return void
	 */
	public function testGetHeaderName() {
		$this->assertSameEquals($this->headerName, $this->modifier->getHeaderName());
	}

	/**
	 * @return void
	 */
	public function testSetLineFeedReplace() {
		$lineFeedReplace = 'replace-text';
		$this->assertInstanceOf(
			TextFormatterRowDataModifier::class,
			$this->modifier->setLineFeedReplace($lineFeedReplace)
		);

		$this->assertSameEquals($lineFeedReplace, $this->modifier->getLineFeedReplace());
	}

	/**
	 * @return void
	 */
	public function testSetMaxLength() {
		$maxLength = 13;
		$this->assertInstanceOf(
			TextFormatterRowDataModifier::class,
			$this->modifier->setMaxLength($maxLength)
		);

		$this->assertSameEquals($maxLength, $this->modifier->getMaxLength());
	}

	/**
	 * @return void
	 */
	public function testIsAddEllipsis() {
		$this->assertInstanceOf(
			TextFormatterRowDataModifier::class,
			$this->modifier->addEllipsis()
		);

		$this->assertFalse($this->modifier->isAddEllipsis());

		$this->assertInstanceOf(
			TextFormatterRowDataModifier::class,
			$this->modifier->addEllipsis(true)
		);

		$this->assertTrue($this->modifier->isAddEllipsis());
	}

	/**
	 * @return void
	 */
	public function testGetModifiedData() {
		$data = [
			'column1' => 'value 1',
			'test-column' => "This text\nhas to be formatted"
		];

		$this
			->modifier
			->setRowData($data)
			->addEllipsis(true)
			->setLineFeedReplace(' ')
			->setMaxLength(15);

		$expected = 'This text has t...';
		$this->assertSameEquals($expected, $this->modifier->getModifiedData());
	}
}
