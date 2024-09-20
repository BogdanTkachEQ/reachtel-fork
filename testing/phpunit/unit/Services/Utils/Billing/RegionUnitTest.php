<?php
/**
 * RegionUnitTest
 *
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Utils\Billing;

use Services\Utils\Billing\Region;
use testing\AbstractPhpunitTest;

/**
 * Class RegionUnitTest
 */
class RegionUnitTest extends AbstractPhpunitTest
{
	/**
	 * @return array
	 */
	public function getBillingRegionIdFromCodeDataProvider() {
		return [
			'Australia' => ['au', 1],
			'Singapore' => ['sg', 3],
			'New Zealand' => ['nz', 2],
			'Great Britain' => ['gb', 4],
			'Philippines' => ['ph', 5],
			'Other' => ['test', 6],
		];
	}

	/**
	 * @dataProvider getBillingRegionIdFromCodeDataProvider
	 * @param string  $code
	 * @param integer $expected
	 * @return void
	 */
	public function testGetBillingRegionIdFromCode($code, $expected) {
		$actual = Region::getBillingRegionIdFromCode($code);

		$this->assertSameEquals($expected, $actual);
	}
}
