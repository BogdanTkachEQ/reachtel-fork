<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Utils;

use Phake;
use Services\Suppliers\Interfaces\SmsRetrievableInterface;
use Services\Suppliers\Interfaces\SmsSendableInterface;
use Services\Suppliers\Utils\SmsUtil;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsUtilsUnitTest
 */
class SmsUtilsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testIsSendable() {
		$service = Phake::mock(SmsSendableInterface::class);
		$this->assertTrue(SmsUtil::isSendable($service));

		$service = Phake::mock(SmsRetrievableInterface::class);
		$this->assertFalse(SmsUtil::isSendable($service));
	}

	/**
	 * @return void
	 */
	public function testIsRetrievable() {
		$service = Phake::mock(SmsSendableInterface::class);
		$this->assertFalse(SmsUtil::isRetrievable($service));

		$service = Phake::mock(SmsRetrievableInterface::class);
		$this->assertTrue(SmsUtil::isRetrievable($service));
	}
}
