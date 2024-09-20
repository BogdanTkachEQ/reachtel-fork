<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Sms;

use Models\SmsDeliveryReceipt;
use Phake;
use Services\Sms\SmsReceiptProcessor;
use Services\Utils\Sms\SinchSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsReceiptProcessorUnitTest
 */
class SmsReceiptProcessorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SmsReceiptProcessor */
	private $processor;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->processor = new SmsReceiptProcessor();
	}

	/**
	 * @return void
	 */
	public function testSaveReceipt() {
		$supplierId = 123;
		$smsId = 'rtege45345325q2fsdf';
		$status = SinchSmsReceiptStatus::EXPIRED();
		$code = 'error_code';
		$supplierDate = new \DateTime();
		$deliveryReceipt = Phake::mock(SmsDeliveryReceipt::class);
		Phake::when($deliveryReceipt)->getSupplierId()->thenReturn($supplierId);
		Phake::when($deliveryReceipt)->getSmsId()->thenReturn($smsId);
		Phake::when($deliveryReceipt)->getStatus()->thenReturn($status);
		Phake::when($deliveryReceipt)->getErrorCode()->thenReturn($code);
		Phake::when($deliveryReceipt)->getStatusUpdateDateTime()->thenReturn($supplierDate);

		$dr = [
			'supplier' => $supplierId,
			'supplieruid' => $smsId,
			'status' => $status->getValue(),
			'code' => $code,
			'supplierdate' => $supplierDate->getTimestamp()
		];
		$this->mock_function_param_value(
			'api_queue_add',
			[
				['params' => ['smsdr', $dr], 'return' => true]
			],
			false
		);
		$this->assertTrue($this->processor->saveReceipt($deliveryReceipt));
	}
}
