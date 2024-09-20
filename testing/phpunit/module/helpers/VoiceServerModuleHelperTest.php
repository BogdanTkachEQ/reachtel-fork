<?php
/**
 * VoiceServerModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use testing\helpers\MethodParametersHelper;

/**
 * Voice Server Module Helper Test
 */
class VoiceServerModuleHelperTest extends AbstractModuleHelperTest
{
	use MethodParametersHelper;
	use VoiceServerModuleHelper;

	const EXPECTED_TYPE = 'VOICESERVERS';
	const FUNCTION_TYPE_NAME = 'voice_servers';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function test_create_new_voiceserver_data() {
		return $this->get_test_data_from_parameters_combinations(
			[$this, 'create_new_voiceserver'],
			[
				'cron_name' => $this->add_parameter_possibilities([null, (function() {return uniqid();})]),
				'status' => $this->add_parameter_possibilities([false, 'DISABLED', 'ACTIVE']),
			]
		);
	}

	/**
	 * @group create_new_voiceserver
	 * @dataProvider test_create_new_voiceserver_data
	 * @param mixed $server_name
	 * @param mixed $status
	 * @return void
	 */
	public function test_create_new_voiceserver($server_name = null, $status = false) {
		if (is_object($server_name) && $server_name instanceof \Closure) {
			$server_name = $server_name();
		}

		$expected_id = $this->get_expected_next_voiceserver_id();
		$this->assertSameEquals(
			$expected_id,
			$this->create_new_voiceserver($server_name, $status)
		);
		// clean up delete voice server directory
		$this->assertTrue(api_voice_servers_delete($expected_id));
	}

	/**
	 * @group link_supplier_to_server
	 * @return void
	 */
	public function test_link_supplier_to_server() {
		$voice_sever_id = $this->create_new_voiceserver();
		$voice_supplier_id = $this->create_new_voicesupplier();
		$this->assertNull($this->link_supplier_to_server($voice_supplier_id, $voice_sever_id));
		// clean up delete voice server directory
		$this->assertTrue(api_voice_supplier_delete($voice_supplier_id));
		$this->assertTrue(api_voice_servers_delete($voice_sever_id));
	}

	/**
	 * @group unlink_supplier_to_server
	 * @return void
	 */
	public function test_unlink_supplier_to_server() {
		$voice_sever_id = $this->create_new_voiceserver();
		$voice_supplier_id = $this->create_new_voicesupplier();
		$this->assertNull($this->link_supplier_to_server($voice_supplier_id, $voice_sever_id));
		$this->assertNull($this->unlink_supplier_to_server($voice_supplier_id, $voice_sever_id));
		// clean up delete voice server directory
		$this->assertTrue(api_voice_supplier_delete($voice_supplier_id));
		$this->assertTrue(api_voice_servers_delete($voice_sever_id));
	}

	/**
	 * @group purge_all_voiceservers
	 * @return void
	 */
	public function test_purge_all_voiceservers_active_only() {
		$voiceservers = api_voice_servers_listall_active();
		$this->assertInternalType('array', $voiceservers);

		$nb = rand(5, 20);
		$expected = $nb + count($voiceservers);
		for ($x = 1; $x <= $nb; $x++) {
			$this->create_new_voiceserver(null, 'active');
		}

		$voiceservers = api_voice_servers_listall_active();
		$this->assertInternalType('array', $voiceservers);
		$this->assertCount($expected, $voiceservers);

		$this->purge_all_voiceservers(true);

		$voiceservers = api_voice_servers_listall_active();
		$this->assertInternalType('array', $voiceservers);
		$this->assertEmpty($voiceservers);
	}
}
