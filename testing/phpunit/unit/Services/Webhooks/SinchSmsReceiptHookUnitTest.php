<?php
/**
 * @author       rohith.mohan@equifax.com
 * @copyright    ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Models\SmsDeliveryReceipt;
use Phake;
use Phake_IMock;
use Services\Http\Request;
use Services\Sms\SmsReceiptProcessor;
use Services\Suppliers\SmsServiceFactory;
use Services\Utils\Sms\SinchSmsReceiptStatus;
use Services\Webhooks\SinchSmsReceiptHook;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SinchSmsReceiptHookUnitTest
 */
class SinchSmsReceiptHookUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SmsReceiptProcessor | Phake_IMock */
	private $processor;

	/** @var SinchSmsReceiptHook */
	private $hook;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->processor = Phake::mock(SmsReceiptProcessor::class);
		$this->hook = new SinchSmsReceiptHook($this->processor);
	}

	/**
	 * @return void
	 */
	public function testRunQueuedJob() {
		$attributes = [
			'status' => 'Delivered',
			'id' => 'fsdf45rdst43t3454',
			'date' => (new \DateTime())->getTimeStamp()
		];

		Phake::when($this->processor)->saveReceipt(Phake::capture($receipt))->thenReturn(true);
		$this->assertTrue($this->hook->runQueuedJob($attributes));
		$this->assertInstanceOf(SmsDeliveryReceipt::class, $receipt);
		$this->assertSameEquals($attributes['id'], $receipt->getSmsId());
		$this->assertSameEquals(SinchSmsReceiptStatus::DELIVERED(), $receipt->getStatus());
		$this->assertSameEquals(SmsServiceFactory::SMS_SUPPLIER_SINCH_ID, $receipt->getSupplierId());
		$this->assertSameEquals(
			$attributes['date'],
			$receipt->getStatusUpdateDateTime()->getTimestamp()
		);
	}

	/**
	 * @expectedException Services\Exceptions\Webhooks\WebhookException
	 * @expectedExceptionMessage Missing batch id in sinch sms receipt
	 * @return void
	 */
	public function testGetHookAttributesForQueueingThrowsExceptionForMissingBatchId() {
		$request = Phake::mock(Request::class);
		Phake::when($request)->getContent()->thenReturn(json_encode(['statuses' => ['status' => []]]));
		$this->hook->getHookAttributesForQueueing($request);
	}

	/**
	 * @expectedException Services\Exceptions\Webhooks\WebhookException
	 * @expectedExceptionMessage Missing statuses in sinch sms receipt
	 * @return void
	 */
	public function testGetHookAttributesForQueueingThrowsExceptionForMissingStatuses() {
		$request = Phake::mock(Request::class);
		Phake::when($request)->getContent()->thenReturn(json_encode(['batch_id' => 2123231]));
		$this->hook->getHookAttributesForQueueing($request);
	}

	/**
	 * @return void
	 */
	public function testGetHookAttributesForQueueing() {
		$content = [
			'type' => 'delivery_report_sms',
			'batch_id' => 123123,
			'total_message_count' => 1,
			'statuses' => [
				[
					'code' => 400,
					'status' => 'delivered',
					'count' => 1
				]
			],
		];

		$request = Phake::mock(Request::class);
		Phake::when($request)->getContent()->thenReturn(json_encode($content));
		$return = $this->hook->getHookAttributesForQueueing($request);

		$this->assertSameEquals($content['batch_id'], $return['id']);
		$this->assertSameEquals('delivered', $return['status']);
		$this->assertArrayHasKey('date', $return);
	}
}
