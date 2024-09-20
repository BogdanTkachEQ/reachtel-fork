<?php
/**
 * ApiEmailModuleTest
 * @author Phillip Berry
 *
 * @copyright  ReachTel (ABN 40 133 677 933)
 *
 */

namespace testing\module;

use testing\module\helpers\UserModuleHelper;

/**
 * Class ApiEmailTemplatesModuleTest
 */
class ApiEmailModuleTest extends AbstractPhpunitModuleTest
{
	use UserModuleHelper;

	public $user1;
	public $user2;
	public $user3;
	public $groupId1;
	public $groupId2;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->groupId1 = $this->create_new_group("test-group-1");
		$this->groupId2 = $this->create_new_group("test-group-2");
		$this->user1 = $this->create_new_user("test1", $this->groupId1);
		$this->user2 = $this->create_new_user("test2", $this->groupId1);
		$this->user3 = $this->create_new_user("test3", $this->groupId2);
		$this->generate_smtp_events($this->user1, 100);
		$this->generate_smtp_events($this->user2, 50);
		$this->generate_smtp_events($this->user3, 10);
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		parent::tearDown();
		api_users_delete($this->user1);
		api_users_delete($this->user2);
		api_users_delete($this->user3);
		api_groups_delete($this->groupId1);
		api_groups_delete($this->groupId2);
		api_db_query_write("delete from smtp_events");
	}

	/**
	* @return void
	*/
	public function test_api_email_smtp_api_sendrate() {
		$sendRate1 = api_email_smtp_api_sendrate(new \DateTime("yesterday"), new \DateTime(), $this->groupId1);
		$sendRate2 = api_email_smtp_api_sendrate(new \DateTime("yesterday"), new \DateTime(), $this->groupId2);
		$this->assertEquals(["test1" => 100, "test2" => 50], $sendRate1);
		$this->assertEquals(["test3" => 10], $sendRate2);
	}

	/**
	 * @return void
	 */
	public function test_api_email_insert_smtp_event() {
		$guid = uniqid();
		$this->assertNotFalse(api_email_insert_smtp_event($this->user1, $guid, "processed", "test"));
		$this->assertFalse(api_email_insert_smtp_event(-1, $guid, "processed", "test"));
	}

	/**
	 * @return void
	 */
	public function test_api_email_retrieve_last_smtp_event_for_guid() {
		$guid = uniqid();
		api_email_insert_smtp_event($this->user1, $guid, "processed", "test");
		api_email_insert_smtp_event($this->user1, $guid, "delivered", "test");
		$last = api_email_retrieve_last_smtp_event_for_guid($guid);
		$data = $last->GetRowAssoc();
		$this->assertNotFalse($data);
		$this->assertEquals("delivered", $data["event_type"]);
	}

	/**
	 * Generates double the amount of $numberOfDelivered as attempts, the rest are bounced or dropped
	 * @param integer $userId            Userid for events.
	 * @param integer $numberOfDelivered Number of delivered events.
	 * @return void
	 */
	private function generate_smtp_events($userId, $numberOfDelivered) {
		for ($i = 0; $i < $numberOfDelivered * 2; $i++) {
			$guid = uniqid();
			api_email_insert_smtp_event($userId, $guid, "processed", "test");
			if ($i % 2 == 0) {
				api_email_insert_smtp_event($userId, $guid, "delivered", ["response" => "ok"]);
				if (rand(1, 10) <= 5) {
					api_email_insert_smtp_event($userId, $guid, "clicked", "");
				}
			} else {
				if (rand(1, 10) <= 5) {
					api_email_insert_smtp_event($userId, $guid, "bounced", "test");
				} else {
					api_email_insert_smtp_event($userId, $guid, "dropped", ["response" => "551 could not send"]);
				}
			}
		}
	}
}
