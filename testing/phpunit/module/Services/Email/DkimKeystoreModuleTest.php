<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services;

use Services\Email\Dkim\DkimKeyFactory;
use Services\Email\Dkim\GroupDkimKeystore;
use Services\Email\Dkim\RSADkimKeyGenerator;
use Services\Email\Dkim\SystemDkimKeystore;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\UserModuleHelper;

/**
 * Class DkimKeystoreModuleTest
 */
class DkimKeystoreModuleTest extends AbstractDatabasePhpunitModuleTest
{
	use UserModuleHelper;

	public $groupId1;
	public $groupId2;

	/**
	 * @return void
	 */
	public function test_group_key_save() {

		$this->mock_function_param_value(
			'api_groups_checkidexists',
			[
				['params' => [1000], 'return' => true],
			],
			false
		);

		$keyStore = new GroupDkimKeystore(1000, "test55", new DkimKeyFactory());
		$this->assertTrue($keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));
		$keyStore->deleteKey();
	}

	/**
	 * @return void
	 */
	public function test_system_key_save() {
		$keyStore = new SystemDkimKeystore("testsystem500", new DkimKeyFactory());
		$this->assertTrue($keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));
		$keyStore->deleteKey();
	}

	/**
	 * @return void
	 */
	public function test_system_key_save_must_fail_on_duplicate() {
		$keyStore = new SystemDkimKeystore("testsystem500", new DkimKeyFactory());
		$this->assertTrue($keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));
		$keyStore = new SystemDkimKeystore("testsystem500", new DkimKeyFactory());
		$this->expectException(\Services\Exceptions\Email\DkimException::class);
		$this->assertTrue($keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));
		$keyStore->deleteKey();
	}

	/**
	 * @return void
	 */
	public function test_key_delete() {
		$this->mock_function_param_value(
			'api_groups_checkidexists',
			[
				['params' => [1000], 'return' => true],
			],
			false
		);
		$keyStore = new GroupDkimKeystore(1000, "test100", new DkimKeyFactory());
		$keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory()));
		$this->assertTrue($keyStore->deleteKey());
	}

	/**
	 * @return void
	 */
	public function test_group_system_key_add() {
		$this->mock_function_param_value(
			'api_groups_checkidexists',
			[
				['params' => [1000], 'return' => true],
			],
			false
		);

		$systemKeyStore = new SystemDkimKeystore("testsystemgroupkey", new DkimKeyFactory());
		$this->assertTrue($systemKeyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));

		$keyStore = new GroupDkimKeystore(1000, "testselector", new DkimKeyFactory());
		$keyStore->setSystemKey("testsystemgroupkey");
		$this->assertEquals("testsystemgroupkey", api_groups_setting_getsingle(1000, GroupDkimKeystore::SYSTEM_KEY_ITEM));
		$this->assertEquals("testselector", api_groups_setting_getsingle(1000, GroupDkimKeystore::SYSTEM_SELECTOR_KEY_ITEM));

		$this->assertEquals("testselector", $keyStore->getSystemSelector());

		$systemKey = $keyStore->getSystemKey();
		$this->assertEquals("testsystemgroupkey", $systemKey[0]['selector']);

		$keyStore->removeSystemKey();
		$systemKeyStore->deleteKey();
	}

	/**
	 * @return void
	 */
	public function test_system_key_delete() {
		$keyStore = new SystemDkimKeystore("test55", new DkimKeyFactory());
		$this->assertTrue($keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));
		$this->assertTrue($keyStore->deleteKey());
	}

	/**
	 * @return void
	 */
	public function test_system_key_delete_must_fail_on_group_uses() {
		$this->mock_function_param_value(
			'api_groups_checkidexists',
			[
				['params' => [1000], 'return' => true],
			],
			false
		);
		$keyStore = new SystemDkimKeystore("test55", new DkimKeyFactory());
		$this->assertTrue($keyStore->saveKey((new RSADkimKeyGenerator())->createKey(new DkimKeyFactory())));

		$groupKeyStore = new GroupDkimKeystore(1000, "testselector", new DkimKeyFactory());
		$groupKeyStore->setSystemKey("test55");

		$this->expectException(\Services\Exceptions\Email\DkimException::class);
		$keyStore->deleteKey();
	}
}
