<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Services\Reports\DateColumnRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DateColumnRowDataModifierUnitTest
 */
class DateColumnRowDataModifierUnitTest extends AbstractPhpunitUnitTest
{
	/** @var string */
	private $headerName;

	/** @var string */
	private $column;

	/** @var string */
	private $format;

	/** @var DateColumnRowDataModifier */
	private $modifier;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->headerName = 'test-name';
		$this->column = 'test-column';
		$this->format = 'd-m-Y\TH:i:s';
		$this->modifier = new DateColumnRowDataModifier(
			$this->headerName,
			$this->column,
			$this->format
		);
	}

	/**
	 * @return void
	 */
	public function testSetRowData() {
		$this->assertSameEquals($this->modifier, $this->modifier->setRowData([]));
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
	public function testGetColumn() {
		$this->assertSameEquals($this->column, $this->modifier->getColumn());
	}

	/**
	 * @return void
	 */
	public function testGetFormat() {
		$this->assertSameEquals($this->format, $this->modifier->getFormat());
	}

	/**
	 * @return void
	 */
	public function testGetModifiedData() {
		$data = ['test-column' => '2020-05-12 23:06:53'];
		$this->modifier->setRowData($data);
		$this->assertSameEquals(
			'12-05-2020T23:06:53',
			$this->modifier->getModifiedData()
		);
	}
}
