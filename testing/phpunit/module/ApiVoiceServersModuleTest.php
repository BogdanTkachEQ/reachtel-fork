<?php
/**
 * ApiVoiceServersModuleTest
 * Module test for api_voice_servers.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\VoiceServerModuleHelper;

/**
 * Api Voice Servers Module Test
 */
class ApiVoiceServersModuleTest extends AbstractPhpunitModuleTest
{
	use VoiceServerModuleHelper;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		self::$type = self::get_voiceserver_type();
	}

	/**
	 * @return void
	 */
	public function test_api_voice_servers_delete() {
		$expected_id = $this->get_expected_next_voiceserver_id();
		$server_name = 'test' . substr(md5(rand()), 0, 11);
		$path_sip = SAVE_LOCATION . "/sip/{$server_name}";
		$path_iax = SAVE_LOCATION . "/iax/{$server_name}";

		$this->assertEquals($expected_id, api_voice_servers_add($server_name));
		$this->assertTrue(is_dir($path_sip));
		$this->assertTrue(is_dir($path_iax));

		// failure not numeric
		$this->assertFalse(api_voice_servers_delete('non-numeric'));

		// failure server assigned to a supplier
		$supplier_id = $this->create_new_voicesupplier();
		$this->link_supplier_to_server($supplier_id, $expected_id);
		$this->assertFalse(api_voice_servers_delete($expected_id));
		$this->unlink_supplier_to_server($supplier_id, $expected_id);

		// failure dir does not exists
		$this->assertTrue(is_dir($path_sip));
		$this->assertTrue(rename($path_sip, "$path_sip-TMP"));
		$this->assertFalse(api_voice_servers_delete($expected_id));
		$this->assertTrue(rename("$path_sip-TMP", $path_sip));

		// failure dir is not empty
		$file = "{$path_iax}/test_api_voice_servers_delete.txt";
		$this->assertEquals(0, file_put_contents($file, ''));
		$this->assertTrue(file_exists($file));
		$this->assertFalse(api_voice_servers_delete($expected_id));
		$this->assertTrue(unlink($file));

		// success
		$this->assertTrue(api_voice_servers_delete($expected_id));
	}
}
