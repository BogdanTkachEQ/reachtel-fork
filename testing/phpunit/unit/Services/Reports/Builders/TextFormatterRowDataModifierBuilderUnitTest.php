<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Builders;

use Services\Reports\Builders\TextFormatterRowDataModifierBuilder;
use Services\Reports\TextFormatterRowDataModifier;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class TextFormatterRowDataModifierBuilderUnitTest
 */
class TextFormatterRowDataModifierBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Text formatter modifier requires a header name to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenNameNotSet() {
		$builder = new TextFormatterRowDataModifierBuilder();
		$builder->buildFromArray([]);
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Text formatter modifier requires column to be set
	 * @return void
	 */
	public function testBuildFromArrayThrowsExceptionWhenColumnNotSet() {
		$builder = new TextFormatterRowDataModifierBuilder();
		$builder->buildFromArray(['name' => 'test']);
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$data = [
			'name' => 'test-name',
			'column' => 'test-column',
			'replacelinefeedby' => ' ',
			'maxlength' => '15',
			'useellipsis' => '1'
		];

		$builder = new TextFormatterRowDataModifierBuilder();
		$modifier = $builder->buildFromArray($data);
		$this->assertInstanceOf(TextFormatterRowDataModifier::class, $modifier);

		$this->assertSameEquals($data['name'], $modifier->getHeaderName());
		$this->assertSameEquals($data['replacelinefeedby'], $modifier->getLineFeedReplace());
		$this->assertSameEquals($data['maxlength'], $modifier->getMaxLength());
		$this->assertTrue($modifier->isAddEllipsis());
	}
}
