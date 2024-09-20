<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Builders;

use Models\Reports\DataSeparatorType;
use Services\Reports\Builders\DispositionColumnRowDataModifierBuilder;
use Services\Reports\DispositionColumnRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DispositionColumnRowDataModifierBuilderUnitTest
 */
class DispositionColumnRowDataModifierBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Disposition modifier requires columns to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenColumnsAreNotSet() {
		$builder = new DispositionColumnRowDataModifierBuilder();
		$builder->buildFromArray([]);
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Disposition modifier requires a header name to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenNameNotSet() {
		$builder = new DispositionColumnRowDataModifierBuilder();
		$builder->buildFromArray(['columns' => ['column1', 'column2']]);
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$data = [
			'columns' => ['column1', 'column2'],
			'name' => 'test-name',
			'separator' => 'hyphen'
		];

		$builder = new DispositionColumnRowDataModifierBuilder();

		/** @var DispositionColumnRowDataModifier $modifier */
		$modifier = $builder->buildFromArray($data);

		$this->assertInstanceOf(DispositionColumnRowDataModifier::class, $modifier);
		$this->assertSameEquals(['column1', 'column2'], $modifier->getColumns());
		$this->assertSameEquals('test-name', $modifier->getHeaderName());
		$this->assertSameEquals(DataSeparatorType::HYPHEN(), $modifier->getSeparator());
	}
}
