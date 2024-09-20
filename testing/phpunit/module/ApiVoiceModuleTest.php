<?php
/**
 * ApiVoiceModuleTest
 * Module test for api_voice.php
 *
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\VoiceServerModuleHelper;

/**
 * Class ApiVoiceModuleTest
 */
class ApiVoiceModuleTest extends AbstractPhpunitModuleTest
{
	use VoiceServerModuleHelper;

	/**
	 * @return void
	 */
	public function test_api_voice_supplier_assign() {
		$voice_server_id = $this->create_new_voiceserver(null, 'active');
		$supplier_ids = [];
		for ($i = 0; $i <= 4; $i++) {
			$id = $this->create_new_voicesupplier();
			$this->assertTrue(api_voice_supplier_setting_set($id, VOICE_SUPPLIER_SETTING_STATUS, VOICE_SUPPLIER_SETTING_STATUS_ACTIVE));
			$this->link_supplier_to_server($id, $voice_server_id);
			$supplier_ids[] = $id;
		}

		$expected_supplier_id = $supplier_ids[1];

		// Set the highest priority so that it is returned. Default priority is 5.
		$this->assertTrue(api_voice_supplier_setting_set($expected_supplier_id, VOICE_SUPPLIER_SETTING_PRIORITY, 10));

		$this->assertSameEquals('0', api_voice_supplier_setting_getsingle($expected_supplier_id, VOICE_SUPPLIER_SETTING_LASTCALL));

		$expected_last_call = '0.76066500 1548731303';
		$this->mock_function_value('microtime', $expected_last_call);
		$this->assertSameEquals($expected_supplier_id, api_voice_supplier_assign());
		$this->assertSameEquals($expected_last_call, api_voice_supplier_setting_getsingle($expected_supplier_id, VOICE_SUPPLIER_SETTING_LASTCALL));

		// Remove everything
		foreach ($supplier_ids as $id) {
			$this->assertTrue(api_voice_supplier_delete($id));
		}

		$this->assertTrue(api_voice_servers_delete($voice_server_id));

		// remove microtime mock
		$this->remove_mocked_functions();
	}
}
