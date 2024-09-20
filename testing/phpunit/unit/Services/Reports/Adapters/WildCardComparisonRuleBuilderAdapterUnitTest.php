<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Reports\Adapters;

use Phake;
use Services\Reports\Adapters\WildCardComparisonRuleBuilderAdapter;
use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\ArrayRules\Builders\AbstractWildCardComparisonRuleBuilder;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WildCardComparisonRuleBuilderAdapterUnitTest
 */
class WildCardComparisonRuleBuilderAdapterUnitTest extends AbstractPhpunitUnitTest
{
	/** @var AbstractWildCardComparisonRuleBuilder | \Phake_IMock */
	private $builder;

	/** @var WildCardComparisonRuleBuilderAdapter */
	private $adapter;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->builder = Phake::mock(AbstractWildCardComparisonRuleBuilder::class);
		$this->adapter = new WildCardComparisonRuleBuilderAdapter($this->builder);
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$data = [
			'field' => 'test-field',
			'value' => 'test-value',
			'compare' => [
				'field',
				'value'
			]
		];

		$rule = Phake::mock(AbstractArrayDataRule::class);
		Phake::when($this->builder)->buildFromArray(Phake::anyParameters())->thenReturn($rule);
		$this->assertSameEquals($rule, $this->adapter->buildFromArray($data));

		Phake::verify($this->builder)->buildFromArray(Phake::capture($modifiedData));
		$this->assertTrue($modifiedData['fieldwildcard']);
		$this->assertTrue($modifiedData['valuewildcard']);
	}
}
