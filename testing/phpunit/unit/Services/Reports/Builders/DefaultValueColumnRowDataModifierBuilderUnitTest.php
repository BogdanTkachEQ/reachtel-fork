<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Builders;

use Services\Reports\Builders\DefaultValueColumnRowDataModifierBuilder;
use Services\Reports\DefaultValueColumnRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DefaultValueColumnRowDataModifierBuilderUnitTest
 */
class DefaultValueColumnRowDataModifierBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Default value modifier requires name to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsException() {
		$builder = new DefaultValueColumnRowDataModifierBuilder();
		$builder->buildFromArray([]);
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Default value modifier requires value to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenValueNotSet() {
		$builder = new DefaultValueColumnRowDataModifierBuilder();
		$builder->buildFromArray(['name' => 'columnname']);
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$data = [
			'name' => 'test-name',
			'value' => 'test-value',
			'valuecolumn' => 'test-column'
		];

		$builder = new DefaultValueColumnRowDataModifierBuilder();

		/** @var DefaultValueColumnRowDataModifier $modifier */
		$modifier = $builder->buildFromArray($data);

		$this->assertInstanceOf(DefaultValueColumnRowDataModifier::class, $modifier);
		$this->assertSameEquals('test-name', $modifier->getColumnName());
		$this->assertSameEquals('test-value', $modifier->getDefaultValue());
		$this->assertSameEquals('test-column', $modifier->getValueColumn());
	}
}
