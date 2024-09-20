<?php
/**
 * ConferenceModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

use testing\helpers\MethodParametersHelper;

/**
 * Conference Module Helper Test
 */
class ConferenceModuleHelperTest extends AbstractModuleHelperTest
{
	use ConferenceModuleHelper;
	use MethodParametersHelper;

	/**
	 * @return void
	 */
	public function setUp() {
		// overrides Abstract setUp
		$this->purge_all_voiceservers(true);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_conference_data() {
		// Success all parameters combinations
		return $this->get_test_data_from_parameters_combinations(
			[$this, 'create_new_conference'],
			[
				'redeemed' => $this->add_parameter_possibilities([false, true]),
				'statuses' => $this->add_parameter_possibilities([[], ['CONNECTED'], ['DISCONNECTED'], ['CONNECTED', 'DISCONNECTED']]),
				'user_id' => $this->add_parameter_possibilities([$this->get_default_admin_id(), $this->create_new_user()]),
			]
		);
	}

	/**
	 * @group create_new_conference
	 * @dataProvider create_new_conference_data
	 * @param mixed $redeemed
	 * @param mixed $statuses
	 * @param mixed $user_id
	 * @return void
	 */
	public function test_create_new_conference($redeemed = false, $statuses = [], $user_id = false) {
		$expected_next_conference_id = $this->get_expected_next_conference_id();
		$conference = $this->create_new_conference($redeemed, $statuses, $user_id);
		$this->assert_conference($conference, $expected_next_conference_id, '/^(\d+){6}$/');

		// asserts redeemed
		$sql = "SELECT * FROM `conferences` WHERE `conferences`.`id` = ?;";
		$rs = api_db_query_read($sql, [$expected_next_conference_id]);
		$this->assertInstanceOf('ADORecordSet_mysqli', $rs);
		$results = $rs->GetAssoc();
		$this->assertInternalType('array', $results);
		$this->assertCount(1, $results);
		$this->assertArrayHasKey($expected_next_conference_id, $results);
		$result = $results[$expected_next_conference_id];
		$this->assertInternalType('array', $result);
		$this->assertCount(6, $result);
		$this->assertSameEquals($redeemed ? '1' : '0', $result['accesscoderedeemed']);
		$this->assertEquals(($user_id ? : $this->get_default_admin_id()), $result['userid']);
	}

	/**
	 * @group get_expected_next_conference_id
	 * @return void
	 */
	public function test_get_expected_next_conference_id() {
		$rs = api_db_query_read('SELECT id FROM `conferences` ORDER BY id DESC;');
		$this->assertInstanceOf('ADORecordSet_mysqli', $rs);
		$results = $rs->GetArray();
		$this->assertInternalType('array', $results);

		if ($results) {
			$result = current($results);
			$this->assertInternalType('array', $result);
			$this->assertArrayHasKey('id', $result);
			$expected_next_conference_id = $result['id'] + 1;
		} else {
			$expected_next_conference_id = 1;
		}

		$this->assertSameEquals(
			$expected_next_conference_id,
			$this->get_expected_next_conference_id()
		);

		// test failure
		$this->setExpectedException('Exception', 'You have an error in your SQL syntax');
		$this->mock_function_value('api_db_query_read', false);
		$this->assertSameEquals(
			$expected_next_conference_id,
			$this->get_expected_next_conference_id()
		);
	}

	/**
	 * @group get_expected_next_conference_participant_id
	 * @return void
	 */
	public function test_get_expected_next_conference_participant_id() {
		$rs = api_db_query_read('SELECT participantid FROM `conferences_status` ORDER BY participantid DESC;');
		$this->assertInstanceOf('ADORecordSet_mysqli', $rs);
		$results = $rs->GetArray();
		$this->assertInternalType('array', $results);

		if ($results) {
			$result = current($results);
			$this->assertInternalType('array', $result);
			$this->assertArrayHasKey('participantid', $result);
			$expected_next_conference_participant_id = $result['participantid'] + 1;
		} else {
			$expected_next_conference_participant_id = 1;
		}

		$this->assertSameEquals(
			$expected_next_conference_participant_id,
			$this->get_expected_next_conference_participant_id()
		);

		// test failure
		$this->setExpectedException('Exception', 'You have an error in your SQL syntax');
		$this->mock_function_value('api_db_query_read', false);
		$this->assertSameEquals(
			$expected_next_conference_participant_id,
			$this->get_expected_next_conference_participant_id()
		);
	}
}
