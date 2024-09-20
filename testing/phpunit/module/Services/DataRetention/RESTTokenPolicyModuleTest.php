<?php
/**
 * ActivityLoggerModuleTest
 * Module tests for Activity Logger
 *
 * @author		kevin.ohayon@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\DataRetention;

use Services\ConfigReader;
use Services\DataRetention\RESTTokenPolicy;
use testing\module\AbstractPhpunitModuleTest;
use testing\module\helpers\UserModuleHelper;

/**
 * RESTTokenPolicyModuleTest
 */
class RESTTokenPolicyModuleTest extends AbstractPhpunitModuleTest
{
	use UserModuleHelper;

	/** @var ConfigReader */
	private $mockConfigReader;

	/**
	 * {@inheritDoc}
	 * @see AbstractPhpunitModuleTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->mockConfigReader = $this
			->getMockBuilder(ConfigReader::class)
			->disableOriginalConstructor()
			->getMock();

		$this->mockConfigReader
			->method('getConfig')
				->with(ConfigReader::DATA_RETENTION_CONFIG_TYPE)
				->willReturn(
					[

					]
				);
	}

	/**
	 * Test new instance failure
	 *
	 * @expectedException        Services\Exceptions\DataRetentionPolicyException
	 * @expectedExceptionMessage Data retention: Group id
	 *
	 * @return void
	 */
	public function testInstanceFailure() {
		$groupId = $this->get_expected_next_group_id();
		new RESTTokenPolicy($groupId, $this->mockConfigReader);
	}

	/**
	 * Test remove token success scenario
	 *
	 * @return void
	 */
	public function testRemoveAllForUserGroupSuccess() {
		// create unrelated users to unrelated group to check we wont delete them
		$groupIdUnrelated = $this->create_new_group();
		$UnrelatedUserIds = [];
		for ($x = 1; $x <= rand(10, 20); $x++) {
			$UnrelatedUserIds[$x] = $this->create_new_user(null, $groupIdUnrelated);
			api_session_token_create($UnrelatedUserIds[$x]);
		}

		// group we need to clean up
		$groupId = $this->create_new_group();
		$RESTTokenPolicy = new RESTTokenPolicy($groupId, $this->mockConfigReader);

		// delete current group tokens
		$RESTTokenPolicy->removeTokens();

		// test unrelated users still have tokens
		$this->assertSameEquals(
			count($UnrelatedUserIds),
			$this->getNbTokens($UnrelatedUserIds)
		);

		// create current group users without tokens
		$userIds = [];
		for ($x = 1; $x <= rand(5, 10); $x++) {
			$userIds[$x] = $this->create_new_user(null, $groupId);
		}

		// test current users have no tokens
		$this->assertSameEquals(
			0,
			$this->getNbTokens($userIds)
		);

		// test unrelated still have tokens
		$RESTTokenPolicy = new RESTTokenPolicy($groupId, $this->mockConfigReader);
		$RESTTokenPolicy->removeTokens($groupId);
		$this->assertSameEquals(
			count($UnrelatedUserIds),
			$this->getNbTokens($UnrelatedUserIds)
		);

		// create current group users with tokens
		$userIds = [];
		for ($x = 1; $x <= rand(5, 10); $x++) {
			$userIds[$x] = $this->create_new_user(null, $groupId);
			api_session_token_create($userIds[$x]);
		}

		// test current group have tokens
		$this->assertSameEquals(
			count($userIds),
			$this->getNbTokens($userIds)
		);

		// remove current group user tokens
		$RESTTokenPolicy = new RESTTokenPolicy($groupId, $this->mockConfigReader);
		$RESTTokenPolicy->removeTokens($groupId);

		// test unrelated still have tokens
		$this->assertSameEquals(
			count($UnrelatedUserIds),
			$this->getNbTokens($UnrelatedUserIds)
		);

		// test current group does not have token anymore
		$this->assertSameEquals(
			0,
			$this->getNbTokens($userIds)
		);
	}

	/**
	 * @param array $userids
	 * @return number
	 */
	private function getNbTokens(array $userids) {
		$sql = "SELECT COUNT(*)
				FROM `rest_tokens`
				WHERE `userid` IN(" . implode(',', array_fill(0, count($userids), '?')) . ");";
		$rs = api_db_query_read($sql, $userids);

		return $rs ? (int) $rs->Fields("COUNT(*)") : false;
	}
}
