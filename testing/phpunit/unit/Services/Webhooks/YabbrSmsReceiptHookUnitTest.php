<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Models\SmsDeliveryReceipt;
use Phake;
use Phake_IMock;
use Services\Http\Request;
use Services\Sms\SmsReceiptProcessor;
use Services\Utils\Sms\YabbrSmsReceiptStatus;
use Services\Webhooks\YabbrSmsReceiptHook;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class YabbrSmsReceiptHookUnitTest
 */
class YabbrSmsReceiptHookUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SmsReceiptProcessor | Phake_IMock */
	private $receiptProcessor;

	/** @var YabbrSmsReceiptHook */
	private $hook;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->receiptProcessor = Phake::mock(SmsReceiptProcessor::class);
		$this->hook = new YabbrSmsReceiptHook($this->receiptProcessor);
	}

	/**
	 * @return void
	 */
	public function testRunQueuedJob() {
		$attributes = [
			[
				'receipts' => ['delivered' => '2019-10-10T23:56:57.000Z'],
				'id' => 123,
			],
			[
				'receipts' => ['undelivered' => '2019-10-11T12:48:50.001Z'],
				'id' => 456,
			],
		];

		Phake::when($this->receiptProcessor)->saveReceipt(Phake::captureAll($receipts))->thenReturn(true);
		$this->assertTrue($this->hook->runQueuedJob($attributes));
		$this->assertCount(2, $receipts);

		$this->assertInstanceOf(SmsDeliveryReceipt::class, $receipts[0]);
		$this->assertSameEquals($attributes[0]['id'], $receipts[0]->getSmsId());
		$this->assertSameEquals(22, $receipts[0]->getSupplierId());
		$this->assertSameEquals(YabbrSmsReceiptStatus::translate('delivered'), $receipts[0]->getStatus());
		$this->assertEquals(
			'2019-10-10 23:56:57',
			$receipts[0]->getStatusUpdateDateTime()->format('Y-m-d H:i:s')
		);

		$this->assertInstanceOf(SmsDeliveryReceipt::class, $receipts[1]);
		$this->assertSameEquals($attributes[1]['id'], $receipts[1]->getSmsId());
		$this->assertSameEquals(22, $receipts[1]->getSupplierId());
		$this->assertSameEquals(YabbrSmsReceiptStatus::translate('undelivered'), $receipts[1]->getStatus());
		$this->assertSameEquals(
			'2019-10-11 12:48:50',
			$receipts[1]->getStatusUpdateDateTime()->format('Y-m-d H:i:s')
		);
	}

	/**
	 * @expectedException Services\Exceptions\Webhooks\WebhookException
	 * @expectedExceptionMessage Missing messages in yabbr sms receipt
	 * @return void
	 */
	public function testGetHookAttributesForQueueingThrowsException() {
		$request = Phake::mock(Request::class);
		$content = ['no_messages' => []];
		Phake::when($request)->getContent()->thenReturn(json_encode($content));
		$this->hook->getHookAttributesForQueueing($request);
	}

	/**
	 * @return void
	 */
	public function testGetHookAttributesForQueueing() {
		$request = Phake::mock(Request::class);
		$content = [
			'nextPageUri' => null,
			'previousPageUri' => null,
			'status' => 'OK',
			'messages' => [
				[
					'to' => '61400000003',
					'content' => 'Hello, World!',
					'from' => 'Yabbr',
					'uri' => '/2019-01-23/messages/3ca557e8c5d196097e7d008165dac01c',
					'type' => 'sms',
					'id' => '3ca557e8c5d196097e7d008165dac01c',
					'receipts' => [
						'undelivered' => '2019-01-23T10:26:57.000Z',
						'simulated' => '2019-01-23T10:26:57.000Z',
					],
					'created' => '2019-01-23T10:26:57.000Z',
				],
				[
					'created' => '2019-01-23T10:15:09.000Z',
					'receipts' => [
						'simulated' => '2019-01-23T10:15:09.000Z',
						'rejected' => '2019-01-23T10:15:09.000Z',
					],
					'from' => 'Yabbr',
					'content' => 'Hello, World!',
					'to' => '61400000002',
					'id' => '1da0bdeaf5229f895a558c3b0be13128',
					'type' => 'sms',
					'uri' => '/2019-01-23/messages/1da0bdeaf5229f895a558c3b0be13128',
				],
			],
		];

		Phake::when($request)->getContent()->thenReturn(json_encode($content));
		$expected = [
			[
				'id' => $content['messages'][0]['id'],
				'receipts' => $content['messages'][0]['receipts']
			],
			[
				'id' => $content['messages'][1]['id'],
				'receipts' => $content['messages'][1]['receipts']
			]
		];

		$actual = $this->hook->getHookAttributesForQueueing($request);

		$this->assertSameEquals($expected, $actual);
	}
}
