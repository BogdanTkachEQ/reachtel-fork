<?php
/**
 * ApiCampaignsModuleTest
 * Module test for api_campaigns.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

use DateTime;
use MabeEnum\EnumSet;
use Models\CampaignType;
use Services\Utils\Billing\DestinationType;
use Services\Utils\Billing\Region;
use testing\helpers\MethodParametersHelper;
use testing\module\helpers\MethodsCheckExistsModuleTrait;
use testing\module\helpers\MethodsSettingsModuleTrait;
use testing\module\helpers\MethodsTagsModuleTrait;
use testing\module\helpers\SmsDidModuleHelper;
use testing\module\helpers\UserModuleHelper;
use testing\module\traits\CampaignDataArchiveTest;

/**
 * Api Campaigns Module Test
 */
class ApiCampaignsModuleTest extends AbstractPhpunitModuleTest
{
	use MethodParametersHelper;
	use MethodsCheckExistsModuleTrait;
	use MethodsSettingsModuleTrait;
	use MethodsTagsModuleTrait;
	use UserModuleHelper;
	use CampaignDataArchiveTest;

	/**
	 * Mininum canpaigns to generate
	 *
	 * @param integer $min_campaigns
	 */
	private $min_campaigns = 10;

	/**
	 * Type value
	 */
	private static $type;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		self::$type = self::get_campaign_type();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_list_all_empty_data() {
		return $this->get_test_data_from_parameters_combinations(
			'api_campaigns_list_all',
			[
				'long' => $this->add_parameter_possibilities([true]),
				'userid' => $this->add_parameter_possibilities([false, null]),
				'limit' => $this->add_parameter_possibilities([true]),
				'options' => $this->add_parameter_possibilities(
					[
						['activeonly' => true],
						['isadmin' => true],
						['search' => '*test*'],
						['offset' => 5]
					]
				)
			]
		);
	}

	/**
	 * @group api_campaigns_list_all
	 * @dataProvider api_campaigns_list_all_empty_data
	 * @return void
	 */
	public function test_api_campaigns_list_all_empty() {
		$this->purge_all_campaigns();
		$campaigns = call_user_func_array('api_campaigns_list_all', func_get_args());
		$this->assertInternalType('array', $campaigns);
		$this->assertEmpty($campaigns);
	}

	/**
	 * @group api_campaigns_list_all
	 * @return void
	 */
	public function test_api_campaigns_list_all() {
		$expected_campaigns = $this->create_new_campaigns();
		$expected_user_id = $this->get_expected_next_user_id();

		// return only campaign ids
		$campaigns = api_campaigns_list_all();
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(array_keys($expected_campaigns), $campaigns);

		// return keys = campaign id and value = campaign names
		$campaigns = api_campaigns_list_all(true);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals($expected_campaigns, $campaigns);

		// Dummy user
		$campaigns = api_campaigns_list_all(false, $expected_user_id);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(array_keys($expected_campaigns), $campaigns);
		$campaigns = api_campaigns_list_all(true, $expected_user_id);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals($expected_campaigns, $campaigns);

		// Existing admin user
		$expected_admin_id = $this->get_default_admin_id();
		$campaigns = api_campaigns_list_all(false, $expected_admin_id);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(array_keys($expected_campaigns), $campaigns);
		$campaigns = api_campaigns_list_all(true, $expected_admin_id);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals($expected_campaigns, $campaigns);

		// Existing user
		$group_id = $this->create_new_group();
		$this->assertSameEquals($expected_user_id, (int) $this->create_new_user(null, $group_id));
		$campaigns = api_campaigns_list_all(false, $expected_user_id);
		$this->assertInternalType('array', $campaigns);
		$this->assertEmpty($campaigns);
		$campaigns = api_campaigns_list_all(true, $expected_user_id);
		$this->assertInternalType('array', $campaigns);
		$this->assertEmpty($campaigns);

		// test limit
		$limit = rand(1, $this->min_campaigns);
		$campaigns = api_campaigns_list_all(false, null, $limit);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice(array_keys($expected_campaigns), 0, $limit, true),
			$campaigns
		);
		$campaigns = api_campaigns_list_all(true, null, $limit);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice($expected_campaigns, 0, $limit, true),
			$campaigns
		);
		$campaigns = api_campaigns_list_all(false, $expected_admin_id, $limit);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice(array_keys($expected_campaigns), 0, $limit, true),
			$campaigns
		);
		$campaigns = api_campaigns_list_all(true, $expected_admin_id, $limit);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice($expected_campaigns, 0, $limit, true),
			$campaigns
		);

		// test limit with dummy user
		$campaigns = api_campaigns_list_all(false, 999, $limit);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice(array_keys($expected_campaigns), 0, $limit, true),
			$campaigns
		);
		$campaigns = api_campaigns_list_all(true, 999, $limit);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice($expected_campaigns, 0, $limit, true),
			$campaigns
		);

		// test with different group
		$group_id = $this->create_new_group(uniqid('list_all'));
		$user_id = $this->create_new_user(uniqid('list_all'), $group_id);
		$campaigns = api_campaigns_list_all(false, $user_id);

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_list_all
	 * @return void
	 */
	public function test_api_campaigns_list_all_options() {
		$expected_admin_id = $this->get_default_admin_id();
		$expected_campaigns = $this->create_new_campaigns();

		// *** Option: countonly ***
		// option countonly with default params
		$campaigns = api_campaigns_list_all(null, false, false, ['countonly' => true]);
		$this->assertSameEquals(count($expected_campaigns), (int) $campaigns);

		// option countonly with long param
		$campaigns = api_campaigns_list_all(true, false, false, ['countonly' => true]);
		$this->assertSameEquals(count($expected_campaigns), (int) $campaigns);

		// option countonly with valid check user param
		$campaigns = api_campaigns_list_all(null, $expected_admin_id, false, ['countonly' => true]);
		$this->assertSameEquals(count($expected_campaigns), (int) $campaigns);

		// option countonly with dummy check user param
		$campaigns = api_campaigns_list_all(null, 999, false, ['countonly' => true]);
		$this->assertSameEquals(count($expected_campaigns), (int) $campaigns);

		// option countonly with limit param
		// NOTE: Should return global count of records
		$campaigns = api_campaigns_list_all(null, false, 1, ['countonly' => true]);
		$this->assertSameEquals(count($expected_campaigns), (int) $campaigns);

		// option countonly with limit param with offset option
		// NOTE: Should return global count of records
		$campaigns = api_campaigns_list_all(null, false, 1, ['countonly' => true, 'offset' => 2]);
		$this->assertSameEquals(count($expected_campaigns), (int) $campaigns);

		// *** Option: activeonly ***
		// create some active campaigns
		$expected_active_campaigns = [];
		foreach ($expected_campaigns as $campaign_id => $campaign_name) {
			if (rand(0, 1)) {
				$this->assertTrue(api_campaigns_setting_set($campaign_id, "status", "ACTIVE"));
				$expected_active_campaigns[$campaign_id] = $campaign_name;
			}
		}

		// option activeonly with default params
		$campaigns = api_campaigns_list_all(null, false, false, ['activeonly' => true]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(array_keys($expected_active_campaigns), $campaigns);

		// option activeonly with long param
		$campaigns = api_campaigns_list_all(true, false, false, ['activeonly' => true]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals($expected_active_campaigns, $campaigns);

		// option activeonly with valid check user param
		$campaigns = api_campaigns_list_all(null, $expected_admin_id, false, ['activeonly' => true]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(array_keys($expected_active_campaigns), $campaigns);

		// option activeonly with dummy user param
		$campaigns = api_campaigns_list_all(null, 999, false, ['activeonly' => true]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(array_keys($expected_active_campaigns), $campaigns);

		// option activeonly with limit
		$limit = rand(1, $this->min_campaigns);
		$campaigns = api_campaigns_list_all(null, false, $limit, ['activeonly' => true]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_slice(array_keys($expected_active_campaigns), 0, $limit, true),
			$campaigns
		);

		// option activeonly with limit and offset option
		$offset = rand(1, $this->min_campaigns);
		$limit = $this->min_campaigns;
		$campaigns = api_campaigns_list_all(null, false, $limit, ['activeonly' => true, 'offset' => $offset]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			array_values(array_slice(array_keys($expected_active_campaigns), $offset, $limit, true)),
			array_values($campaigns),
			"Failed asserting option activeonly with limit = {$limit} and offset = {$offset}"
		);

		// option search
		$name = 'search' . uniqid();
		$campaignId = $this->create_new_campaign($name);
		$campaigns = api_campaigns_list_all(false, false, false, ['search' => $name]);
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals(
			[$campaignId],
			$campaigns
		);

		// option search wildcard
		$campaigns = api_campaigns_list_all(false, false, false, ['search' => 'campaign*']);
		$this->assertInternalType('array', $campaigns);
		$this->assertGreaterThan(0, count($campaigns));

		// option search regex
		$campaigns = api_campaigns_list_all(false, false, false, ['regex' => 'campaign.+']);
		$this->assertInternalType('array', $campaigns);
		$this->assertGreaterThan(0, count($campaigns));

		$campaigns = api_campaigns_list_all(false, false, false, ['groupid' => 2]);
		$this->assertInternalType('array', $campaigns);
		$this->assertGreaterThan(0, count($campaigns));

		$campaigns = api_campaigns_list_all(false, false, false, ['campaigntypes' => ['sms']]);
		$types = new EnumSet(CampaignType::class);
		$types->attach(CampaignType::SMS());
		$this->assertInternalType('array', $campaigns);
		$this->assertCount(count(api_groups_get_all_campaignids(2, $types)), $campaigns);

		$campaigns = api_campaigns_list_all(false, false, false, ['campaigntypes' => ['phone']]);
		$types = new EnumSet(CampaignType::class);
		$types->attach(CampaignType::PHONE());
		$this->assertInternalType('array', $campaigns);
		$this->assertCount(count(api_groups_get_all_campaignids(2, $types)), $campaigns);

		$campaigns = api_campaigns_list_all(false, 2, false, ['campaigntypes' => ['phone', 'sms']]);
		$types = new EnumSet(CampaignType::class);
		$types->attach(CampaignType::PHONE());
		$types->attach(CampaignType::SMS());

		$this->assertInternalType('array', $campaigns);
		$this->assertCount(count(api_groups_get_all_campaignids(2, $types)), $campaigns);

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_healthcheck_data() {
		return [
			// Failure campaign does not exists
			[false],

			// Failure heartbeat passed allowed time
			[false, ['status' => 'ACTIVE', 'heartbeattimestamp' => '3 minutes ago']],

			// Failure heartbeat and created passed allowed time
			[false, ['status' => 'ACTIVE', 'heartbeattimestamp' => '3 minutes ago', 'created' => '3 minutes ago']],

			// Failure no heartbeat and created passed allowed time
			[false, ['status' => 'ACTIVE', 'created' => '3 minutes ago']],

			// Success no heartbeat and just created
			[true, ['status' => 'ACTIVE', 'created' => '1 second ago']],

			// Success
			[true, ['status' => 'ACTIVE']],
			[true, ['status' => 'ACTIVE', 'created' => 'now', 'heartbeattimestamp' => 'now']],

			// Success inactive
			[true, ['status' => 'DISABLED']],
			[true, ['heartbeattimestamp' => '3 minutes ago']],
			[true, ['heartbeattimestamp' => '3 minutes ago', 'created' => '3 minutes ago']],
			[true, ['created' => '3 minutes ago']],
			[true, ['created' => '1 minutes ago']],
			[true, ['created' => 'now', 'heartbeattimestamp' => 'now']],

		];
	}

	/**
	 * @group api_campaigns_healthcheck
	 * @dataProvider api_campaigns_healthcheck_data
	 * @param boolean $expected_value
	 * @param array   $settings
	 * @return void
	 */
	public function test_api_campaigns_healthcheck($expected_value, array $settings = []) {
		$campaign_id = count($settings) ? $this->create_new_campaign(uniqid('healthcheck')) : $this->get_expected_next_campaign_id();
		foreach ($settings as $setting => $value) {
			if (in_array($setting, ['heartbeattimestamp', 'created'])) {
				$value = strtotime($value);
			}
			$this->assertTrue(api_campaigns_setting_set($campaign_id, $setting, $value));
		}

		$this->assertSameEquals($expected_value, api_campaigns_healthcheck($campaign_id));

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_add
	 * @return void
	 */
	public function test_api_campaigns_add() {
		$user_id = $this->create_new_user();

		// Add for each type
		$created_campaigns = [];
		foreach ($this->get_campaign_types() as $type) {
			$campaign_name = uniqid("campaign-add-{$type}");
			$campaign_2_name = "{$campaign_name}-2";
			$expected_campaign_id = $this->get_expected_next_campaign_id();

			// Failure invalid campaign name
			$this->assertFalse(api_campaigns_add("!Inv@lid_N@me for type {$type}!", $type));

			// Success
			$this->assertSameEquals(
				$expected_campaign_id,
				api_campaigns_add($campaign_name, $type),
				"Failed creating {$type} campaign '{$campaign_name}'"
			);

			// Fail different user does not exists
			$_SESSION['userid'] = $this->get_expected_next_user_id();
			$this->assertFalse(api_campaigns_add(uniqid('duplicate-fail'), $type));
			unset($_SESSION['userid']);

			// Success different user
			$_SESSION['userid'] = $user_id;
			$expected_campaign_id = $this->get_expected_next_campaign_id();
			$this->assertSameEquals(
				$expected_campaign_id,
				api_campaigns_add($campaign_2_name, $type),
				"Failed creating {$type} second campaign '{$campaign_2_name}'"
			);
			// check correct user
			$this->assertSameEquals((string) $user_id, api_campaigns_setting_getsingle($expected_campaign_id, 'owner'));
			unset($_SESSION['userid']);

			// Failure campaign already exists
			$this->assertFalse(api_campaigns_add($campaign_name, $type));

			// Success duplicate
			$duplicate_campaign_id = $expected_campaign_id;
			$expected_campaign_id = $this->get_expected_next_campaign_id();
			$this->assertSameEquals(
				$expected_campaign_id,
				api_campaigns_add(uniqid("duplicated-from-id-{$duplicate_campaign_id}"), $type, $duplicate_campaign_id),
				"Failed duplicate {$type} campaign '{$campaign_2_name}' from campaign id {$duplicate_campaign_id}"
			);
			// check duplicated from setting
			$this->assertEquals(
				$duplicate_campaign_id,
				api_campaigns_setting_getsingle($expected_campaign_id, 'duplicatedfrom')
			);
		}

		// Fail type does not exists
		$this->assertFalse(api_campaigns_add(uniqid('duplicate-fail'), 'whatever'));

		// Failure no duplicate and no type
		$this->assertFalse(api_campaigns_add(uniqid('duplicate-fail')));

		// Failure duplicate campaign does not exists
		$this->assertFalse(api_campaigns_add(uniqid('duplicate-fail'), null, $this->get_expected_next_campaign_id()));

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @return array
	 */
	public function api_campaigns_add_use_rate_plan_data_provider() {
		return [
			'When not duplicating, it should use users rate plan' => ['10', '4', null, '10'],
			'When duplicating it should duplicate the rate plan' => ['10', '4', '20', '20'],
			'When not duplicating and owner not present it should use default rate plan' => [null, '4', null, '4']
		];
	}

	/**
	 * @dataProvider api_campaigns_add_use_rate_plan_data_provider
	 * @group api_campaigns_add
	 * @param string $user_rate_plan_id
	 * @param string $default_rate_plan_id
	 * @param string $duplicate_campaign_rate_plan_id
	 * @param string $expected
	 * @return void
	 */
	public function test_api_campaigns_add_use_rate_plan($user_rate_plan_id, $default_rate_plan_id, $duplicate_campaign_rate_plan_id, $expected) {
		if ($user_rate_plan_id) {
			$user_id = $this->create_new_user();
			api_users_setting_set($user_id, 'apirateplan', $user_rate_plan_id);
		}

		foreach ($this->get_campaign_types() as $type) {
			$campaignid = api_campaigns_add(uniqid("campaign-add-{$type}"), $type);
			$this->assertSameEquals($default_rate_plan_id, api_campaigns_setting_getsingle($campaignid, 'rateplanid'));

			if ($duplicate_campaign_rate_plan_id) {
				api_campaigns_setting_set($campaignid, 'rateplanid', $duplicate_campaign_rate_plan_id);
			}

			$new_campaignid = api_campaigns_add(
				uniqid("campaign-add-{$type}"),
				$type,
				$duplicate_campaign_rate_plan_id ? $campaignid : null,
				$user_rate_plan_id ? $user_id : null
			);

			$this->assertSameEquals($expected, api_campaigns_setting_getsingle($new_campaignid, 'rateplanid'));
		}

		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_delete
	 * @return void
	 */
	public function test_api_campaigns_delete() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// campaign does not exists
		$this->assertFalse(api_campaigns_delete($expected_campaign_id));

		// campaign exists
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign());
		$this->assertTrue(api_campaigns_delete($expected_campaign_id));

		// campaign does not exists anymore
		$this->assertFalse(api_campaigns_delete($expected_campaign_id));
	}

	/**
	 * @group api_campaigns_checkorcreate
	 * @return void
	 */
	public function test_api_campaigns_checkorcreate() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// Failure empty campiagn name
		$this->assertFalse(api_campaigns_checkorcreate(null, $expected_campaign_id));
		$this->assertFalse(api_campaigns_checkorcreate(false, $expected_campaign_id));
		$this->assertFalse(api_campaigns_checkorcreate('', $expected_campaign_id));

		// Failure invalid campaign id
		$this->assertFalse(api_campaigns_checkorcreate('whatever', null));
		$this->assertFalse(api_campaigns_checkorcreate('whatever', false));
		$this->assertFalse(api_campaigns_checkorcreate('whatever', ''));

		// Failure duplicate campaign id does not exists
		$this->assertFalse(api_campaigns_checkorcreate('whatever', $expected_campaign_id));

		// Success campaign name exists
		$campaign_name = uniqid('checkorcreate');
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign($campaign_name));
		$this->assertSameEquals(
			(string) $expected_campaign_id,
			api_campaigns_checkorcreate($campaign_name, $expected_campaign_id)
		);

		// Success duplicate campaign id
		$this->assertSameEquals(
			$this->get_expected_next_campaign_id(),
			api_campaigns_checkorcreate(uniqid('checkorcreate-new'), $expected_campaign_id)
		);

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_rename
	 * @return void
	 */
	public function test_api_campaigns_rename() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// Failure campaign does not exists
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, 'whatever'));

		// Failure invlid campaign name format
		$campaign_name = uniqid('rename');
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign($campaign_name));
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, null));
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, false));
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, ''));
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, '1234'));
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, str_repeat('A', 61)));
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, '!@#$%^&*()_+'));

		// Failure campaign name exists
		$this->assertFalse(api_campaigns_rename($expected_campaign_id, $campaign_name));

		// Success
		$new_campaign_name = uniqid('rename-new-name');
		$this->assertTrue(api_campaigns_rename($expected_campaign_id, $new_campaign_name));
		$this->assertFalse(api_campaigns_checknameexists($campaign_name));
		$this->assertSameEquals(
			$new_campaign_name,
			api_campaigns_setting_getsingle($expected_campaign_id, 'name')
		);

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_list_active
	 * @return void
	 */
	public function test_api_campaigns_list_active() {
		// Purge all campaigns for test expectations
		$this->purge_all_campaigns();

		// No campaigns
		$campaigns = api_campaigns_list_active();
		$this->assertInternalType('array', $campaigns);
		$this->assertEmpty($campaigns);

		// Only disabled campaigns
		$expected_campaign_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign());
		$campaigns = api_campaigns_list_active();
		$this->assertInternalType('array', $campaigns);
		$this->assertEmpty($campaigns);

		// Active campaigns
		$expected_campaign_2_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals($expected_campaign_2_id, $this->create_new_campaign(null, null, null, ['status' => 'ACTIVE']));
		$campaigns = api_campaigns_list_active();
		$this->assertInternalType('array', $campaigns);
		$this->assertSameEquals([$expected_campaign_2_id], $campaigns);

		// Purge all campaigns for test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_nametoid
	 * @return void
	 */
	public function test_api_campaigns_nametoid() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// Failure campaign does not exists
		$this->assertFalse(api_campaigns_nametoid($expected_campaign_id));

		// Success
		$campaign_name = uniqid('nametoid');
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign($campaign_name));
		$this->assertSameEquals(
			(string) $expected_campaign_id,
			api_campaigns_nametoid($campaign_name)
		);

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_namesuggester
	 * @return void
	 */
	public function test_api_campaigns_namesuggester_failures() {
		// Failure name
		$this->assertFalse(api_campaigns_namesuggester(null));
		$this->assertFalse(api_campaigns_namesuggester(false));
		$this->assertFalse(api_campaigns_namesuggester(''));
	}

	/**
	 * @group api_campaigns_namesuggester
	 * @return void
	 */
	public function test_api_campaigns_namesuggester_fallback_name() {
		$name = uniqid('namesuggester-fallback-name');

		$this->assertSameEquals($name, api_campaigns_namesuggester($name));
		$expected_campaign_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign($name));
		$this->assertSameEquals("{$name}-2", api_campaigns_namesuggester($name));
		$this->assertTrue(api_campaigns_delete($expected_campaign_id));

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_campaigns_namesuggester_data() {
		// %1$s = campaign type, %2$s = date, %3$s = version
		return [
			['CompanyName1-%1$s-ProjectName-%2$s%3$s'],
			['CompanyName2-%2$s-%1$s-ProjectName-%3$s'],
			['CompanyName3-%2$s-ProjectName', true],
			['CompanyName3-ProjectName-%2$s', true],
		];
	}

	/**
	 * @group api_campaigns_namesuggester
	 * @dataProvider api_campaigns_namesuggester_data
	 * @param string  $campaign_name_format
	 * @param boolean $add_version
	 * @return void
	 */
	public function test_api_campaigns_namesuggester($campaign_name_format, $add_version = false) {
		// Random campaign type
		$campaign_type = $this->get_campaign_types()[array_rand($this->get_campaign_types())];
		// Random nb campaign versions
		$nb_campaigns = rand(1, 8);
		// All date format used
		$date_formats = ['jFY', 'jFy', 'jMY', 'jMy'];

		// Generate all existing campaigns
		foreach ($date_formats as $date_format) {
			$date = date($date_format);
			$this->assertInternalType('string', $date);

			for ($x = 0; $x <= $nb_campaigns; $x++) {
				$version = $x ? "-$x" : '';
				$expected_campaign_id = $this->get_expected_next_campaign_id();
				$campaign_name = sprintf($campaign_name_format . ($add_version ? '%3$s' : ''), strtoupper($campaign_type), $date, $version);
				$this->create_new_campaign($campaign_name, $campaign_type);
			}
		}

		$expected_version = '-' . ($nb_campaigns + 1);
		foreach ($date_formats as $date_format) {
			$expected_date = date(str_replace('M', 'F', $date_format));
			$old_date = date($date_format, strtotime(rand(1, 1000) . ' days ago'));
			$date = date($date_format);

			// Success match name like 'Matches "IgniteTravel-SMS-DocRemind-13February14-1'
			// test with old date version = 1
			$this->assertSameEquals(
				sprintf($campaign_name_format . ($add_version ? '%3$s' : ''), strtoupper($campaign_type), $expected_date, $expected_version),
				api_campaigns_namesuggester(sprintf($campaign_name_format, strtoupper($campaign_type), $old_date, '-1'))
			);

			// test with today's date version = 1
			$this->assertSameEquals(
				sprintf($campaign_name_format . ($add_version ? '%3$s' : ''), strtoupper($campaign_type), $expected_date, $expected_version),
				api_campaigns_namesuggester(sprintf($campaign_name_format, strtoupper($campaign_type), $date, '-1'))
			);
		}

		// Purge all campaigns
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_namesuggester
	 * @return void
	 */
	public function test_api_campaigns_namesuggester_other_formats() {
		// Success match name like Matches "ToyotaFS-Hardship-20160705" and "ToyotaFS-Hardship-05072016"
		foreach (['now', 'now -' . rand(1, 12) . ' months'] as $i => $time) {
			foreach (['dmY', 'Ymd'] as $date_format) {
				$date = date($date_format, strtotime($time));
				$date_now = date($date_format);
				$name_prefix = "CompanyName{$i}-Whatever-";

				$this->assertSameEquals("{$name_prefix}{$date_now}", api_campaigns_namesuggester("{$name_prefix}{$date}"));

				$expected_campaign_id = $this->get_expected_next_campaign_id();
				$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign("{$name_prefix}{$date}"));

				$this->assertSameEquals(
					"{$name_prefix}{$date_now}" . ($time == 'now' ? '-2' : ''),
					api_campaigns_namesuggester("{$name_prefix}{$date}")
				);

				$this->assertTrue(api_campaigns_delete($expected_campaign_id));
			}
		}

		// Some-UnsupportedFormat-WithANumber-OnTheEnd-6
		$expected_campaign_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign("Whatever-1"));
		$expected_campaign_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign("Whatever-2"));
		$this->assertSameEquals("Whatever-3", api_campaigns_namesuggester("Whatever-1"));
	}

	/**
	 * @group api_campaigns_averageduration
	 * @return void
	 */
	public function test_api_campaigns_averageduration() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// Failure wrong campaign id
		$this->assertFalse(api_campaigns_averageduration(null, 'whatever', 'whatever'));
		$this->assertFalse(api_campaigns_averageduration(false, 'whatever', 'whatever'));
		$this->assertFalse(api_campaigns_averageduration('', 'whatever', 'whatever'));
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, 'whatever', 'whatever'));

		// Failure campaign not phone
		foreach ($this->get_campaign_types() as $type) {
			if ($type == 'phone') {
				continue;
			}

			$expected_campaign_id = $this->get_expected_next_campaign_id();
			$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign(uniqid("averageduration-{$type}"), $type));
			$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, 'whatever', 'whatever'));
		}

		$expected_campaign_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals($expected_campaign_id, $this->create_new_campaign(uniqid("averageduration"), 'phone'));

		// Failure wrong action name
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, null, 'whatever'));
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, false, 'whatever'));
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, '', 'whatever'));

		// Failure wrong value
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, 'whatever', null));
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, 'whatever', false));
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, 'whatever', ''));

		// Failure no data or call results
		$this->assertFalse(api_campaigns_averageduration($expected_campaign_id, 'action', 'value'));

		// Success
		$gaps = [];
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($expected_campaign_id, $targets);
		$targets = api_targets_listall($expected_campaign_id);
		foreach ($targets as $target_id => $target) {
			$gap = rand(10, 100);
			$gaps[] = $gap;
			$call_results = $this->add_campaign_call_result(date('Y-m'), $target_id, 'hangup', $gap);
			$event_id = key($call_results);

			foreach ($targets as $target_key) {
				$result_id = api_data_responses_add($expected_campaign_id, $event_id, $target_id, $target_key, 'action_1', 'value_1');
			}
		}

		$expected_gap = round(array_sum($gaps) / count($gaps), 1);
		$this->assertSameEquals($expected_gap, api_campaigns_averageduration($expected_campaign_id, 'action_1', 'value_1'));

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_gettimezone
	 * @return void
	 */
	public function test_api_campaigns_gettimezone() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// Failure wrong campaign id
		$this->assertFalse(api_campaigns_gettimezone($expected_campaign_id));

		// Success timezone
		$campaign_id = $this->create_new_campaign();
		$this->assertEquals(new \DateTimeZone('Australia/Brisbane'), api_campaigns_gettimezone($campaign_id));

		// Success global timezone no settings
		$this->assertTrue(api_campaigns_setting_delete_single($campaign_id, 'timezone'));
		$this->assertEquals(new \DateTimeZone('Australia/Brisbane'), api_campaigns_gettimezone($campaign_id));

		// defined timezone in campaign settings
		$this->assertTrue(api_campaigns_setting_set($campaign_id, 'timezone', 'Australia/Sydney'));
		$this->assertEquals(new \DateTimeZone('Australia/Sydney'), api_campaigns_gettimezone($campaign_id));

		// Wrong timezone in campaign settings so return default one
		$this->assertTrue(api_campaigns_setting_set($campaign_id, 'timezone', 'whatever'));
		$this->assertEquals(new \DateTimeZone('Australia/Brisbane'), api_campaigns_gettimezone($campaign_id));

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @return array
	 */
	private function create_new_campaigns() {
		$expected_campaigns = [];

		for ($x = 0; $x <= rand($this->min_campaigns, $this->min_campaigns * 10); $x++) {
			$campaign_name = uniqid("campaign{$x}");
			$campaign_id = $this->create_new_campaign($campaign_name);
			$expected_campaigns[$campaign_id] = $campaign_name;
		}
		krsort($expected_campaigns);

		return $expected_campaigns;
	}

	/**
	 * @group api_campaigns_getclassification
	 * @return void
	 */
	public function test_api_campaigns_getclassification() {
		$expected_campaign_id = $this->get_expected_next_campaign_id();

		// Failure wrong campaign id
		$this->assertFalse(api_campaigns_getclassification($expected_campaign_id));

		// default classification map
		$defaults = [
			'phone' => 'telemarketing',
			'wash' => 'telemarketing',
			'sms' => 'exempt',
			'email' => 'exempt',
		];
		foreach ($defaults as $type => $expected) {
			$campaign_id = $this->create_new_campaign(null, $type);
			$this->assertNotFalse($campaign_id);

			// default classification value
			$this->assertEquals(
				$expected,
				api_campaigns_getclassification($campaign_id)
			);

			// classification is set
			$this->assertTrue(
				api_campaigns_setting_set($campaign_id, 'classification', 'research')
			);
			$this->assertEquals('research', api_campaigns_getclassification($campaign_id));
		}

		// Purge all campaigns for other test expectations
		$this->purge_all_campaigns();
	}

	/**
	 * @group api_campaigns_apirate
	 * @return void
	 */
	public function test_api_campaigns_apirate() {
		// no data
		$this->assertEquals(
			[],
			api_campaigns_apirate(
				new \DateTime('-2 days'),
				new \DateTime('+1 day'),
				$this->get_default_group_id()
			)
		);

		// send sms
		$username1 = 'smsusernametest1';
		$userId1 = $this->create_new_user($username1);
		$username2 = 'smsusernametest2';
		$userId2 = $this->create_new_user($username2);
		$smsDidId = $this->create_new_smsdid();
		$this->assertTrue(
			api_users_setting_set($userId1, 'smsapidid', $smsDidId)
		);

		$this->assertTrue(
			api_users_setting_set($userId2, 'smsapidid', $smsDidId)
		);

		// 1 australian sms
		$this->assertGreaterThan(
			0,
			api_sms_apisend('0422000222', 'test AU', $userId1)
		);
		// 2 NZ sms
		$this->assertGreaterThan(
			0,
			api_sms_apisend('+64205123456', 'test NZ', $userId1)
		);
		$this->assertGreaterThan(
			0,
			api_sms_apisend('+64205123499', 'test NZ 2', $userId2)
		);
		// International sms (Indonesia)
		$this->assertGreaterThan(
			0,
			api_sms_apisend('628113920136', 'test Indonesia', $userId2, null, null, true)
		);

		// wrong dates
		$this->assertEquals(
			[],
			api_campaigns_apirate(
				new \DateTime('-10 day'),
				new \DateTime('-5 day'),
				$this->get_default_group_id()
			)
		);

		// wrong group
		$this->assertEquals(
			[],
			api_campaigns_apirate(
				new \DateTime('-1 day'),
				new \DateTime('+1 day'),
				999999
			)
		);

		// should have 3 sms
		$this->assertEquals(
			[
				Region::REGION_AUSTRALIA => [$username1 => 1],
				Region::REGION_NEW_ZEALAND => [$username1 => 1, $username2 => 1],
				Region::REGION_OTHER => [$username2 => 1],
			],
			api_campaigns_apirate(
				new \DateTime('-1 day'),
				new \DateTime('+1 day'),
				$this->get_default_group_id()
			)
		);
	}

	/**
	 * @group api_campaigns_washrate
	 * @return void
	 */
	public function test_api_campaigns_washrate() {

		// no data
		$this->assertEquals(
			[],
			api_campaigns_washrate(
				new \DateTime('-2 days'),
				new \DateTime('+1 day'),
				$this->get_default_group_id()
			)
		);

		$username1 = 'apiwashtest1';
		$username2 = 'apiwashtest2';

		$userid1 = $this->create_new_user($username1);
		$userid2 = $this->create_new_user($username2);

		foreach ([$userid1, $userid2] as $userid) {
			$data = [
				// au
				'61422000111' => 'CONNECTED', // mobile
				'61212341234' => 'DISCONNECTED', // landline
				'61200440044' => null, // landline QUEUED
				'61422000444' => 'INDETERMINATE', // mobile
				'1300 362 070' => null, // free but not supported
				// nz
				'+64205111000' => 'DISCONNECTED', // mobile
				'+64 9357 3000' => 'CONNECTED', // landline
				// other
				'628113920136' => 'CONNECTED', // other mobile not supported
			];

			if ($userid === $userid2) {
				$data['64205111002'] = 'CONNECTED';
			}

			foreach ($data as $destination => $status) {
				$wash = api_wash_check($destination, $userid);
				$this->assertInternalType('array', $wash);
				if ($status) {
					$this->assertTrue(
						api_wash_out_save($wash['id'], 'CONNECTED', 1, 1)
					);
				}
			}
		}

		// wrong dates
		$this->assertEquals(
			[],
			api_campaigns_washrate(
				new \DateTime('-20 days'),
				new \DateTime('-10 days'),
				$this->get_default_group_id()
			)
		);

		// wrong group
		$this->assertEquals(
			[],
			api_campaigns_washrate(
				new \DateTime('-1 day'),
				new \DateTime('+1 day'),
				999999
			)
		);

		// data
		$this->assertEquals(
			[
				Region::REGION_AUSTRALIA => [
					DestinationType::ID_MOBILE => [$username1 => 2, $username2 => 2],
					DestinationType::ID_LANDLINE => [$username1 => 1, $username2 => 1],
				],
				Region::REGION_NEW_ZEALAND => [
					DestinationType::ID_MOBILE => [$username1 => 1, $username2 => 2],
					DestinationType::ID_LANDLINE => [$username1 => 1, $username2 => 1],
				]
			],
			api_campaigns_washrate(
				new \DateTime('-1 day'),
				new \DateTime('+1 day'),
				$this->get_default_group_id()
			)
		);
	}

	/**
	 * @group api_campaigns_rate
	 * @return void
	 */
	public function test_api_campaigns_rate() {
		$this->assertEquals(
			[],
			api_campaigns_rate(
				new \DateTime('-1 day'),
				new \DateTime('+1 day'),
				$this->get_default_group_id()
			)
		);
	}

	/**
	 * @group api_campaigns_get_campaigns_sent_after_period
	 * @return void
	 */
	public function test_api_campaigns_get_campaigns_sent_after_period() {
		$this->purge_all_campaigns();
		$group1 = $this->create_new_group();
		$group2 = $this->create_new_group();
		$group1user = $this->create_new_user(null, $group1);
		$group2user = $this->create_new_user(null, $group2);

		$date = \DateTime::createFromFormat('d-m-Y H:i:s', '25-09-2019 00:00:00');

		$this->assertEmpty(api_campaigns_get_campaigns_sent_after_period($date, $group1));
		$this->assertEmpty(api_campaigns_get_campaigns_sent_after_period($date, $group2));
		$this->assertEmpty(api_campaigns_get_campaigns_sent_after_period($date));

		$date1 = clone $date;
		$campaign1 = $this->create_new_campaign(
			null,
			null,
			$group1user,
			['lastsend' => $date1->add(new \DateInterval('P1D'))->getTimestamp()]
		);

		$date2 = clone $date;
		$campaign2 = $this->create_new_campaign(
			null,
			null,
			$group2user,
			['lastsend' => $date2->add(new \DateInterval('P2D'))->getTimestamp()]
		);

		$date3 = clone $date;
		$campaign3 = $this->create_new_campaign(
			null,
			null,
			$group1user,
			['lastsend' => $date3->sub(new \DateInterval('P2D'))->getTimestamp()]
		);

		$date4 = clone $date;
		$campaign4 = $this->create_new_campaign(
			null,
			null,
			$group2user,
			['lastsend' => $date4->sub(new \DateInterval('P1D'))->getTimestamp()]
		);

		$return = api_campaigns_get_campaigns_sent_after_period($date, $group1);
		$this->assertEquals([$campaign1], $return);

		$return = api_campaigns_get_campaigns_sent_after_period($date, $group2);
		$this->assertEquals([$campaign2], $return);

		$return = api_campaigns_get_campaigns_sent_after_period($date);
		$this->assertEquals([$campaign1, $campaign2], $return);
	}

	/**
	 * @group api_campaigns_get_campaigns_sent_after_period
	 * @return void
	 */
	public function test_api_campaigns_get_campaigns_sent_before_period() {
		$this->purge_all_campaigns();
		$group1 = $this->create_new_group();
		$group2 = $this->create_new_group();
		$group1user = $this->create_new_user(null, $group1);
		$group2user = $this->create_new_user(null, $group2);

		$date = \DateTime::createFromFormat('d-m-Y H:i:s', '25-09-2019 00:00:00');

		$this->assertEmpty(api_campaigns_get_campaigns_sent_after_period($date, $group1));
		$this->assertEmpty(api_campaigns_get_campaigns_sent_after_period($date, $group2));
		$this->assertEmpty(api_campaigns_get_campaigns_sent_after_period($date));

		$date1 = clone $date;
		$campaign1 = $this->create_new_campaign(
			null,
			null,
			$group1user,
			['lastsend' => $date1->add(new \DateInterval('P1D'))->getTimestamp()]
		);

		$date2 = clone $date;
		$campaign2 = $this->create_new_campaign(
			null,
			null,
			$group2user,
			['lastsend' => $date2->add(new \DateInterval('P2D'))->getTimestamp()]
		);

		$date3 = clone $date;
		$campaign3 = $this->create_new_campaign(
			null,
			null,
			$group1user,
			['lastsend' => $date3->sub(new \DateInterval('P2D'))->getTimestamp()]
		);

		$date4 = clone $date;
		$campaign4 = $this->create_new_campaign(
			null,
			null,
			$group2user,
			['lastsend' => $date4->sub(new \DateInterval('P1D'))->getTimestamp()]
		);

		$return = api_campaigns_get_campaigns_sent_before_period($date, $group1);
		$this->assertEquals([$campaign3], $return);

		$return = api_campaigns_get_campaigns_sent_before_period($date, $group2);
		$this->assertEquals([$campaign4], $return);

		$return = api_campaigns_get_campaigns_sent_before_period($date);
		$this->assertEquals([$campaign3, $campaign4], $return);
	}

	/**
	 * @return array
	 */
	public function periodProvider() {
		return [
			[new DateTime("2020-01-01"), new DateTime("2019-01-01"), new DateTime("2019-12-31"), false],
			[new DateTime("2019-01-02"), new DateTime("2019-01-01"), new DateTime("2019-12-31"), true],
			[new DateTime("2002-01-01"), new DateTime("2019-01-01"), new DateTime("2019-12-31"), false]
		];
	}

	/**
	 * @dataProvider periodProvider
	 * @param DateTime $lastSend
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param boolean  $expected
	 * @return void
	 */
	public function test_api_campaigns_get_campaigns_sent_during_period(DateTime $lastSend, DateTime $start, DateTime $end, $expected) {
		$this->purge_all_campaigns();
		$source = $this->create_new_campaign(null, 'sms', null, ['lastsend' => $lastSend->getTimestamp()]);
		$prev = clone $start;
		$next = clone $end;
		$this->create_new_campaign(null, 'phone', null, ['lastsend' => $prev->sub(new \DateInterval("P1M"))->getTimestamp()]);
		$this->create_new_campaign(null, 'phone', null, ['lastsend' => $next->add(new \DateInterval("P1M"))->getTimestamp()]);
		$return = api_campaigns_get_campaigns_lastsend_during_period($start, $end);
		if ($expected) {
			$this->assertEquals([$source], $return);
		} else {
			$this->assertEmpty($return);
		}
	}

	/**
	 * @return void
	 */
	public function test_api_campaigns_get_all_targets() {
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_SMS);
		$targets = [
			'key1' => '0412345678',
			'key2' => '0412345676',
			'key3' => '0412345675'
		];
		$this->add_campaign_targets($campaignid, $targets);

		$return = api_campaigns_get_all_targets($campaignid);
		$this->assertCount(3, $return);

		$retuned_keys = [];
		foreach ($return as $target) {
			$this->assertArrayHasKey('targetid', $target);
			$this->assertArrayHasKey('targetkey', $target);
			$this->assertArrayHasKey('status', $target);
			$this->assertArrayHasKey('nextattempt', $target);
			$this->assertArrayHasKey('errors', $target);
			$this->assertArrayHasKey('destination', $target);
			$this->assertSameEquals('READY', $target['status']);

			$return_target_key = $target['targetkey'];
			$retuned_keys[] = $target['targetkey'];
			$this->assertSameEquals($targets[$return_target_key], $target['destination']);
		}

		$this->assertSameEquals(array_keys($targets), $retuned_keys);
	}

	/**
	 * @return void
	 */
	public function test_api_campaigns_delete_target() {
		$campaignid = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE);
		$targets = [
			'targetkey1' => '0412345678',
			'targetkey2' => '0412345676',
		];
		$this->add_campaign_targets($campaignid, $targets);

		$return = api_campaigns_get_all_targets($campaignid);
		$this->assertCount(2, $return);

		api_targets_add_extradata_multiple($campaignid, 'targetkey1', ['name' => 'value', 'name2' => 'value2']);
		api_targets_add_extradata_multiple($campaignid, 'targetkey2', ['name' => 'value', 'name2' => 'value2']);

		$this->assertCount(2, api_data_merge_get_all($campaignid, 'targetkey1'));
		$this->assertCount(2, api_data_merge_get_all($campaignid, 'targetkey2'));

		foreach ($return as $target) {
			$eventid = rand(1000, 10000);
			if ($target['targetkey'] === 'targetkey1') {
				$targetToRemove = $target['targetid'];
			} else {
				$targetToStay = $target['targetid'];
			}
			$this
				->assertNotFalse(
					api_data_responses_add(
						$campaignid,
						$eventid,
						$target['targetid'],
						$target['targetkey'],
						'test1',
						'value1'
					)
				);

			$this->add_campaign_call_result(null, $target['targetid'], 'hangup');
		}

		$this->assertTrue(api_campaigns_delete_target($campaignid, 'targetkey1'));

		$this->assertFalse(api_targets_getinfo($targetToRemove));
		$this->assertNotFalse(api_targets_getinfo($targetToStay));

		$this->assertEmpty(api_data_merge_get_all($campaignid, 'targetkey1'));
		$this->assertNotEmpty(api_data_merge_get_all($campaignid, 'targetkey2'));

		$results = api_data_callresult_get_all($campaignid);

		$this->assertTrue(isset($results[$targetToStay]));
		$this->assertFalse(isset($results[$targetToRemove]));

		$this->assertCount(0, api_data_responses_getall($targetToRemove));
		$this->assertCount(1, api_data_responses_getall($targetToStay));
	}
}
