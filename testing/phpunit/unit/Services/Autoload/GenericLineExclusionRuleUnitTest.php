<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Services\Autoload\GenericLineExclusionRule;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GenericLineExclusionRuleUnitTest
 */
class GenericLineExclusionRuleUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function shouldExcludeDataProvider() {
		return [
			[['a' => 'z', 'b' => 'x'], ['b' => 'x'], true],
			[['a' => 'z', 'b' => 'x'], ['a' => 'x', 'b' => 'x'], true],
			[['a' => 'z', 'b' => 'x'], ['b' => 'y'], false],
		];
	}

	/**
	 * @dataProvider shouldExcludeDataProvider
	 * @param array   $line
	 * @param array   $exclusionColumns
	 * @param boolean $expected
	 * @return void
	 */
	public function testShouldExclude(array $line, array $exclusionColumns, $expected) {
		$rule = new GenericLineExclusionRule($exclusionColumns);
		$this->assertSameEquals($expected, $rule->shouldExclude($line));
	}
}
