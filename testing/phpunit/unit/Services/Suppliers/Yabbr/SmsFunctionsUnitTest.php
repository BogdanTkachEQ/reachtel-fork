<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Yabbr;

use Services\Suppliers\Yabbr\SmsFunctions;
use Services\Utils\Sms\YabbrSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsFunctionsUnitTest
 */
class SmsFunctionsUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function statusDataProvider() {
		return [
			'delivered' => [
				['simulated' => '2019-10-12T23:56:57.000Z', 'delivered' => '2019-10-12T23:56:57.000Z'],
				YabbrSmsReceiptStatus::DELIVERED()
			],
			'undelivered' => [
				['simulated' => '2019-10-10T23:56:57.000Z', 'undelivered' => '2019-10-10T23:56:57.000Z'],
				YabbrSmsReceiptStatus::UNDELIVERED()
			],
			'expired' => [
				['simulated' => '2019-09-12T23:56:57.000Z', 'expired' => '2019-09-12T23:56:57.000Z'],
				YabbrSmsReceiptStatus::EXPIRED()
			],
			'rejected' => [
				['simulated' => '2019-11-12T23:56:57.000Z', 'rejected' => '2019-11-12T23:56:57.000Z'],
				YabbrSmsReceiptStatus::UNDELIVERED()
			],
			'unknown' => [
				['simulated' => '2019-11-12T23:56:57.000Z'],
				YabbrSmsReceiptStatus::UNKNOWN()
			]
		];
	}

	/**
	 * @dataProvider statusDataProvider
	 * @param array                 $receipts
	 * @param YabbrSmsReceiptStatus $expected
	 * @return void
	 */
	public function testGetStatusFromReceipts(array $receipts, YabbrSmsReceiptStatus $expected) {
		$this->assertSameEquals($expected, SmsFunctions::getStatusFromReceipts($receipts));
	}

	/**
	 * @return array
	 */
	public function statusDateTimeDataProvider() {
		return [
			'delivered' => [
				['simulated' => '2019-10-12T23:56:57.000Z', 'delivered' => '2019-10-12T23:56:57.000Z'],
				\DateTime::createFromFormat('Y-m-d\TH:i:s.uO', '2019-10-12T23:56:57.000Z')
			],
			'undelivered' => [
				['simulated' => '2019-10-10T23:56:57.000Z', 'undelivered' => '2019-10-10T20:56:57.000Z'],
				\DateTime::createFromFormat('Y-m-d\TH:i:s.uO', '2019-10-10T20:56:57.000Z')
			],
			'unknown' => [
				['simulated' => '2019-11-12T23:56:57.000Z'],
				null
			]
		];
	}

	/**
	 * @dataProvider statusDateTimeDataProvider
	 * @param array $receipts
	 * @param mixed $expected
	 * @return void
	 */
	public function testGetStatusUpdateDateTimeFromReceipts(array $receipts, $expected) {
		$this->assertEquals($expected, SmsFunctions::getStatusUpdateDateTimeFromReceipts($receipts));
	}
}
