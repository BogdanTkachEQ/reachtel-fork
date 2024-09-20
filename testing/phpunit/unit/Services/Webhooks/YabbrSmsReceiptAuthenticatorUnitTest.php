<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Webhooks;

use Phake;
use Services\Authenticators\SystemUserBasicHttpRequestAuthenticator;
use Services\Exceptions\Authenticators\AuthenticatorException;
use Services\Http\Request;
use Services\Webhooks\YabbrSmsReceiptAuthenticator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class YabbrSmsReceiptAuthenticatorUnitTest
 */
class YabbrSmsReceiptAuthenticatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SystemUserBasicHttpRequestAuthenticator | \Phake_IMock */
	private $basicHttpAuthenticator;

	/** @var YabbrSmsReceiptAuthenticator */
	private $authenticator;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->basicHttpAuthenticator = Phake::mock(SystemUserBasicHttpRequestAuthenticator::class);
		$this->authenticator = new YabbrSmsReceiptAuthenticator($this->basicHttpAuthenticator);
	}

	/**
	 * @expectedException Services\Exceptions\Webhooks\WebhookAuthenticationException
	 * @expectedExceptionMessage Something went wrong
	 * @return void
	 */
	public function testAuthenticateThrowsException() {
		$request = Phake::mock(Request::class);
		Phake::when($this->basicHttpAuthenticator)
			->authenticate($request)
			->thenThrow(new AuthenticatorException('Something went wrong'));

		$this->authenticator->authenticate($request);
	}

	/**
	 * @return array
	 */
	public function authenticateDataProvider() {
		return [
			'successful' => [true, true],
			'failed' => [false, false]
		];
	}

	/**
	 * @dataProvider authenticateDataProvider
	 * @param boolean $return
	 * @param boolean $expected
	 * @return void
	 */
	public function testAuthenticate($return, $expected) {
		$request = Phake::mock(Request::class);
		Phake::when($this->basicHttpAuthenticator)
			->authenticate($request)
			->thenReturn($return);

		$this->assertSameEquals($expected, $return);
	}
}
