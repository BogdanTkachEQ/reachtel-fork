<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Models\Reports\DataSeparatorType;
use Services\Reports\DispositionColumnRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DispositionColumnRowDataModifierUnitTest
 */
class DispositionColumnRowDataModifierUnitTest extends AbstractPhpunitUnitTest
{
	/** @var DispositionColumnRowDataModifier */
	private $modifier;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->modifier = new DispositionColumnRowDataModifier();
	}

	/**
	 * @return void
	 */
	public function testSettersAndGetters() {
		$headerName = 'test-header';
		$columns = ['column1', 'column2'];
		$data = ['value1', 'value2'];
		$separator = \Phake::mock(DataSeparatorType::class);

		$this
			->modifier
			->setHeaderName($headerName)
			->setColumns($columns)
			->setSeparator($separator)
			->setRowData($data);

		$this->assertSameEquals($headerName, $this->modifier->getHeaderName());
		$this->assertSameEquals($columns, $this->modifier->getColumns());
		$this->assertSameEquals($separator, $this->modifier->getSeparator());
		$this->assertSameEquals($data, $this->modifier->getRowData());
	}

	/**
	 * @return void
	 */
	public function testGetModifiedData() {
		$this
			->modifier
			->setHeaderName('test')
			->setSeparator(DataSeparatorType::UNDER_SCORE())
			->setColumns(['column1', 'column3', 'column5'])
			->setRowData(
				[
					'column1' => 'value1',
					'column2' => 'value2',
					'column3' => 'value3',
					'column4' => 'value4',
					'column5' => 'value5',
				]
			);

		$expected = 'value1_value3_value5';
		$this->assertSameEquals($expected, $this->modifier->getModifiedData());
	}
}
