<?php
/**
 * ApiTagsTest
 * Unit test for api_tags.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

/**
 * Api tags Unit Test class
 */
class ApiTagsModuleTest extends AbstractPhpunitModuleTest
{
	use helpers\AudioModuleHelper;
	use helpers\CampaignModuleHelper;
	use helpers\CronModuleHelper;
	use helpers\UserModuleHelper;

	/**
	 * @return array
	 */
	public function get_all_types() {
		return [
			['AUDIO'],
			['CAMPAIGNS'],
			['CRON'],
			['GROUPS'],
			['SMSDIDS'],
			['USERS'],
		];
	}

	/**
	 * @group api_tags_get
	 * @dataProvider get_all_types
	 * @param string $type
	 * @return void
	 */
	public function test_api_tags_get($type) {
		$nextId = $this->get_expected_next_user_id();
		// user does not exists, no tags
		$this->assertEmpty(api_tags_get($type, $nextId));

		$function = sprintf('create_new_%s', strtolower($type));
		if (!method_exists($this, $function)) {
			$function = sprintf('create_new_%s', rtrim(strtolower($type), 's'));
			$this->assertTrue(
				method_exists($this, $function),
				"{$type} Helper method '{$function}' does not exists"
			);
		}

		if (is_array($id = $this->$function())) {
			$id = $id['id'];
		}

		// new user no tags
		$this->assertEmpty(api_tags_get($type, $id));

		// new user has tags
		$this->assertTrue(api_tags_set($type, $id, ['t1' => 1, 't2' => '2']));
		$tags = api_tags_get($type, $id);
		$this->assertInternalType('array', $tags);
		$this->assertCount(2, $tags);

		// test specific tag values
		foreach ([null, '', false, true, 1, 2.1, [null, '', false, true, 1, 2.1]] as $value) {
			// non crypted
			$this->assertTrue(api_tags_set($type, $id, ['specific' => $value]));
			$this->assertSameEquals($value, api_tags_get($type, $id, 'specific'));
		}

		// cleaning and start with new user
		$this->assertTrue(api_keystore_purge($type, $id));
		if (is_array($id = $this->$function())) {
			$id = $id['id'];
		}
		$this->assertTrue(api_tags_set($type, $id, ['s' => 'v', 0 => 0, true => true]));

		// test tag filter
		$this->assertSameEquals(
			false,
			api_tags_get($type, $id, 'whatever')
		);
		$this->assertSameEquals(
			'v',
			api_tags_get($type, $id, 's')
		);
		$this->assertSameEquals(
			['s' => 'v'],
			api_tags_get($type, $id, ['s'])
		);
		$this->assertSameEquals(
			0,
			api_tags_get($type, $id, 0)
		);
		$this->assertSameEquals(
			[0 => 0],
			api_tags_get($type, $id, [0])
		);
		$this->assertSameEquals(
			0,
			api_tags_get($type, $id, '0')
		);
		$this->assertSameEquals(
			[0 => 0],
			api_tags_get($type, $id, ['0'])
		);
		$this->assertSameEquals(
			['s' => 'v'],
			api_tags_get($type, $id, ['s', 'whatever'])
		);
		// test tags order
		$this->assertSameEquals(
			[0 => 0, 's' => 'v'],
			api_tags_get($type, $id, [0, 's'])
		);

		// cleaning
		$this->assertTrue(api_keystore_purge($type, $id));
	}

	/**
	 * @group api_tags_set
	 * @dataProvider get_all_types
	 * @param string $type
	 * @return void
	 */
	public function test_api_tags_set($type) {
		$this->purge_all_groups();
		$nextId = $this->get_expected_next_id($type);
		$tags = ['tag1' => 'v1', 'tag2' => 2];

		// user does not exists, no tags
		$this->assertFalse(api_tags_set($type, $nextId));

		$function = sprintf('create_new_%s', strtolower($type));
		if (!method_exists($this, $function)) {
			$function = sprintf('create_new_%s', rtrim(strtolower($type), 's'));
			$this->assertTrue(
				method_exists($this, $function),
				"{$type} Helper method '{$function}' does not exists"
			);
		}
		if (is_array($id = $this->$function())) {
			$id = $id['id'];
		}

		// test specific tag values
		foreach ([null, '', false, true, 1, 2.1, [null, '', false, true, 1, 2.1]] as $value) {
			$value = ['specific' => $value];
			$this->assertTrue(api_tags_set($type, $id, $value));
			$this->assertSameEquals(
				serialize($value),
				api_keystore_get($type, $id, 'tags')
			);
		}

		// non string or int
		foreach ([true, false, null, []] as $v) {
			$this->assertFalse(api_tags_set($type, $id, ['encrypted' => $v], ['encrypted']));
		}
		// string and int
		foreach ([['string', 'string'], [1, '1'], [1.1, '1.1']] as $v) {
			$this->assertTrue(api_tags_set($type, $id, ['specific' => $v[0]], ['specific']));
			$this->assertSameEquals($v[1], api_tags_get($type, $id, 'specific'));
		}

		// cleaning
		$this->assertTrue(api_keystore_purge($type, $id));
	}
}
