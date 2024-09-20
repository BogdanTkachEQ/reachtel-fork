<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Services\Utils\SecurityZone;
use Services\Utils\Webhooks\WebhookType;
use Services\Webhooks\SinchWebhookAuthenticator;
use Services\Webhooks\WebhookAuthenticatorFactory;
use Services\Webhooks\YabbrSmsReceiptAuthenticator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WebhookAuthenticatorFactoryUnitTest
 */
class WebhookAuthenticatorFactoryUnitTest extends AbstractPhpunitUnitTest
{
	/** @var WebhookAuthenticatorFactory */
	private $factory;

	/**
	 * @return void
	 */
	public function setUp() {
		if (!defined('YABBR_SMS_RECEIPT_USER')) {
			define('YABBR_SMS_RECEIPT_USER', 'yabbruser');
		}

		if (!defined('YABBR_SMS_RECEIPT_PWD')) {
			define('YABBR_SMS_RECEIPT_PWD', 'yabbrpwd');
		}
		$this->factory = new WebhookAuthenticatorFactory();
	}

	/**
	 * @return array
	 */
	public function authenticatorDataProvider() {
		return [
			'Yabbr sms receipt hook' => [
				WebhookType::YABBR_SMS_RECEIPT_HOOK(),
				YabbrSmsReceiptAuthenticator::class
			],
			'Sinch sms receipt hook' => [
				WebhookType::SINCH_SMS_RECEIPT_HOOK(),
				SinchWebhookAuthenticator::class,
				SecurityZone::SINCH_SMS_DR_SECURITY_ZONE()
			],
			'Sinch inbound sms hook' => [
				WebhookType::SINCH_INBOUND_SMS_HOOK(),
				SinchWebhookAuthenticator::class,
				SecurityZone::SINCH_INBOUND_SMS_SECURITY_ZONE()
			]
		];
	}

	/**
	 * @dataProvider authenticatorDataProvider
	 * @param WebhookType       $type
	 * @param string            $expected
	 * @param SecurityZone|null $securityZone
	 * @return void
	 */
	public function testGetAuthenticator(WebhookType $type, $expected, SecurityZone $securityZone = null) {
		$authenticator = $this->factory->getAuthenticator($type);
		$this->assertInstanceOf($expected, $authenticator);

		if ($securityZone) {
			$this->assertSameEquals($securityZone, $authenticator->getSecurityZone());
		}
	}
}
