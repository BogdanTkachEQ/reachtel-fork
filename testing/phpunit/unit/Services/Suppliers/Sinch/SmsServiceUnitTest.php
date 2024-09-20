<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Suppliers\Sinch;

use Models\Rest\RequestEnvelop;
use Models\Sms;
use Phake;
use Services\Rest\Adapter\MorpheusResponseAdapter;
use Services\Suppliers\Sinch\Client\RestClient;
use Services\Suppliers\Sinch\SmsService;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SmsServiceUnitTest
 */
class SmsServiceUnitTest extends AbstractPhpunitUnitTest
{
	/** @var RestClient | \Phake_IMock */
	private $client;

	/** @var SmsService */
	private $service;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->client = Phake::mock(RestClient::class);
		$this->service = new SmsService($this->client);
	}

	/**
	 * @expectedException Services\Exceptions\Suppliers\SmsServiceException
	 * @expectedExceptionMessage Sms send failed. Response: Bad request
	 * @return void
	 */
	public function testSendSmsThrowsExceptions() {
		$response = Phake::mock(MorpheusResponseAdapter::class);
		Phake::when($response)->getStatusCode()->thenReturn(400);
		Phake::when($response)->getBody()->thenReturn('Bad request');
		Phake::when($this->client)->send(Phake::anyParameters())->thenReturn($response);

		$this->service->sendSms(new Sms(), 1234);
	}

	/**
	 * @return void
	 */
	public function testSendSms() {
		$response = Phake::mock(MorpheusResponseAdapter::class);
		$smsId = 'new-sms-id';
		Phake::when($response)->getStatusCode()->thenReturn(201);
		Phake::when($response)->getBody()->thenReturn(json_encode(['id' => $smsId]));
		Phake::when($this->client)->send(Phake::capture($envelop))->thenReturn($response);

		$sms = Phake::mock(Sms::class);
		$from = '614578956256';
		$to = '64214578963';
		$content = 'Test message body';
		$eventId = 123123;

		Phake::when($sms)->getFrom()->thenReturn($from);
		Phake::when($sms)->getTo()->thenReturn($to);
		Phake::when($sms)->getContent()->thenReturn($content);
		$return = $this->service->sendSms($sms, $eventId);

		$this->assertSameEquals($smsId, $return);
		$this->assertInstanceOf(RequestEnvelop::class, $envelop);
		$this->assertSameEquals(1, $envelop->getAction());
		$body = [
			'from' => $from,
			'to' => [$to],
			'body' => $content,
			'delivery_report' => 'full'
		];

		if (defined('SINCH_DELIVERY_REPORT_OVERRIDE_URL') && SINCH_DELIVERY_REPORT_OVERRIDE_URL) {
			$body['callback_url'] = SINCH_DELIVERY_REPORT_OVERRIDE_URL;
		}

		$this->assertSameEquals($body, $envelop->getBody());
	}
}
