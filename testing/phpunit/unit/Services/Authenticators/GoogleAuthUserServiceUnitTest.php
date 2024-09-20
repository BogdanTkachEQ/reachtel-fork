<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Authenticators;

use Services\Authenticators\GoogleAuthUserService;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GoogleAuthUserServiceUnitTest
 */
class GoogleAuthUserServiceUnitTest extends AbstractPhpunitUnitTest
{
	/** @var GoogleAuthUserService */
	private $googleAuthUserService;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->googleAuthUserService = new GoogleAuthUserService();
	}

	/**
	 * @return array
	 */
	public function getSecretDataProvider() {
		return [
			'when fetching from user settings returns false' => [false, null],
			'when fetching from user settings returns a value' => ['ADSFFDDF', 'ADSFFDDF']
		];
	}

	/**
	 * @dataProvider getSecretDataProvider
	 * @param mixed  $settingsReturn
	 * @param string $expected
	 * @return void
	 */
	public function testGetSecret($settingsReturn, $expected) {
		$userId = 4545;
		$this->mock_function_param_value(
			'api_users_setting_getsingle',
			[
				['params' => [$userId, 'googleauthsecret'], 'return' => $settingsReturn]
			],
			'test'
		);

		$this->assertSameEquals($expected, $this->googleAuthUserService->getSecret($userId));
	}

	/**
	 * @return array
	 */
	public function removeSecretDataProvider() {
		return [
			'when removing from user settings returns false' => [false, false],
			'when removing from user settings returns true' => [true, true]
		];
	}

	/**
	 * @dataProvider removeSecretDataProvider
	 * @param boolean $settingsReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testRemoveSecret($settingsReturn, $expected) {
		$userId = 4545;
		$this->mock_function_param_value(
			'api_users_setting_delete_single',
			[
				['params' => [$userId, 'googleauthsecret'], 'return' => $settingsReturn]
			],
			false
		);

		$this->assertSameEquals($expected, $this->googleAuthUserService->removeSecret($userId));
	}

	/**
	 * @return array
	 */
	public function saveSecretDataProvider() {
		return [
			'when adding to user settings returns false' => [false, false],
			'when adding to user settings returns true' => [true, true]
		];
	}

	/**
	 * @dataProvider saveSecretDataProvider
	 * @param boolean $settingsReturn
	 * @param boolean $expected
	 * @return void
	 */
	public function testSaveSecret($settingsReturn, $expected) {
		$userId = 4545;
		$secret = 'ADFASDFDS';
		$this->mock_function_param_value(
			'api_users_setting_set',
			[
				['params' => [$userId, 'googleauthsecret', $secret], 'return' => $settingsReturn]
			],
			false
		);

		$this->assertSameEquals($expected, $this->googleAuthUserService->saveSecret($secret, $userId));
	}

	/**
	 * @return void
	 */
	public function testGetUserName() {
		$userId = 4454;
		$userName = 'test-user-name';
		$this->mock_function_param_value(
			'api_users_setting_getsingle',
			[
				['params' => [$userId, 'username'], 'return' => $userName]
			],
			'test'
		);

		$this->assertSameEquals($userName, $this->googleAuthUserService->getUserName($userId));
	}
}
