<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Phake;
use Phake_IMock;
use Services\Http\Request;
use Services\Utils\Webhooks\WebhookType;
use Services\Webhooks\Interfaces\QueueableWebhookInterface;
use Services\Webhooks\Interfaces\WebhookAuthenticatorInterface;
use Services\Webhooks\WebhookAuthenticatorFactory;
use Services\Webhooks\WebhookFactory;
use Services\Webhooks\WebhookProcessor;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WebhookProcessorUnitTest
 */
class WebhookProcessorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var WebhookAuthenticatorFactory | Phake_IMock */
	private $authFactory;

	/** @var WebhookProcessor */
	private $processor;

	/** @var WebhookFactory | Phake_IMock */
	private $webhookFactory;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->webhookFactory = Phake::mock(WebhookFactory::class);
		$this->authFactory = Phake::mock(WebhookAuthenticatorFactory::class);
		$this->processor = new WebhookProcessor($this->webhookFactory);
	}

	/**
	 * @expectedException Services\Exceptions\Webhooks\InvalidWebhookException
	 * @expectedExceptionMessage Invalid webhook name passed
	 * @return void
	 */
	public function testProcessRequestthrowsInvalidHookException() {
		$request = Phake::mock(Request::class);
		Phake::when($request)->get('name')->thenReturn('random_hook');
		$this->processor->processRequest($request, $this->authFactory);
	}

	/**
	 * @expectedException Services\Exceptions\Webhooks\WebhookAuthenticationException
	 * @expectedExceptionMessage Authentication failed
	 * @return void
	 */
	public function testProcessRequestThrowsAuthException() {
		$request = Phake::mock(Request::class);
		$authenticator = Phake::mock(WebhookAuthenticatorInterface::class);
		Phake::when($authenticator)->authenticate($request)->thenReturn(false);
		Phake::when($request)->get('name')->thenReturn(WebhookType::YABBR_SMS_RECEIPT_HOOK);
		Phake::when($this->authFactory)
			->getAuthenticator(WebhookType::byValue(WebhookType::YABBR_SMS_RECEIPT_HOOK))
			->thenReturn($authenticator);

		$this->processor->processRequest($request, $this->authFactory);
	}

	/**
	 * @return void
	 */
	public function testProcessRequest() {
		$request = Phake::mock(Request::class);
		$authenticator = Phake::mock(WebhookAuthenticatorInterface::class);
		Phake::when($authenticator)->authenticate($request)->thenReturn(true);
		Phake::when($request)->get('name')->thenReturn(WebhookType::YABBR_SMS_RECEIPT_HOOK);
		Phake::when($this->authFactory)
			->getAuthenticator(WebhookType::byValue(WebhookType::YABBR_SMS_RECEIPT_HOOK))
			->thenReturn($authenticator);

		$webhook = Phake::mock(QueueableWebhookInterface::class);
		$attributes = [
			'key1' => 'value1',
			'key2' => 'value2'
		];

		Phake::when($webhook)->getHookAttributesForQueueing($request)->thenReturn($attributes);

		Phake::when($this->webhookFactory)
			->getWebhook(WebhookType::byValue(WebhookType::YABBR_SMS_RECEIPT_HOOK))
			->thenReturn($webhook);

		$this->mock_function_param_value(
			'api_queue_add',
			[
				[
					'params' => [
						'webhook',
						[
							'name' => WebhookType::YABBR_SMS_RECEIPT_HOOK,
							'hook_attr' => $attributes
						]
					],
					'return' => true
				]
			],
			false
		);

		$this->assertTrue($this->processor->processRequest($request, $this->authFactory));
	}

	/**
	 * @return void
	 */
	public function testProcessQueuedJob() {
		$webhook = Phake::mock(QueueableWebhookInterface::class);
		Phake::when($webhook)->runQueuedJob(Phake::capture($actualAttributes))->thenReturn(true);
		Phake::when($this->webhookFactory)
			->getWebhook(WebhookType::byValue(WebhookType::SINCH_INBOUND_SMS_HOOK))
			->thenReturn($webhook);

		$attributes = [
			'key1' => 'value1',
			'key2' => 'value2',
			'key3' => 'value3',
		];

		$this->assertTrue(
			$this
				->processor
				->processQueuedJob(
					[
						'name' => WebhookType::SINCH_INBOUND_SMS_HOOK,
						'hook_attr' => $attributes
					],
					WebhookType::SINCH_INBOUND_SMS_HOOK
				)
		);
		$this->assertSameEquals($attributes, $actualAttributes);
	}
}
