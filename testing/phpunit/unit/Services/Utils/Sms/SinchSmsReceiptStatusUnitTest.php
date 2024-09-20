<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Utils\Sms;

use Services\Utils\Sms\SinchSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SinchSmsReceiptStatusUnitTest
 */
class SinchSmsReceiptStatusUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function translateDataProvider() {
		return [
			['Queued', SinchSmsReceiptStatus::SUBMITTED()],
			['Delivered', SinchSmsReceiptStatus::DELIVERED()],
			['Expired', SinchSmsReceiptStatus::EXPIRED()],
			['random', SinchSmsReceiptStatus::UNKNOWN()]
		];
	}

	/**
	 * @dataProvider translateDataProvider
	 * @param string                $string
	 * @param SinchSmsReceiptStatus $status
	 * @return void
	 */
	public function testTranslate($string, SinchSmsReceiptStatus $status) {
		$this->assertSameEquals($status, SinchSmsReceiptStatus::translate($string));
	}
}
