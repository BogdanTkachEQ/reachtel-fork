<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Yabbr;

use Models\Rest\RequestEnvelop;
use Models\Sms;
use Phake;
use Services\Rest\Adapter\MorpheusResponseAdapter;
use Services\Suppliers\Yabbr\Client\RestClient;
use Services\Suppliers\Yabbr\SmsService;
use Services\Utils\Sms\YabbrSmsReceiptStatus;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsServiceUnitTest
 */
class SmsServiceUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @var SmsService
	 */
	private $smsService;

	/**
	 * @var RestClient | \Phake_IMock
	 */
	private $client;

	/** @var boolean */
	private $isSimulated;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->client = Phake::mock(RestClient::class);
		$this->isSimulated = true;

		$this->smsService = new SmsService($this->client, $this->isSimulated);
	}

	/**
	 * @return void
	 */
	public function testSendSms() {
		$sms = Phake::mock(Sms::class);
		$from = '6423456789';
		$to = '61448214578';
		$content = 'Test Content';

		Phake::when($sms)->getTo()->thenReturn($to);
		Phake::when($sms)->getFrom()->thenReturn($from);
		Phake::when($sms)->getContent()->thenReturn($content);

		$response = Phake::mock(MorpheusResponseAdapter::class);
		$messageId = '453453fdsfaf435345tggfg';
		$body = [
			'messages' => [
				['id' => $messageId]
			]
		];
		Phake::when($response)->getStatusCode()->thenReturn(200);
		Phake::when($response)->getBody()->thenReturn(json_encode($body));

		Phake::when($this->client)->send(Phake::capture($envelop))->thenReturn($response);

		$return = $this->smsService->sendSms($sms, 123);

		$this->assertInstanceOf(RequestEnvelop::class, $envelop);
		$this->assertSameEquals(1, $envelop->getAction());
		$this->assertSameEquals(
			[
				'to' => $to,
				'from' => $from,
				'content' => $content,
				'type' => 'sms',
				'simulated' => $this->isSimulated
			],
			$envelop->getBody()
		);

		$this->assertSameEquals($messageId, $return);
	}

	/**
	 * @expectedException Services\Exceptions\Suppliers\SmsServiceException
	 * @expectedExceptionMessage Sms send failed. Response: Bad request
	 * @return void
	 */
	public function testSendSmsThrowsException() {
		$response = Phake::mock(MorpheusResponseAdapter::class);
		Phake::when($response)->getStatusCode(400);
		Phake::when($response)->getBody()->thenReturn('Bad request');
		Phake::when($this->client)->send(Phake::anyParameters())->thenReturn($response);
		$this->smsService->sendSms(new Sms(), 12312);
	}

	/**
	 * @return array
	 */
	public function retrieveSmsDataProvider() {
		return [
			'when message delivered' => [
				[
					'from' => '61412345678',
					'to' => '64214568523',
					'content' => 'Test message',
					'receipts' => [
						'delivered' => '2019-10-20T14:02:06.000Z'
					],
				],
				1234,
				YabbrSmsReceiptStatus::DELIVERED()
			],
			'when message is not delivered' => [
				[
					'from' => '61412345678',
					'to' => '64214568523',
					'content' => 'Test message',
					'receipts' => [],
				],
				1235,
				YabbrSmsReceiptStatus::UNKNOWN()
			]
		];
	}

	/**
	 * @dataProvider retrieveSmsDataProvider
	 * @param array                 $message
	 * @param integer               $messageId
	 * @param YabbrSmsReceiptStatus $expectedStatus
	 * @return void
	 */
	public function testRetrieveSms(array $message, $messageId, YabbrSmsReceiptStatus $expectedStatus) {
		$response = Phake::mock(MorpheusResponseAdapter::class);
		$body = [
			'messages' => [$message]
		];

		Phake::when($response)->getStatusCode()->thenReturn(200);
		Phake::when($response)->getBody()->thenReturn(json_encode($body));
		Phake::when($this->client)->send(Phake::capture($envelop))->thenReturn($response);

		$return = $this->smsService->retrieveSms($messageId);
		$this->assertInstanceOf(RequestEnvelop::class, $envelop);
		$this->assertSameEquals(2, $envelop->getAction());
		$this->assertSameEquals(['id' => $messageId], $envelop->getBody());

		$this->assertInstanceOf(Sms::class, $return);
		$this->assertSameEquals($message['from'], $return->getFrom());
		$this->assertSameEquals($message['to'], $return->getTo());
		$this->assertSameEquals($message['content'], $return->getContent());
		$this->assertSameEquals($expectedStatus, $return->getStatus());

		if ($expectedStatus === YabbrSmsReceiptStatus::DELIVERED()) {
			$this->assertInstanceOf(\DateTime::class, $return->getStatusUpdateTime());
			$this->assertSameEquals(
				$message['receipts']['delivered'],
				$return->getStatusUpdateTime()->format('Y-m-d\TH:i:s.000\Z')
			);
		} else {
			$this->assertNull($return->getStatusUpdateTime());
		}

		$this->assertSameEquals($messageId, $return->getId());
	}

	/**
	 * @expectedException Services\Exceptions\Suppliers\SmsServiceException
	 * @expectedExceptionMessage Sms retrieve failure. Response: Bad request
	 * @return void
	 */
	public function testRetrieveSmsThrowsException() {
		$response = Phake::mock(MorpheusResponseAdapter::class);
		Phake::when($response)->getStatusCode()->thenReturn(400);
		$body = 'Bad request';
		Phake::when($response)->getBody()->thenReturn($body);
		Phake::when($this->client)->send(Phake::anyParameters())->thenReturn($response);

		$this->smsService->retrieveSms(123465);
	}
}
