<?php
/**
 * VoiceServerModuleHelper
 * Helper to create voice servers
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait Helper for Voice Server
 */
trait VoiceServerModuleHelper
{
	use VoiceSupplierModuleHelper;

	/**
	 * @return string
	 */
	protected static function get_voiceserver_type() {
		return 'VOICESERVERS';
	}

	/**
	 * @return string
	 */
	protected function get_expected_next_voiceserver_id() {
		return $this->get_expected_next_id(self::get_voiceserver_type());
	}

	/**
	 * @param string $server_name
	 * @param mixed  $status
	 * @return integer
	 */
	protected function create_new_voiceserver($server_name = null, $status = false) {
		$expected_id = $this->get_expected_next_id(self::get_voiceserver_type());
		$this->assertSameEquals($expected_id, api_voice_servers_add($server_name ? : uniqid()));

		if ($status !== false) {
			$this->assertTrue(api_voice_servers_setting_set($expected_id, 'status', $status));
		}

		return (int) $expected_id;
	}

	/**
	 * @param string $supplier_id
	 * @param string $server_id
	 * @return void
	 */
	protected function link_supplier_to_server($supplier_id, $server_id) {
		$this->assertTrue(api_voice_supplier_setting_set($supplier_id, "voiceserver", $server_id));
	}

	/**
	 * @param string $supplier_id
	 * @param string $server_id
	 * @return void
	 */
	protected function unlink_supplier_to_server($supplier_id, $server_id) {
		$this->assertTrue(api_voice_supplier_setting_delete_single($supplier_id, "voiceserver", $server_id));
	}

	/**
	 * @param boolean $active
	 * @return void
	 */
	protected function purge_all_voiceservers($active = false) {
		// TODO FIXME api_voice_servers_listall_active and api_voice_servers_listall params short is fixed ???
		$all_voiceserver_ids = ($active ? array_keys(api_voice_servers_listall_active()) : array_keys(api_voice_servers_listall()));

		$this->assertInternalType('array', $all_voiceserver_ids);
		foreach ($all_voiceserver_ids as $voiceserver_id) {
			$this->assertTrue(api_voice_servers_delete($voiceserver_id));
		}

		$this->assertEmpty(($active ? api_voice_servers_listall_active() : api_voice_servers_listall()));
	}
}
