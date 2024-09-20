<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Authenticators;

use Google\Authenticator\GoogleAuthenticator;
use Phake;
use Phake_IMock;
use Services\Authenticators\GoogleAuthUserService;
use Services\Authenticators\GoogleMultiFactorAuthenticator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GoogleMultiFactorAuthenticatorUnitTest
 */
class GoogleMultiFactorAuthenticatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var GoogleAuthenticator | Phake_IMock */
	private $googleAuthenticator;

	/** @var GoogleAuthUserService | Phake_IMock */
	private $googleAuthUserService;

	/** @var GoogleMultiFactorAuthenticator */
	private $googleMultiFactorAuthenticator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->googleAuthenticator = Phake::mock(GoogleAuthenticator::class);
		$this->googleAuthUserService = Phake::mock(GoogleAuthUserService::class);
		$this->googleMultiFactorAuthenticator = new GoogleMultiFactorAuthenticator($this->googleAuthenticator, $this->googleAuthUserService);
	}

	/**
	 * @return void
	 */
	public function testCheckCodeReturnsFalseIfAuthNotEnabledForUser() {
		Phake::when($this->googleAuthUserService)->getSecret(Phake::anyParameters())->thenReturn(null);
		$this->assertFalse($this->googleMultiFactorAuthenticator->checkCode(123, 132312));
	}

	/**
	 * @return array
	 */
	public function checkCodeDataProvider() {
		return [
			'when check code is successful' => [143434345, false, false],
			'when check code is unsuccessful' => [32434345, true, true]
		];
	}

	/**
	 * @dataProvider checkCodeDataProvider
	 * @param integer $code
	 * @param boolean $checkCodeReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testCheckCode($code, $checkCodeReturn, $expected) {
		$userId = 123;
		$secret = 'SSGET3454DFD';
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn($secret);
		Phake::when($this->googleAuthenticator)->checkCode($secret, $code)->thenReturn($checkCodeReturn);
		$this->assertSameEquals($expected, $this->googleMultiFactorAuthenticator->checkCode($userId, $code));
	}

	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Google auth is not enabled for the user
	 * @return void
	 */
	public function testCreateQRThrowsExceptionIfGoogleAuthNotEnabledForUser() {
		Phake::when($this->googleAuthUserService)->getSecret(Phake::anyParameters())->thenReturn(null);
		$this->googleMultiFactorAuthenticator->createQR(234);
	}

	/**
	 * @return void
	 */
	public function testCreateQR() {
		$userId = 234;
		$secret = 'SSGET3454DFD';
		$userName = 'test-user';
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn($secret);
		Phake::when($this->googleAuthUserService)->getUserName($userId)->thenReturn($userName);
		$hostName = defined('APP_HOST_NAME') ? APP_HOST_NAME : 'morpheus.reachtel.com.au';
		$url = 'https://chart.google.com/xyz';
		Phake::when($this->googleAuthenticator)->getUrl($userName, $hostName, $secret)->thenReturn($url);
		$return = $this->googleMultiFactorAuthenticator->createQR($userId);
		$this->assertSameEquals($url, $return);
	}

	/**
	 * @return void
	 */
	public function testIsGoogleAuthEnabledForUser() {
		$userId = 3434;
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn(null);
		$this->assertFalse($this->googleMultiFactorAuthenticator->isGoogleAuthEnabledForUser($userId));
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn('ASFDGGDFSD');
		$this->assertTrue($this->googleMultiFactorAuthenticator->isGoogleAuthEnabledForUser($userId));
	}

	/**
	 * @return void
	 */
	public function testDisableGoogleAuthForUserReturnsTrueWhenAuthAlreadyDisabled() {
		$userId = 343;
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn(null);
		$this->assertTrue($this->googleMultiFactorAuthenticator->disableGoogleAuthForUser($userId));
		Phake::verify($this->googleAuthUserService, Phake::times(0))->removeSecret($userId);
	}

	/**
	 * @return array
	 */
	public function disableGoogleAuthForUserDataProvider() {
		return [
			'remove secret returns true' => [true, true],
			'remove secret fails' => [false, false]
		];
	}

	/**
	 * @dataProvider disableGoogleAuthForUserDataProvider
	 * @param boolean $removeSecretReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testDisableGoogleAuthForUser($removeSecretReturn, $expected) {
		$userId = 343;
		$secret = 'ADSFSDGSFD';
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn($secret);
		Phake::when($this->googleAuthUserService)->removeSecret($userId)->thenReturn($removeSecretReturn);
		$this->assertSameEquals($expected, $this->googleMultiFactorAuthenticator->disableGoogleAuthForUser($userId));
		Phake::verify($this->googleAuthUserService, Phake::times(1))->removeSecret($userId);
	}

	/**
	 * @return void
	 */
	public function testEnableGoogleAuthForUserReturnsTrueWhenAuthAlreadyEnabled() {
		$userId = 343;
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn('ADFSDFDS');
		$this->assertTrue($this->googleMultiFactorAuthenticator->enableGoogleAuthForUser($userId));
		Phake::verify($this->googleAuthUserService, Phake::times(0))->saveSecret(Phake::anyParameters());
	}

	/**
	 * @return array
	 */
	public function enableGoogleAuthForUserDataProvider() {
		return [
			'save secret returns true' => [true, true],
			'save secret fails' => [false, false]
		];
	}

	/**
	 * @dataProvider enableGoogleAuthForUserDataProvider
	 * @param boolean $saveSecretReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testEnableGoogleAuthForUser($saveSecretReturn, $expected) {
		$userId = 343;
		$secret = 'ADSFSDGSFD';
		Phake::when($this->googleAuthUserService)->getSecret($userId)->thenReturn(null);
		Phake::when($this->googleAuthenticator)->generateSecret()->thenReturn($secret);
		Phake::when($this->googleAuthUserService)->saveSecret($secret, $userId)->thenReturn($saveSecretReturn);
		$this->assertSameEquals($expected, $this->googleMultiFactorAuthenticator->enableGoogleAuthForUser($userId));
		Phake::verify($this->googleAuthUserService, Phake::times(1))->saveSecret($secret, $userId);
	}
}
