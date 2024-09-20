<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Hooks\Cascading;

use Exception;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\Hooks\Cascading\Targets\CascadingSingleDestinationCampaignTargetCopier;
use Services\Exceptions\CampaignValidationException;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Hooks\Exceptions\TargetCreationException;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Class CascadingCampaignTargetCopierTest
 */
class CascadingCampaignTargetCopierTest extends AbstractDatabasePhpunitModuleTest {
	use CampaignModuleHelper;
	use UserModuleHelper;

	/**
	 * @var false|int
	 */
	private $baseCampaignId;

	/**
	 * @var array
	 */
	private $targetData = [
		[
			'CONTACT_NO_1' => "0422222001",
			'CONTACT_NO_2' => "0422222002",
			'CONTACT_NO_3' => "0422222003",
			'CONTACT_NO_4' => "0422222004",
			'CONTACT_NO_5' => "0422222005",

		],
		[
			'CONTACT_NO_1' => "0422222006",
			'CONTACT_NO_2' => "0422222007",
			'CONTACT_NO_3' => "0422222008",
			'CONTACT_NO_4' => "0422222009",
			'CONTACT_NO_5' => "",
		],
		[
			'CONTACT_NO_1' => "0422222010",
			'CONTACT_NO_2' => "0422222011",
			'CONTACT_NO_3' => "",
			'CONTACT_NO_4' => "",
			'CONTACT_NO_5' => "",
		],
	];

	/**
	 * Setup the base campaign
	 * @return void
	 * @throws Exception Could not create campaign.
	 */
	public function setUp() {
		$this->purge_all_campaigns();
		$user = $this->create_new_user("test-user");
		$campaignSettings = [
			'cascadingcampaign' => 1,
			'defaultdestination1' => "CONTACT_NO_1",
		];

		$this->baseCampaignId = $this->create_new_campaign("source-campaign", "phone", $user, $campaignSettings);
		foreach ($this->targetData as $target) {
			api_targets_add_single($this->baseCampaignId, $target['CONTACT_NO_1'], uniqid("key-", true), null, $target);
		}
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->purge_all_campaigns();
	}

	/**
	 * @return array
	 */
	public function IterationDataProvider() {
		return [
			[2, ['0422222002', '0422222007', '0422222011']],
			[3, ['0422222003', '0422222008', '0422222010']],
			[4, ['0422222004', '0422222009', '0422222010']],
			[5, ['0422222005', '0422222006', '0422222010']]
		];
	}

	/**
	 * @dataProvider IterationDataProvider
	 * @param integer $iteration
	 * @param string  $expectedNumbers
	 * @return void
	 * @throws CampaignValidationException Could not create campaign.
	 * @throws TargetCreationException Target creation failed.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testCopy($iteration, $expectedNumbers) {
		$cloner = new GenericCampaignCloner();
		$lastCampaignId = $this->baseCampaignId;
		for ($z = 1; $z <= $iteration; $z++) {
			$clonedCampaignId = $cloner->cloneCampaign($lastCampaignId, "source-campaign-$z");
			api_campaigns_setting_set($clonedCampaignId, "defaultdestination1", "CONTACT_NO_" . ($z));

			$targetCopier = new CascadingSingleDestinationCampaignTargetCopier($iteration, $lastCampaignId, $this->baseCampaignId);
			$targets = api_targets_listall($lastCampaignId);
			$lastCampaignId = $clonedCampaignId;
			$i = 0;
			foreach ($targets as $targetId => $dest) {
				api_targets_updatestatus($targetId, "ABANDONED");
				$newTarget = $targetCopier->copy($targetId, $clonedCampaignId, 4);
				$this->assertNotFalse($newTarget);
				$i++;
			}
			$this->assertCount(count($targets), api_targets_listall($clonedCampaignId));
		}

		$targets = api_targets_listall($lastCampaignId);
		$this->assertEmpty(array_diff($expectedNumbers, $targets));
		$this->assertCount(count($expectedNumbers), $targets);
	}
}
