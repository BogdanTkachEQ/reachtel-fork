<?php
/**
 * @author        rohith.mohan@equifax.com
 * @copyright    ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Utils;

use Services\Utils\SecurityZone;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SecurityZoneUnitTest
 */
class SecurityZoneUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function constantsDataProvider() {
		return [
			[SecurityZone::SINCH_INBOUND_SMS_SECURITY_ZONE, 184],
			[SecurityZone::SINCH_SMS_DR_SECURITY_ZONE, 185]
		];
	}

	/**
	 * @dataProvider constantsDataProvider
	 * @param integer $zone
	 * @param integer $value
	 * @return void
	 */
	public function testConstants($zone, $value) {
		$this->assertSameEquals($value, $zone);
	}
}
