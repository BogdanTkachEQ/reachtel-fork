<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Utils\Sms;

use Services\Utils\Sms\YabbrSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class YabbrSmsReceiptStatusUnitTest
 */
class YabbrSmsReceiptStatusUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function translateDataProvider() {
		return [
			['undelivered', YabbrSmsReceiptStatus::UNDELIVERED()],
			['delivered', YabbrSmsReceiptStatus::DELIVERED()],
			['expired', YabbrSmsReceiptStatus::EXPIRED()],
			['rejected', YabbrSmsReceiptStatus::UNDELIVERED()],
			['random', YabbrSmsReceiptStatus::UNKNOWN()]
		];
	}

	/**
	 * @dataProvider translateDataProvider
	 * @param string                $string
	 * @param YabbrSmsReceiptStatus $status
	 * @return void
	 */
	public function testTranslate($string, YabbrSmsReceiptStatus $status) {
		$this->assertSameEquals($status, YabbrSmsReceiptStatus::translate($string));
	}
}
