<?php
/**
 * CampaignUtilsUnitTest
 *
 * @author		kevin.ohayon@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Utils;

use PHPUnit_Framework_TestCase;
use Services\Utils\CampaignUtils;

/**
 * CampaignUtilsUnitTest
 */
class CampaignUtilsUnitTest extends PHPUnit_Framework_TestCase {

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function getDaysOfWeekArrayData() {
		return [
			// No active days
			[[false, false, false, false, false, false, false], 0],
			// Monday + Tuesday
			[[true, true, false, false, false, false, false], (1 + 2)],
			// Wednesday + Saturday
			[[false, false, true, false, false, true, false], (4 + 32)],
			// Monday + Wednesday + Sunday
			[[true, false, true, false, false, false, true], (1 + 4 + 64)],
			// Not Sunday
			[[true, true, true, true, true, true, false], (1 + 2 + 4 + 8 + 16 + 32)],
			// All days
			[[true, true, true, true, true, true, true], (1 + 2 + 4 + 8 + 16 + 32 + 64)],
			// Monday + Saturday with days as keys
			[
				[
					'mon' => true,
					'tue' => false,
					'wed' => false,
					'thu' => false,
					'fri' => false,
					'sat' => true,
					'sun' => false,
				],
				(1 + 32),
				true
			],
		];
	}

	/**
	 * @dataProvider getDaysOfWeekArrayData
	 * @param array   $expected
	 * @param integer $int
	 * @param boolean $keys
	 * @return void
	 */
	public function testGetDaysOfWeekArray(array $expected, $int, $keys = false) {
		$this->assertEquals(
			$expected,
			CampaignUtils::getDaysOfWeekArray($int, $keys)
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function getBitwiseDaysOfWeekData() {
		return [
			// No active days
			[0, [false, false, false, false, false, false, false]],
			// Monday + Tuesday
			[(1 + 2), [true, true, false, false, false, false, false]],
			// Wednesday + Saturday
			[(4 + 32), [false, false, true, false, false, true, false]],
			// Monday + Wednesday + Sunday
			[(1 + 4 + 64), [true, false, true, false, false, false, true]],
			// Not Sunday
			[(1 + 2 + 4 + 8 + 16 + 32), [true, true, true, true, true, true, false]],
			// All days
			[(1 + 2 + 4 + 8 + 16 + 32 + 64), [true, true, true, true, true, true, true]],
			// Monday + Saturday with days as keys
			[
				(1 + 32),
				[
					'mon' => true,
					'tue' => false,
					'wed' => false,
					'thu' => false,
					'fri' => false,
					'sat' => true,
					'sun' => false,
				],
			],
		];
	}

	/**
	 * @dataProvider getBitwiseDaysOfWeekData
	 * @param integer $expected
	 * @param array   $daysOfWeek
	 * @return void
	 */
	public function testGetBitwiseDaysOfWeek($expected, array $daysOfWeek) {
		$this->assertEquals(
			$expected,
			CampaignUtils::getBitwiseDaysOfWeek($daysOfWeek)
		);
	}
}
