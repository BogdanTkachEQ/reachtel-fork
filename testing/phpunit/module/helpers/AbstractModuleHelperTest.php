<?php
/**
 * AbstractModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use Exception;
use testing\module\AbstractPhpunitModuleTest;

/**
 * Abstract Module Helper Test
 */
abstract class AbstractModuleHelperTest extends AbstractPhpunitModuleTest
{
	const EXPECTED_TYPE_CONSTANT_NAME = 'EXPECTED_TYPE'; // Required constant
	const EXPECTED_TYPE_FUNCTION_TYPE_NAME = 'FUNCTION_TYPE_NAME'; // Optional constant

	/*
	 * Expected type value
	 */
	private $expected_type;

	/**
	 * Function type name
	 */
	private $function_type_name;

	/**
	 * @return void
	 */
	public function setUp() {
		// check TYPE constant is defined
		$constant = sprintf('%s::%s', get_class($this), self::EXPECTED_TYPE_CONSTANT_NAME);
		$this->assertTrue(defined($constant), "Constant {$constant} not found.");
		$this->expected_type = constant($constant);

		// check function TYPE NAME constant is defined (optional)
		$constant = sprintf('%s::%s', get_class($this), self::EXPECTED_TYPE_FUNCTION_TYPE_NAME);
		$this->function_type_name = strtolower(defined($constant) ? constant($constant) : $this->expected_type);

		parent::setUp();
	}

	/**
	 * @group get_asset_type
	 * @group get_audio_type
	 * @group get_campaign_type
	 * @group get_cron_type
	 * @group get_group_type
	 * @group get_hlrsupplier_type
	 * @group get_smssupplier_type
	 * @group get_user_type
	 * @group get_voiceserver_type
	 * @group get_voicesupplier_type
	 * @return void
	 */
	public function test_get_type() {
		if ($this->expected_type) {
			$method = $this->get_method('get_%s_type', false);
			$this->assertTrue(method_exists($this, $method), "Method {$method} not found.");
			$this->assertSameEquals($this->expected_type, $this->$method());
		}
	}

	/**
	 * @group get_expected_next_asset_id
	 * @group get_expected_next_audio_id
	 * @group get_expected_next_campaign_id
	 * @group get_expected_next_cron_id
	 * @group get_expected_next_group_id
	 * @group get_expected_next_hlrsupplier_id
	 * @group get_expected_next_smssupplier_id
	 * @group get_expected_next_user_id
	 * @group get_expected_next_voiceserver_id
	 * @group get_expected_next_voicesupplier_id
	 * @return void
	 */
	public function test_get_expected_next_id() {
		if ($this->expected_type) {
			$expected_type = $this->expected_type;
			$method = $this->get_method('get_expected_next_%s_id', false);
			$next_id = $this->$method();
			$rs = api_db_query_read("SELECT `value` + 1 AS `value` FROM `key_store` WHERE `type` = '{$expected_type}' AND `id` = 0 AND `item` = 'nextid';");
			$this->assertInstanceOf('ADORecordSet_mysqli', $rs);
			$this->assertSameEquals($next_id, (int) $rs->Fields('value'));
		}
	}

	/**
	 * @group purge_all_assets
	 * @group purge_all_audios
	 * @group purge_all_campaigns
	 * @group purge_all_crons
	 * @group purge_all_groups
	 * @group purge_all_users
	 * @return void
	 */
	public function test_purge_all() {
		$purge_method = $this->get_method('purge_all_%s', true);

		if ($this->expected_type && method_exists($this, $purge_method)) {
			$create_new_method = $this->get_method('create_new_%s', false);
			for ($x = 1; $x <= rand(5, 20); $x++) {
				call_user_func([$this, $create_new_method]);
			}

			$values = api_keystore_getentirenamespace($this->expected_type);
			$this->assertInternalType('array', $values);
			$this->assertNotEmpty($values);

			call_user_func([$this, $purge_method]);

			$values = api_keystore_getentirenamespace($this->expected_type);
			$this->assertInternalType('array', $values);
			$this->assertEmpty($values);
		}
	}

	/**
	 * @param boolean $expected_success
	 * @param array   $params
	 * @return mixed
	 */
	protected function do_test_create_new($expected_success, array $params = []) {
		$this->assertNotNull($this->expected_type, 'No expected type is set');
		$expected_id = $this->get_expected_next_id($this->expected_type);

		$method = $this->get_method('create_new_%s', false);
		$id = call_user_func_array([$this, $method], $params);

		if ($expected_success) {
			// Assert $id
			$this->assertEquals($expected_id, $id);

			// Assert default setting values
			$method = $this->get_method('api_%s_setting_getsingle');
			foreach ($this->get_default_expected_values($this->expected_type) as $key => $expected_value) {
				$value = $method($id, $key);
				$this->assertEquals($expected_value, $value, "Failed asserting setting '{$key}'.\n- '$expected_value'\n+ '$value'");
			}
		} else {
			$this->assertFalse($id);
		}

		return $id;
	}

	/**
	 * @param string $pattern
	 * @param mixed  $plural
	 * @return string
	 * @throws Exception Method name does not exists..
	 */
	private function get_method($pattern, $plural = null) {
		// check for  api function
		$type = preg_match('/^api_/i', $pattern) ? $this->function_type_name : $this->expected_type;
		if ($plural !== null) {
			$type = $this->type_plural($type, $plural);
		}

		return strtolower(sprintf($pattern, $type));
	}
}
