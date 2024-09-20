<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Services\Sms\SmsReceiptProcessor;
use Services\Utils\Webhooks\WebhookType;
use Services\Webhooks\Interfaces\QueueableWebhookInterface;
use Services\Webhooks\SinchInboundSmsHook;
use Services\Webhooks\SinchSmsReceiptHook;
use Services\Webhooks\WebhookFactory;
use Services\Webhooks\YabbrSmsReceiptHook;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WebhookFactoryUnitTest
 */
class WebhookFactoryUnitTest extends AbstractPhpunitUnitTest
{
	/** @var WebhookFactory */
	private $factory;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->factory = new WebhookFactory();
	}

	/**
	 * @return array
	 */
	public function webHookDataProvider() {
		return [
			'Yabbr sms receipt hook' => [
				WebhookType::YABBR_SMS_RECEIPT_HOOK(),
				YabbrSmsReceiptHook::class
			],
			'Sinch sms receipt hook' => [
				WebhookType::SINCH_SMS_RECEIPT_HOOK(),
				SinchSmsReceiptHook::class
			],
			'Sinch inbound sms hook' => [
				WebhookType::SINCH_INBOUND_SMS_HOOK(),
				SinchInboundSmsHook::class
			]
		];
	}

	/**
	 * @dataProvider webHookDataProvider
	 * @param WebhookType $type
	 * @param string      $expected
	 * @return void
	 */
	public function testGetWebhook(WebhookType $type, $expected) {
		$this->assertInstanceOf($expected, $this->factory->getWebhook($type));
	}
}
