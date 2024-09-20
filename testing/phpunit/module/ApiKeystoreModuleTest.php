<?php
/**
 * ApiKeystoreModuleTest
 * Module test for api_keystore.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\UserModuleHelper;

/**
 * Api Key Store Module Test
 */
class ApiKeystoreModuleTest extends AbstractPhpunitModuleTest
{
	use UserModuleHelper;

	/**
	 * @group api_keystore_get
	 * @return void
	 */
	public function test_api_keystore_get() {
		$username = uniqid('test');
		$user_id = $this->create_new_user($username);
		$user_type = self::get_user_type();

		$this->assertFalse(api_keystore_get('type.doesnt.exists', $user_id + 10, 'item.doesnt.exists'));
		$this->assertFalse(api_keystore_get($user_type, $user_id + 10, 'item.doesnt.exists'));
		$this->assertFalse(api_keystore_get($user_type, $user_id, 'item.doesnt.exists'));
		$this->assertSameEquals($username, api_keystore_get($user_type, $user_id, 'username'));
		$this->assertSameEquals($username, api_keystore_get($user_type, $user_id, 'username', true)); // memcache
	}

	/**
	 * @group api_keystore_get_multi_byid
	 * @return void
	 */
	public function test_api_keystore_get_multi_byid() {
		$username1 = uniqid('test1');
		$user1_id = $this->create_new_user($username1);
		$username2 = uniqid('test2');
		$user2_id = $this->create_new_user($username2);
		$user_type = self::get_user_type();

		// Failure empty user ids
		$this->assertSameEquals([], api_keystore_get_multi_byid($user_type, [], 'username'));

		// Failure empty item
		$this->assertSameEquals([], api_keystore_get_multi_byid($user_type, [$user1_id], false));
		$this->assertSameEquals([], api_keystore_get_multi_byid($user_type, [$user1_id], null));
		$this->assertSameEquals([], api_keystore_get_multi_byid($user_type, [$user1_id], ''));

		$this->assertSameEquals([], api_keystore_get_multi_byid('type.doesnt.exists', [$user2_id + 10], 'item.doesnt.exists'));
		$this->assertSameEquals([], api_keystore_get_multi_byid($user_type, [$user2_id + 10], 'item.doesnt.exists'));
		$this->assertSameEquals([], api_keystore_get_multi_byid($user_type, [$user1_id], 'item.doesnt.exists'));

		$this->assertSameEquals(
			[$user1_id => $username1, $user2_id => $username2],
			api_keystore_get_multi_byid($user_type, [$user1_id, $user2_id], 'username')
		);

		// good and wrong user ids
		$this->assertSameEquals(
			[$user1_id => $username1],
			api_keystore_get_multi_byid($user_type, [$user1_id, $user2_id + 10], 'username')
		);

		// All wrong user ids
		$this->assertSameEquals(
			[],
			api_keystore_get_multi_byid($user_type, [$user2_id + 10, $user2_id + 10], 'username')
		);
	}

	/**
	 * @group api_keystore_get_multi_byitem
	 * @return void
	 */
	public function test_api_keystore_get_multi_byitem() {
		$username = uniqid('test');
		$user_id = $this->create_new_user($username);
		$user_type = self::get_user_type();

		// Failure empty user ids
		$this->assertSameEquals([], api_keystore_get_multi_byitem($user_type, false, ['item']));
		$this->assertSameEquals([], api_keystore_get_multi_byitem($user_type, null, ['item']));
		$this->assertSameEquals([], api_keystore_get_multi_byitem($user_type, '', ['item']));

		// Failure empty items
		$this->assertSameEquals([], api_keystore_get_multi_byitem($user_type, $user_id, []));

		$this->assertSameEquals([], api_keystore_get_multi_byitem('type.doesnt.exists', $user_id + 10, ['item.doesnt.exists']));
		$this->assertSameEquals([], api_keystore_get_multi_byitem($user_type, $user_id + 10, ['item.doesnt.exists']));
		$this->assertSameEquals([], api_keystore_get_multi_byitem($user_type, $user_id, ['item.doesnt.exists']));

		$this->assertSameEquals(
			['status' => '-1', 'username' => $username],
			api_keystore_get_multi_byitem($user_type, $user_id, ['status', 'username'])
		);

		/* good and wrong items */
		$this->assertSameEquals(
			['username' => $username],
			api_keystore_get_multi_byitem($user_type, $user_id, ['username', 'whatever'])
		);

		/* All wrong items */
		$this->assertSameEquals(
			[],
			api_keystore_get_multi_byitem($user_type, $user_id, ['whatever', 'whatever_again'])
		);
	}

	/**
	 * @group api_keystore_set
	 * @return void
	 */
	public function test_api_keystore_set() {
		$user_id = $this->create_new_user();
		$user_type = self::get_user_type();

		// id non numeric
		$this->assertFalse(api_keystore_set($user_type, false, 'item', 'value'));
		$this->assertFalse(api_keystore_set($user_type, null, 'item', 'value'));
		$this->assertFalse(api_keystore_set($user_type, '', 'item', 'value'));

		// item empty
		$this->assertFalse(api_keystore_set($user_type, $user_id, false, 'value'));
		$this->assertFalse(api_keystore_set($user_type, $user_id, null, 'value'));
		$this->assertFalse(api_keystore_set($user_type, $user_id, '', 'value'));

		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_keystore_set', 'value'));
		$this->assertSameEquals('value', api_keystore_get($user_type, $user_id, 'test_keystore_set'));

		// invalid type
		$this->assertFalse(api_keystore_set(false, $user_id, 'test_keystore_set', 'value'));

		// query failure (item varchar > 100)
		$this->mock_function_value('api_db_query_write', false);
		$this->assertFalse(api_keystore_set($user_type, $user_id, 'should_fail', 'should_fail'));
		$this->remove_mocked_functions('api_db_query_write');

		// update same value
		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_keystore_set', 'value'));
		$this->assertSameEquals('value', api_keystore_get($user_type, $user_id, 'test_keystore_set'));

		// test data type cast
		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_keystore_set', false));
		$this->assertSameEquals('0', api_keystore_get($user_type, $user_id, 'test_keystore_set'));
		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_keystore_set', null));
		$this->assertSameEquals(null, api_keystore_get($user_type, $user_id, 'test_keystore_set'));
		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_keystore_set', 0));
		$this->assertSameEquals('0', api_keystore_get($user_type, $user_id, 'test_keystore_set'));
	}

	/**
	 * @group api_keystore_cas
	 * @return void
	 */
	public function test_api_keystore_cas() {
		$user_id = $this->create_new_user();
		$user_type = self::get_user_type();

		$this->assertFalse(api_keystore_cas($user_type, $user_id, 'test_api_keystore_cas', 'value', 'value.doesnt.exists'));
		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_api_keystore_cas', 'value'));
		$this->assertTrue(api_keystore_cas($user_type, $user_id, 'test_api_keystore_cas', 'value', 'value.doesnt.exists'));
	}

	/**
	 * @group api_keystore_delete
	 * @return void
	 */
	public function test_api_keystore_delete() {
		$user_id = $this->create_new_user();
		$user_type = self::get_user_type();

		// id non numeric
		$this->assertFalse(api_keystore_delete($user_type, false, 'item'));
		$this->assertFalse(api_keystore_delete($user_type, null, 'item'));
		$this->assertFalse(api_keystore_delete($user_type, '', 'item'));

		// item empty
		$this->assertFalse(api_keystore_delete($user_type, $user_id, false));
		$this->assertFalse(api_keystore_delete($user_type, $user_id, null));
		$this->assertFalse(api_keystore_delete($user_type, $user_id, ''));

		$this->assertFalse(api_keystore_delete('type.doesnt.exists', $user_id + 10, 'item.doesnt.exists'));
		$this->assertFalse(api_keystore_delete($user_type, $user_id + 10, 'item.doesnt.exists'));
		$this->assertFalse(api_keystore_delete($user_type, $user_id, null));
		$this->assertFalse(api_keystore_delete($user_type, $user_id, 'item.doesnt.exists'));

		$this->assertTrue(api_keystore_set($user_type, $user_id, 'test_api_keystore_delete', 'value'));
		$this->assertTrue(api_keystore_delete($user_type, $user_id, 'test_api_keystore_delete'));
		$this->assertFalse(api_keystore_get($user_type, $user_id, 'test_api_keystore_delete'));
		$this->assertFalse(api_keystore_delete($user_type, $user_id, 'test_api_keystore_delete'));
	}

	/**
	 * @group api_keystore_increment
	 * @return void
	 */
	public function test_api_keystore_increment() {
		$user_type = self::get_user_type();
		$next_id = $this->get_expected_next_user_id();
		$this->assertFalse(api_keystore_increment($user_type, null, 'user.doesnt.exists'));
		$this->assertFalse(api_keystore_increment($user_type, false, 'user.doesnt.exists'));
		$this->assertFalse(api_keystore_increment($user_type, '', 'user.doesnt.exists'));
		$this->assertFalse(api_keystore_increment($user_type, $next_id, 'user.doesnt.exists'));

		$user_id = $this->create_new_user();
		$this->assertFalse(api_keystore_increment($user_type, $user_id, 'whatever.doesnt.exists'));

		$autherrors = api_users_setting_getsingle($user_id, "autherrors");
		$this->assertEquals($autherrors + 1, api_keystore_increment($user_type, $user_id, 'autherrors'));
	}

	/**
	 * @group api_keystore_purge
	 * @return void
	 */
	public function test_api_keystore_purge() {
		$username = uniqid('test');
		$user_id = $this->create_new_user($username);
		$user_type = self::get_user_type();

		$this->assertFalse(api_keystore_purge($user_type, null));
		$this->assertFalse(api_keystore_purge($user_type, false));

		$this->assertSameEquals($username, api_keystore_get($user_type, $user_id, 'username'));
		$this->assertTrue(api_keystore_purge($user_type, $user_id));
		$this->assertFalse(api_keystore_get($user_type, $user_id, 'username'));
	}

	/**
	 * @group api_keystore_getnamespace
	 * @return void
	 */
	public function test_api_keystore_getnamespace() {
		$username = uniqid('test');
		$user_id = $this->create_new_user($username);
		$user_type = self::get_user_type();

		// id non numeric
		$this->assertFalse(api_keystore_getnamespace($user_type, false));
		$this->assertFalse(api_keystore_getnamespace($user_type, null));
		$this->assertFalse(api_keystore_getnamespace($user_type, ''));

		// wrong type
		$this->assertFalse(api_keystore_getnamespace($this, $user_id));
		$values = api_keystore_getnamespace('type.doesnt.exists', $user_id);
		$this->assertInternalType('array', $values);
		$this->assertEmpty($values);

		// Query failed
		$this->assertFalse(api_keystore_getnamespace($user_type, pow(1000, 1000)));

		// id non numeric
		$values = api_keystore_getnamespace('type.doesnt.exists', $user_id + 10);
		$this->assertInternalType('array', $values);
		$this->assertEmpty($values);
		$values = api_keystore_getnamespace($user_type, $user_id + 10);
		$this->assertInternalType('array', $values);
		$this->assertEmpty($values);
		$values = api_keystore_getnamespace('type.doesnt.exists', $user_id);
		$this->assertInternalType('array', $values);
		$this->assertEmpty($values);

		$values = api_keystore_getnamespace($user_type, $user_id);
		$this->assertInternalType('array', $values);
		$default_expected_values = $this->get_default_expected_values($user_type);
		$this->assertCount(13, $values);
		$this->assertEmpty(array_diff($default_expected_values, $values));
	}

	/**
	 * @group api_keystore_getnamespaces_byids
	 * @return void
	 */
	public function test_api_keystore_getnamespaces_byids() {
		$admin_userid = $this->get_default_admin_id();

		// failures wront type
		$this->assertFalse(api_keystore_getnamespaces_byids(null, [1]));
		$this->assertFalse(api_keystore_getnamespaces_byids(false, [2]));
		$this->assertFalse(api_keystore_getnamespaces_byids(true, [1]));
		$this->assertFalse(api_keystore_getnamespaces_byids(1, [1]));
		$this->assertFalse(api_keystore_getnamespaces_byids([22], [1]));

		// no users found
		$users = api_keystore_getnamespaces_byids('USERS', [9999999]);
		$this->assertInternalType('array', $users);
		$this->assertEmpty($users);

		// found 1 user
		$users = api_keystore_getnamespaces_byids('USERS', [$admin_userid]);
		$this->assertInternalType('array', $users);
		$this->assertCount(1, $users);
		$users = api_keystore_getnamespaces_byids('USERS', [$admin_userid, 9999999]);
		$this->assertInternalType('array', $users);
		$this->assertCount(1, $users);

		// found many users
		$user1_id = $this->create_new_user();
		$user2_id = $this->create_new_user();

		$users = api_keystore_getnamespaces_byids('USERS', [$admin_userid, $user1_id, $user2_id]);
		$this->assertInternalType('array', $users);
		$this->assertCount(3, $users);

		// test filters
		// no filters
		$users = api_keystore_getnamespaces_byids(
			'USERS',
			[$admin_userid, $user1_id, $user2_id],
			['whatever' => 'whatever']
		);
		$this->assertInternalType('array', $users);
		$this->assertEmpty($users);

		// filters key found but value not match
		$users = api_keystore_getnamespaces_byids(
			'USERS',
			[$admin_userid, $user1_id, $user2_id],
			['username' => 'whatever']
		);
		$this->assertInternalType('array', $users);
		$this->assertEmpty($users);

		// filters key found but wrong value
		$users = api_keystore_getnamespaces_byids(
			'USERS',
			[$admin_userid, $user1_id, $user2_id],
			['firstname' => 'Admin']
		);
		$this->assertInternalType('array', $users);
		$this->assertCount(1, $users);

		// filters case sensitive
		$users = api_keystore_getnamespaces_byids(
			'USERS',
			[$admin_userid, $user1_id, $user2_id],
			['firstname' => 'admin']
		);
		$this->assertInternalType('array', $users);
		$this->assertEmpty($users);
	}

	/**
	 * @group api_keystore_getentirenamespace
	 * @return void
	 */
	public function test_api_keystore_getentirenamespace() {
		$username = uniqid('test');
		$user_id = $this->create_new_user($username);

		$values = api_keystore_getentirenamespace('type.doesnt.exists');
		$this->assertInternalType('array', $values);
		$this->assertEmpty($values);

		// invalid type
		$this->assertFalse(api_keystore_getentirenamespace(false));

		// query failure
		$this->mock_function_value('api_db_query_read', false);
		$this->assertFalse(api_keystore_getentirenamespace('SECURITYZONE'));
		$this->remove_mocked_functions('api_db_query_read');

		$values = api_keystore_getentirenamespace('SECURITYZONE');
		$this->assertInternalType('array', $values);
	}

	/**
	 * @group api_keystore_getids
	 * @return void
	 */
	public function test_api_keystore_getids() {
		$username = uniqid('test');
		$user_id = $this->create_new_user($username);
		$user_type = self::get_user_type();

		$this->assertSameEquals([], api_keystore_getids('type.doesnt.exists'));

		$ids = api_keystore_getids('SECURITYZONE');
		$this->assertInternalType('array', $ids);
		$this->assertNotEmpty('array', $ids);

		$values = api_keystore_getids($user_type, 'username');
		$this->assertInternalType('array', $values);
		$this->assertNotEmpty('array', $values);
		$this->assertTrue(in_array($user_id, $values));
		$this->assertFalse(in_array($user_id + 1, $values));

		$values = api_keystore_getids($user_type, 'username', true);
		$this->assertInternalType('array', $values);
		$this->assertNotEmpty('array', $values);
		$this->assertTrue(in_array($username, $values));
	}

	/**
	 * @group api_keystore_getidswithvalue
	 * @return void
	 */
	public function test_api_keystore_getidswithvalue() {
		$username = uniqid('test');

		// empty $type
		$this->assertSameEquals([], api_keystore_getidswithvalue(false, 'name', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue(null, 'name', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue(0, 'name', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('', 'name', 'API - REST - DNC - Get'));

		// empty $item
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', false, 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', null, 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 0, 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', '', 'API - REST - DNC - Get'));

		// empty $value
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'name', false));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'name', null));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'name', 0));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'name', ''));

		// type failure
		$this->assertSameEquals([], api_keystore_getidswithvalue($this, 'name', 'API - REST - DNC - Get'));

		// query failure
		$this->mock_function_value('api_db_query_read', false);
		$this->assertFalse(api_keystore_getidswithvalue('QUERY', 'SHOULD', 'FAIL'));
		$this->remove_mocked_functions('api_db_query_read');

		// values does not exists
		$this->assertSameEquals([], api_keystore_getidswithvalue('type.doesnt.exists', 'item.doesnt.exists', 'value.doesnt.exists'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'item.doesnt.exists', 'value.doesnt.exists'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'name', 'value.doesnt.exists'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('SECURITYZONE', 'item.doesnt.exists', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_getidswithvalue('type.doesnt.exists', 'name', 'API - REST - DNC - Get'));

		// id not 0
		$this->assertSameEquals([], api_keystore_getidswithvalue('RATEPLANS', 'smsaumobile', 'SMS - AU mobile'));

		// success
		$this->assertSameEquals([150], api_keystore_getidswithvalue('SECURITYZONE', 'name', 'API - REST - DNC - Get'));
	}

	/**
	 * @group api_keystore_checkkeyexists
	 * @return void
	 */
	public function test_api_keystore_checkkeyexists() {
		$username = uniqid('test');

		// empty $type
		$this->assertSameEquals([], api_keystore_checkkeyexists(false, 'name', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_checkkeyexists(null, 'name', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_checkkeyexists(0, 'name', 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_checkkeyexists('', 'name', 'API - REST - DNC - Get'));

		// empty $item
		$this->assertSameEquals([], api_keystore_checkkeyexists('SECURITYZONE', false, 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_checkkeyexists('SECURITYZONE', null, 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_checkkeyexists('SECURITYZONE', 0, 'API - REST - DNC - Get'));
		$this->assertSameEquals([], api_keystore_checkkeyexists('SECURITYZONE', '', 'API - REST - DNC - Get'));

		// empty $value
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'name', false));
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'name', null));
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'name', 0));
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'name', ''));

		// type failure
		$this->assertSameEquals([], api_keystore_checkkeyexists($this, 'name', 'value'));

		// query failure
		$this->mock_function_value('api_db_query_read', false);
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'name', 'value'));
		$this->remove_mocked_functions('api_db_query_read');

		// values does not exists
		$this->assertFalse(api_keystore_checkkeyexists('type.doesnt.exists', 'item.doesnt.exists', 'value.doesnt.exists'));
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'item.doesnt.exists', 'value.doesnt.exists'));
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'name', 'value.doesnt.exists'));
		$this->assertFalse(api_keystore_checkkeyexists('SECURITYZONE', 'item.doesnt.exists', 'API - REST - DNC - Get'));
		$this->assertFalse(api_keystore_checkkeyexists('type.doesnt.exists', 'name', 'API - REST - DNC - Get'));

		// success
		$this->assertSameEquals('0', api_keystore_checkkeyexists('RATEPLANS', 'smsaumobile', 'SMS - AU mobile'));
		$this->assertSameEquals('150', api_keystore_checkkeyexists('SECURITYZONE', 'name', 'API - REST - DNC - Get'));

		// success but api_misc_audit called
		$expected_id = api_keystore_increment('SECURITYZONE', 0, 'nextid');
		$this->assertInternalType('int', $expected_id);
		$this->assertTrue(api_security_zone_setting_set($expected_id, 'name', 'ACE - Login'));
		$this->assertSameEquals('140', api_keystore_checkkeyexists('SECURITYZONE', 'name', 'ACE - Login'));
		$this->assertTrue(api_keystore_delete('SECURITYZONE', $expected_id, 'name'));

		// casesensitive checks
		$this->assertSameEquals('150', api_keystore_checkkeyexists('SECURITYZONE', 'name', 'API - REST - DNC - Get', ['casesensitive' => false]));
		$this->assertSameEquals('150', api_keystore_checkkeyexists('SECURITYZONE', 'name', 'API - REST - DNC - Get', ['casesensitive' => true]));
		$this->assertSameEquals('150', api_keystore_checkkeyexists('SECURITYZONE', 'name', 'api - rest - Dnc - get', ['casesensitive' => false]));
		$this->assertSameEquals(false, api_keystore_checkkeyexists('SECURITYZONE', 'name', 'api - rest - Dnc - get', ['casesensitive' => true]));
	}
}
