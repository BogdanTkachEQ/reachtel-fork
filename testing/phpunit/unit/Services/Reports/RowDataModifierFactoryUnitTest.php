<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports;

use Models\Reports\DataSeparatorType;
use Models\Reports\RowDataModifierType;
use Services\Reports\DispositionColumnRowDataModifier;
use Services\Reports\RowDataModifierFactory;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class RowDataModifierFactoryUnitTest
 */
class RowDataModifierFactoryUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testCreate() {
		$factory = new RowDataModifierFactory();

		$type = RowDataModifierType::DISPOSITION();
		$data = [
			'columns' => ['column1', 'column2', 'column3'],
			'name' => 'test-name',
			'separator' => 'pipe'
		];

		/** @var DispositionColumnRowDataModifier $modifier */
		$modifier = $factory->create($type, $data);

		$this->assertInstanceOf(DispositionColumnRowDataModifier::class, $modifier);
		$this->assertSameEquals(['column1', 'column2', 'column3'], $modifier->getColumns());
		$this->assertSameEquals('test-name', $modifier->getHeaderName());
		$this->assertSameEquals(DataSeparatorType::PIPE(), $modifier->getSeparator());
	}
}
