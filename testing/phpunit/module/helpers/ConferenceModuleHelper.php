<?php
/**
 * ConferenceModuleHelper
 * Helper to create Conferences
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use Exception;

/**
 * Trait Helper for conferences
 */
trait ConferenceModuleHelper
{
	use UserModuleHelper;
	use VoiceServerModuleHelper;

	/**
	 * @param boolean $redeemed
	 * @param array   $statuses
	 * @param mixed   $user_id
	 * @return array
	 */
	protected function create_new_conference($redeemed = false, array $statuses = [], $user_id = false) {
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$servers = api_voice_servers_listall_active();

		if (!$servers) {
			$voiceserver_id = $this->create_new_voiceserver();
			$this->assertTrue(api_voice_servers_setting_set($voiceserver_id, 'status', "active"));
		}

		// Failures expiry options
		$conference = api_conferences_add(['userid' => ($user_id ? : $this->get_default_admin_id())]);

		$this->assert_conference(
			$conference,
			$expected_next_conference_id,
			'/^(\d+){6}$/'
		);

		if ($redeemed) {
			$sql = "UPDATE `conferences` SET `accesscoderedeemed` = ? WHERE `id` = ?";
			$rs = api_db_query_write($sql, array(1, $expected_next_conference_id));
			$this->assertTrue(
				$rs && api_db_affectedrows() > 0,
				"Failed set conferences id={$expected_next_conference_id} redeemed accesscode"
			);
		}

		if ($statuses) {
			foreach ($statuses as $status) {
				$status = strtoupper($status);
				$this->assertContains($status, ['CONNECTED', 'DISCONNECTED']);
				$sql = "INSERT INTO `conferences_status` (`conferenceid`, `status`, `channel`, `callerid`) VALUES (?, ?, ?, ?)";
				$rs = api_db_query_write($sql, array($expected_next_conference_id, $status, 'channel' . rand(1, 9), '07' . rand(11111111, 99999999)));
				$this->assertTrue(
					$rs && api_db_affectedrows() > 0,
					"Failed set conferences status '{$status}' for conference id={$expected_next_conference_id}"
				);
			}
		}

		return $conference;
	}

	/**
	 * @return string
	 * @throws Exception If You have an error in your SQL syntax.
	 */
	protected function get_expected_next_conference_id() {
		$sql = "SELECT (MAX(`id`) + 1) AS NEXT_ID FROM `conferences`;";
		$rs = api_db_query_read($sql);

		if (!$rs) {
			global $DB_WRITE;
			$error = $DB_WRITE->ErrorMsg();
			throw new Exception($error ? : 'You have an error in your SQL syntax');
		}

		$next_id = $rs->fields['NEXT_ID'];

		return is_null($next_id) ? 1 : (int) $next_id;
	}

	/**
	 * @return string
	 * @throws Exception If You have an error in your SQL syntax.
	 */
	protected function get_expected_next_conference_participant_id() {
		$sql = "SELECT (MAX(`participantid`) + 1) AS NEXT_ID FROM `conferences_status`;";
		$rs = api_db_query_read($sql);

		if (!$rs) {
			global $DB_WRITE;
			$error = $DB_WRITE->ErrorMsg();
			throw new Exception($error ? : 'You have an error in your SQL syntax');
		}

		$next_id = $rs->fields['NEXT_ID'];

		return is_null($next_id) ? 1 : (int) $next_id;
	}

	/**
	 * @codeCoverageIgnore
	 * @param mixed $conference
	 * @param mixed $conference_id
	 * @param mixed $accesscode_reg_exp
	 * @return void
	 */
	private function assert_conference($conference, $conference_id, $accesscode_reg_exp) {
		$this->assertInternalType('array', $conference);
		$this->assertArrayHasKey('conferenceid', $conference);
		$this->assertArrayHasKey('accesscode', $conference);
		$this->assertSameEquals($conference_id, $conference['conferenceid']);
		$this->assertRegExp($accesscode_reg_exp, $conference['accesscode']);
	}

	/**
	 * @codeCoverageIgnore
	 * @param mixed $participant
	 * @return void
	 */
	private function assert_conference_participant($participant) {
		$this->assertInternalType('array', $participant);
		$this->assertCount(4, $participant);
		$this->assertArrayHasKey('timestamp', $participant);
		$this->assertArrayHasKey('status', $participant);
		$this->assertArrayHasKey('channel', $participant);
		$this->assertArrayHasKey('callerid', $participant);
		$this->assertContains($participant['status'], ['CONNECTED', 'DISCONNECTED']);
	}
}
