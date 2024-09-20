<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Services\Reports\DefaultValueColumnRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DefaultValueColumnRowDataModifier
 */
class DefaultValueColumnRowDataModifierUnitTest extends AbstractPhpunitUnitTest
{
	/** @var string */
	private $columnName;

	/** @var string */
	private $defaultValue;

	/** @var DefaultValueColumnRowDataModifier */
	private $modifier;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->columnName = 'test-name';
		$this->defaultValue = 'test-value';
		$this->modifier = new DefaultValueColumnRowDataModifier($this->columnName, $this->defaultValue);
	}

	/**
	 * @return void
	 */
	public function testGetDefaultValue() {
		$this->assertSameEquals($this->defaultValue, $this->modifier->getDefaultValue());
	}

	/**
	 * @return void
	 */
	public function testGetColumnName() {
		$this->assertSameEquals($this->columnName, $this->modifier->getColumnName());
	}

	/**
	 * @return void
	 */
	public function testGetHeaderName() {
		$this->assertSameEquals($this->columnName, $this->modifier->getHeaderName());
	}

	/**
	 * @return void
	 */
	public function testGetModifiedData() {
		$this->assertSameEquals($this->defaultValue, $this->modifier->getModifiedData());
	}

	/**
	 * @return void
	 */
	public function testSetRowData() {
		$this->assertInstanceOf(
			DefaultValueColumnRowDataModifier::class,
			$this->modifier->setRowData(['column1' => 'test'])
		);
	}

	/**
	 * @return void
	 */
	public function testGetModifiedDataWhenThereIsAValueColumn() {
		$valueColumn = 'column1';
		$modifier = new DefaultValueColumnRowDataModifier($this->columnName, $this->defaultValue, $valueColumn);
		$modifier->setRowData(['column1' => 'test', 'column2' => 'test123']);
		$this->assertSameEquals('test', $modifier->getModifiedData());
	}

	/**
	 * @return void
	 */
	public function testGetModifiedDataReturnsDefaultValueWithValueColumnSet() {
		$valueColumn = 'column1';
		$modifier = new DefaultValueColumnRowDataModifier($this->columnName, $this->defaultValue, $valueColumn);
		$modifier->setRowData(['column5' => 'test', 'column2' => 'test123']);
		$this->assertSameEquals($this->defaultValue, $modifier->getModifiedData());
	}
}
