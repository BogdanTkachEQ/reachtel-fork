<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Adapters;

use Services\Reports\Adapters\ArrayRuleBuilderAdapterFactory;
use Services\Reports\Adapters\WildCardComparisonRuleBuilderAdapter;
use Services\Rules\ArrayRules\Builders\AbstractWildCardComparisonRuleBuilder;
use Services\Rules\ArrayRules\Builders\EqualToRuleBuilder;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class ArrayRuleBuilderAdapterFactoryUnitTest
 */
class ArrayRuleBuilderAdapterFactoryUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testCreate() {
		$factory = new ArrayRuleBuilderAdapterFactory();
		$this->assertInstanceOf(
			WildCardComparisonRuleBuilderAdapter::class,
			$factory->create(\Phake::mock(AbstractWildCardComparisonRuleBuilder::class))
		);

		$builder = \Phake::mock(EqualToRuleBuilder::class);
		$this->assertSameEquals($builder, $factory->create($builder));
	}
}
