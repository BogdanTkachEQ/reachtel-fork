<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Builders;

use Services\Reports\Builders\DateColumnRowDataModifierBuilder;
use Services\Reports\DateColumnRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DateColumnRowDataModifierBuilderUnitTest
 */
class DateColumnRowDataModifierBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Data column modifier requires a header name to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenHeaderNameNotSet() {
		$builder = new DateColumnRowDataModifierBuilder();
		$builder->buildFromArray([]);
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Data column modifier requires column to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenColumnNotSet() {
		$builder = new DateColumnRowDataModifierBuilder();
		$builder->buildFromArray(['name' => 'test']);
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Data column modifier requires format to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenFormatNotSet() {
		$builder = new DateColumnRowDataModifierBuilder();
		$builder->buildFromArray(['name' => 'test', 'column' => 'test-column']);
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$name = 'test-name';
		$column = 'test-column';
		$format = 'd-m-Y H:i:s';
		$builder = new DateColumnRowDataModifierBuilder();
		$modifier = $builder->buildFromArray(['name' => $name, 'column' => $column, 'format' => $format]);
		$this->assertInstanceOf(DateColumnRowDataModifier::class, $modifier);
		$this->assertSameEquals($name, $modifier->getHeaderName());
		$this->assertSameEquals($column, $modifier->getColumn());
		$this->assertSameEquals($format, $modifier->getFormat());
	}
}
