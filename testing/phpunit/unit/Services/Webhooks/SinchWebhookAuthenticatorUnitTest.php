<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Phake;
use Services\Authenticators\Oauth2AccessTokenAuthenticator;
use Services\Http\Request;
use Services\Utils\SecurityZone;
use Services\Webhooks\SinchWebhookAuthenticator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SinchWebhookAuthenticatorUnitTest
 */
class SinchWebhookAuthenticatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var Oauth2AccessTokenAuthenticator | \Phake_IMock */
	private $authenticator;

	/** @var SecurityZone | \Phake_IMock */
	private $securityZone;

	/** @var Request | \Phake_IMock */
	private $request;

	/** @var SinchWebhookAuthenticator */
	private $webHookAuthenticator;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->authenticator = Phake::mock(Oauth2AccessTokenAuthenticator::class);
		$this->securityZone = SecurityZone::SINCH_INBOUND_SMS_SECURITY_ZONE();
		$this->request = Phake::mock(Request::class);
		$this->webHookAuthenticator = new SinchWebhookAuthenticator($this->authenticator, $this->securityZone);
	}

	/**
	 * @return void
	 */
	public function testGetSecurityZone() {
		$this->assertSameEquals($this->securityZone, $this->webHookAuthenticator->getSecurityZone());
	}

	/**
	 * @return void
	 */
	public function testAuthenticateFailsWhenAuthenticatorReturnsFalse() {
		Phake::when($this->authenticator)->authenticate($this->request)->thenReturn(false);
		$this->assertFalse($this->webHookAuthenticator->authenticate($this->request));
	}

	/**
	 * @return array
	 */
	public function userIdDataProvider() {
		return [
			'valid user' => [123, true],
			'invalid user' => [3434, false]
		];
	}

	/**
	 * @dataProvider userIdDataProvider
	 * @param integer $userId
	 * @param boolean $expected
	 * @return void
	 */
	public function testAuthenticate($userId, $expected) {
		Phake::when($this->authenticator)->authenticate($this->request)->thenReturn(true);
		Phake::when($this->authenticator)->getUserId()->thenReturn($userId);

		$this->mock_function_param_value(
			'api_security_check',
			[
				['params' => [$this->securityZone->getValue(), null, true, 123], 'return' => true],
			],
			false
		);

		$this->assertSameEquals($expected, $this->webHookAuthenticator->authenticate($this->request));
	}
}
