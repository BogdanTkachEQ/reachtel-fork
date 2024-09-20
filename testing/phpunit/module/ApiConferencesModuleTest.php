<?php
/**
 * ApiConferencesModuleTest
 * Module test for api_campaigns.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use testing\module\helpers\ConferenceModuleHelper;

/**
 * Api Conferences Module Test
 */
class ApiConferencesModuleTest extends AbstractPhpunitModuleTest
{
	use ConferenceModuleHelper;

	/**
	 * @group_disabled api_conferences_add
	 * @return void
	 */
	public function test_api_conferences_add() {
		$this->markTestIncomplete('Weird voiceservers Behaviour. Needs to be fixed in MOR-1783');

		// Purge active voice servers only
		$this->purge_all_voiceservers(true);

		// Failures no userid options
		$this->assertFalse(api_conferences_add());
		$this->assertFalse(api_conferences_add([]));
		$this->assertFalse(api_conferences_add(['whatever' => 'whatever']));

		// Failures no active servers
		$this->assertFalse(api_conferences_add(['userid' => 2]));

		$voiceserver_id = $this->create_new_voiceserver();
		// Failures servers but none active servers
		$this->assertFalse(api_conferences_add(['userid' => 2]));

		$voiceserver2_id = $this->create_new_voiceserver();
		$this->assertTrue(api_voice_servers_setting_set($voiceserver_id, 'status', "active"));
		$this->assertTrue(api_voice_servers_setting_set($voiceserver2_id, 'status', "active"));

		// Failures expiry options
		$this->assertFalse(api_conferences_add(['userid' => 2, 'expiry' => 'whatever']));
		// TODO FIXME expiry not working
		// $this->assertFalse(api_conferences_add(['userid' => 2, 'expiry' => 1]));

		// Failure accesscodelength id
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$this->assertFalse(api_conferences_add(['userid' => 2, 'accesscodelength' => 'whatever']));
		$this->assertFalse(api_conferences_add(['userid' => 2, 'accesscodelength' => 11]));
		// TODO FIXME infinte loop if -6
		// $this->assertFalse(api_conferences_add(['userid' => 2, 'accesscodelength' => -6]));

		// Success serverpreference id not in range
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$conference = api_conferences_add(['userid' => 2, 'serverpreference' => $voiceserver2_id + 1]);
		$this->assert_conference(
			$conference,
			$expected_next_conference_id,
			'/^(\d+){6}$/'
		);

		// Success serverpreference id
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$conference = api_conferences_add(['userid' => 2, 'serverpreference' => $voiceserver2_id]);
		$this->assert_conference(
			$conference,
			$expected_next_conference_id,
			'/^(\d+){6}$/'
		);

		// Success accesscodelength
		for ($length = 3; $length <= 10; $length++) {
			$expected_next_conference_id = $this->get_expected_next_conference_id();
			$conference = api_conferences_add(['userid' => 2, 'accesscodelength' => $length]);
			$this->assert_conference(
				$conference,
				$expected_next_conference_id,
				"/^(\d+){{$length}}$/"
			);
		}
	}

	/**
	 * @group api_conferences_exists
	 * @return void
	 */
	public function test_api_conferences_exists() {
		// Failures $conferenceid
		$this->assertFalse(api_conferences_exists(null));
		$this->assertFalse(api_conferences_exists(false));
		$this->assertFalse(api_conferences_exists(''));
		$this->assertFalse(api_conferences_exists('whatever'));

		// Failures conference invalid options
		$conference_id = $this->get_expected_next_conference_id() - 1;
		$invalid_options = [
			[],
			['connectedonly' => false],
			['connectedonly' => null],
			['connectedonly' => ''],
			['awaitinghost' => false],
			['awaitinghost' => null],
			['awaitinghost' => ''],
			['awaitinghost' => true],
			['awaitinghost' => true, 'accesscode' => null],
			['awaitinghost' => false, 'accesscode' => 123456],
		];
		foreach ($invalid_options as $options) {
			$this->assertFalse(api_conferences_exists($conference_id, $options));
		}

		$conference = $this->create_new_conference(true);
		// Failures valid awaitinghost options accesscode redeemed
		$this->assertFalse(
			api_conferences_exists(
				$conference['conferenceid'],
				['awaitinghost' => true, 'accesscode' => $conference['accesscode']]
			)
		);

		$conference = $this->create_new_conference(true, ['DISCONNECTED']);
		// Failures valid awaitinghost status not CONNECTED
		$this->assertFalse(
			api_conferences_exists(
				$conference['conferenceid'],
				['connectedonly' => true, 'accesscode' => $conference['accesscode']]
			)
		);

		$conference = $this->create_new_conference();
		// Failures user id not valid
		$this->assertFalse(
			api_conferences_exists(
				$conference['conferenceid'],
				[
				'awaitinghost' => true,
				'accesscode' => $conference['accesscode'],
				'userid' => $this->get_expected_next_user_id()
				]
			)
		);

		$conference = $this->create_new_conference();
		// Success valid awaitinghost options accesscode not redeemed
		$this->assertTrue(
			api_conferences_exists(
				$conference['conferenceid'],
				['awaitinghost' => true, 'accesscode' => $conference['accesscode']]
			)
		);
		// Success valid awaitinghost options accesscode not redeemed and user id
		$this->assertTrue(
			api_conferences_exists(
				$conference['conferenceid'],
				[
				'awaitinghost' => true,
				'accesscode' => $conference['accesscode'],
				'userid' => $this->get_default_admin_id()
				]
			)
		);

		$conference = $this->create_new_conference(true, ['CONNECTED']);
		// Success valid awaitinghost status is CONNECTED
		$this->assertTrue(
			api_conferences_exists(
				$conference['conferenceid'],
				['connectedonly' => true, 'accesscode' => $conference['accesscode']]
			)
		);
		// Success valid awaitinghost status is CONNECTED and user id
		$this->assertTrue(
			api_conferences_exists(
				$conference['conferenceid'],
				[
				'connectedonly' => true,
				'accesscode' => $conference['accesscode'],
				'userid' => $this->get_default_admin_id()
				]
			)
		);
	}

	/**
	 * @group api_conferences_get
	 * @return void
	 */
	public function test_api_conferences_get() {
		// Failures conference invalid id
		$this->assertFalse(api_conferences_get(null));
		$this->assertFalse(api_conferences_get(false));
		$this->assertFalse(api_conferences_get(''));
		$this->assertFalse(api_conferences_get('whatever'));

		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$this->assertFalse(api_conferences_get($expected_next_conference_id));

		// Success
		$conference = $this->create_new_conference();
		$conference = api_conferences_get($conference['conferenceid']);
		$this->assertInternalType('array', $conference);
		$this->assertArrayHasKey('id', $conference);
		$this->assertArrayHasKey('userid', $conference);
		$this->assertArrayHasKey('timestamp', $conference);
		$this->assertArrayHasKey('serverid', $conference);
		$this->assertArrayHasKey('accesscode', $conference);
		$this->assertArrayHasKey('accesscodeexpiry', $conference);
		$this->assertArrayHasKey('accesscoderedeemed', $conference);
		$this->assertSameEquals((string) $expected_next_conference_id, $conference['id']);
		$this->assertSameEquals((string) $this->get_default_admin_id(), $conference['userid']);
		$this->assertRegExp('/^(\d+){6}$/', $conference['accesscode']);
	}

	/**
	 * @group api_conferences_participants_get
	 * @return void
	 */
	public function test_api_conferences_participants_get() {
		// Failures conference invalid id
		$this->assertFalse(api_conferences_participants_get(null));
		$this->assertFalse(api_conferences_participants_get(false));
		$this->assertFalse(api_conferences_participants_get(''));
		$this->assertFalse(api_conferences_participants_get('whatever'));

		// Failures conference id does not exists
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$participants = api_conferences_participants_get($expected_next_conference_id);
		$this->assertInternalType('array', $participants);
		$this->assertEmpty($participants);

		// Failures participant id does not exists
		$participants = api_conferences_participants_get(
			$expected_next_conference_id,
			['participantid' => $this->get_expected_next_conference_participant_id()]
		);
		$this->assertInternalType('array', $participants);
		$this->assertEmpty($participants);

		// Success no options
		$conference = $this->create_new_conference(true, ['CONNECTED', 'DISCONNECTED']);
		$all_options = [
			[],
			['connectedonly' => false],
			['participantid' => false],
			['participantid' => null],
			['participantid' => ''],
			['participantid' => 'whatever']
		];
		foreach ($all_options as $options) {
			$participants = api_conferences_participants_get($conference['conferenceid'], $options);
			$this->assertInternalType('array', $participants);
			$this->assertCount(2, $participants);
			foreach ($participants as $participant) {
				$this->assert_conference_participant($participant);
			}
		}

		// Success connectedonly option
		$participants = api_conferences_participants_get($conference['conferenceid'], ['connectedonly' => true]);
		$this->assertInternalType('array', $participants);
		$this->assertCount(1, $participants);
		$participant = current($participants);
		$this->assert_conference_participant($participant);
		$this->assertSameEquals('CONNECTED', $participant['status']);

		// Success participantid option
		$conference_participant_id = $this->get_expected_next_conference_participant_id() - 1;
		$participants = api_conferences_participants_get($conference['conferenceid'], ['participantid' => $conference_participant_id]);
		$this->assertInternalType('array', $participants);
		$this->assertCount(1, $participants);
		$participant = current($participants);
		$this->assert_conference_participant($participant);
		$this->assertArrayHasKey($conference_participant_id, $participants);

		// Success connectedonly and participantid option
		$conference = $this->create_new_conference(true, ['CONNECTED']);
		$conference_participant_id = $this->get_expected_next_conference_participant_id() - 1;
		$participants = api_conferences_participants_get(
			$conference['conferenceid'],
			[
				'connectedonly' => true,
				'participantid' => $conference_participant_id
			]
		);
		$this->assertInternalType('array', $participants);
		$this->assertCount(1, $participants);
		$participant = current($participants);
		$this->assert_conference_participant($participant);
		$this->assertArrayHasKey($conference_participant_id, $participants);
	}

	/**
	 * @group api_conferences_participants_kick
	 * @return void
	 */
	public function test_api_conferences_participants_kick() {
		// Failures conference invalid id
		$this->assertFalse(api_conferences_participants_kick(null));
		$this->assertFalse(api_conferences_participants_kick(false));
		$this->assertFalse(api_conferences_participants_kick(''));
		$this->assertFalse(api_conferences_participants_kick('whatever'));

		// Failures conference id does not exists
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$this->assertFalse(api_conferences_participants_kick($expected_next_conference_id));

		// Failures no participants
		$conference = $this->create_new_conference();
		$expected_next_conference_participant_id = $this->get_expected_next_conference_participant_id();
		$this->assertFalse(api_conferences_participants_kick($conference['conferenceid']));
		$this->assertFalse(api_conferences_participants_kick($conference['conferenceid'], [$expected_next_conference_participant_id]));
		$this->assertFalse(api_conferences_participants_kick($conference['conferenceid'], $expected_next_conference_participant_id));

		// Success
		$conference = $this->create_new_conference(false, ['CONNECTED', 'DISCONNECTED']);
		$participant_id = $this->get_expected_next_conference_participant_id() - 2;
		$this->assertTrue(api_conferences_participants_kick($conference['conferenceid']));
		$this->assertTrue(api_conferences_participants_kick($conference['conferenceid'], [$participant_id]));
		$this->assertTrue(api_conferences_participants_kick($conference['conferenceid'], $participant_id));
	}
}
