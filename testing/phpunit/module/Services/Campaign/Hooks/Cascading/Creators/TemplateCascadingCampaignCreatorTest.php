<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Services\Campaign\Hooks\Cascading;

use Exception;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use Services\Campaign\Hooks\Cascading\Creators\InitialTemplateBasedCascadingCampaignCreator;
use Services\Exceptions\CampaignValidationException;
use Services\Exceptions\Campaign\CampaignCreationException;
use Services\Hooks\Exceptions\TargetCreationException;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Class TemplateCascadingCampaignCreatorTest
 */
class TemplateCascadingCampaignCreatorTest extends AbstractDatabasePhpunitModuleTest {
	use CampaignModuleHelper;
	use UserModuleHelper;

	/**
	 * @var
	 */
	private $initialTemplateCampaignId;

	/**
	 * @var array
	 */
	private $targetData = [
		[
			'CONTACT_NO_1' => "0422222001",
			'CONTACT_NO_2' => "0422222002",
			'CONTACT_NO_3' => "0422222003",
			'CONTACT_NO_4' => "0422222004",

		],
		[
			'CONTACT_NO_1' => "0422222006",
			'CONTACT_NO_2' => "0422222007",
			'CONTACT_NO_3' => "0422222008",
			'CONTACT_NO_4' => "",
		],
		[
			'CONTACT_NO_1' => "0422222010",
			'CONTACT_NO_2' => "0422222011",
			'CONTACT_NO_3' => "",
			'CONTACT_NO_4' => "",
		],
	];

	/**
	 * Setup the base campaign templates
	 * @return void
	 */
	public function createTemplates() {
		$this->purge_all_campaigns();
		$user = $this->create_new_user("test-user");

		for ($i = 1; $i <= 6; $i++) {
			$campaignSettings = [
				'cascadingcampaign' => 1,
				'defaultdestination1' => "CONTACT_NO_$i"
			];

			$campaignName = "cascade-template-campaign-step-$i";
			$campaignId = $this->create_new_campaign($campaignName, "phone", $user, $campaignSettings);
			if ($i === 1) {
				$this->initialTemplateCampaignId = $campaignId;
				api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_CASCADING_BASE_TEMPLATE, 1);
			}
			if ($i <= 5) {
				api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE, "cascade-template-campaign-step-" . ($i + 1));
			}
		}
	}

	/**
	 * @return void
	 */
	public function setUp() {
		$this->initialTemplateCampaignId = null;
	}

	/**
	 * @return void
	 */
	public function tearDown() {
		$this->purge_all_campaigns();
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws Exception Could not create campaign.
	 */
	public function testSetupNextCampaignAllIterations() {
		$this->createTemplates();
		$inititalCloner = (new CascadingCampaignCreatorFactory())->makeCreator($this->initialTemplateCampaignId, "real-campaign");
		$lastCampaignId = $inititalCloner->setupNextCampaign();
		foreach ($this->targetData as $targets) {
			$targetId = api_targets_add_single($lastCampaignId, $targets['CONTACT_NO_1'], uniqid("key-", true), null, $targets);
			api_targets_updatestatus($targetId, "ABANDONED");
		}

		for ($currentIteration = 1; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}

			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator($lastCampaignId);
			$clonedCampaignId = $cloner->setupNextCampaign();

			// Set the campaign's targets to abandoned so they'll copy for the next clone
			$targets = api_targets_listall($clonedCampaignId);
			foreach ($targets as $targetId => $dest) {
				api_targets_updatestatus($targetId, "ABANDONED");
			}
			$this->assertNotEquals($lastCampaignId, $clonedCampaignId);
			$lastCampaignId = $clonedCampaignId;

			$this->assertCount(3, api_targets_listall($clonedCampaignId));
			$newSettings = api_campaigns_setting_getall($clonedCampaignId);
			$this->assertEquals("real-campaign-step-" . ($currentIteration + 1), $newSettings['name']);
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 */
	public function testSetupNextCampaignNoNumbers() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;

		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$lastCampaignId = $clonedCampaignId;
			$targets = api_targets_listall($clonedCampaignId);
			$this->assertCount(0, $targets);
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 */
	public function testsetupNextCampaignSomeNumbers() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;
		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			// Setup the base campaign with targets
			if ($currentIteration === 0) {
				foreach ($this->targetData as $targets) {
					api_targets_add_single($clonedCampaignId, $targets['CONTACT_NO_1'], uniqid("key-", true), null, $targets);
				}
			}

			// Set the campaign's targets to abandoned so they'll copy for the next clone
			$targets = api_targets_listall($clonedCampaignId);
			foreach ($targets as $targetId => $dest) {
				api_targets_updatestatus($targetId, "ABANDONED");
				break;
			}

			$this->assertNotEquals($lastCampaignId, $clonedCampaignId);
			$lastCampaignId = $clonedCampaignId;

			$newSettings = api_campaigns_setting_getall($clonedCampaignId);
			$this->assertEquals("real-campaign-step-" . ($currentIteration + 1), $newSettings['name']);

			if ($currentIteration > 0) {
				$targets = api_targets_listall($clonedCampaignId);
				$this->assertCount(1, $targets);
			}
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testSetupNextCampaignUsingExistingCascades() {

		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;
		$initialCampaignId = null;
		$clonedCampaignIds = [];
		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$lastCampaignId = $clonedCampaignId;
			$clonedCampaignIds[] = $clonedCampaignId;
			if ($currentIteration === 0) {
				$initialCampaignId = $lastCampaignId;
			}
		}

		$lastCampaignId = $initialCampaignId;
		for ($currentIteration = 1; $currentIteration <= 5; $currentIteration++) {
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator($lastCampaignId);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$this->assertTrue(in_array($clonedCampaignId, $clonedCampaignIds));
			$lastCampaignId = $clonedCampaignId;
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testSetupInitialCampaignUsingExistingCampaignDifferentTemplate() {
		$this->createTemplates();
		$this->create_new_campaign("real-campaign-" . InitialTemplateBasedCascadingCampaignCreator::INITIAL_CASCADING_NAME_SUFFIX, "phone");
		$cloner = (new CascadingCampaignCreatorFactory())->makeCreator($this->initialTemplateCampaignId, "real-campaign");
		$this->expectException(CampaignValidationException::class);
		$cloner->setupNextCampaign();
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testSetupNextCampaignUsingExistingCampaignDifferentTemplate() {
		$this->createTemplates();
		$this->create_new_campaign("real-campaign-" . InitialTemplateBasedCascadingCampaignCreator::CASCADING_NAME_SUFFIX . "2", "phone");
		$cloner = (new CascadingCampaignCreatorFactory())->makeCreator($this->initialTemplateCampaignId, "real-campaign");
		$cloner = (new CascadingCampaignCreatorFactory())->makeCreator($cloner->setupNextCampaign());
		$this->expectException(CampaignValidationException::class);
		$cloner->setupNextCampaign();
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed to create.
	 */
	public function testSetupNextCampaignInitialNameWithSuffix() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;
		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign-01-step-1" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$lastCampaignId = $clonedCampaignId;
			if ($currentIteration) {
				$this->assertEquals("real-campaign-01-step-{$currentIteration}", $cloner->getCurrentCampaignName());
				$this->assertEquals("real-campaign-01-step-" . ($currentIteration + 1), $cloner->getNextCampaignName());
				$this->assertEquals($currentIteration, $cloner->getCurrentCampaignIteration());
				$this->assertEquals(($currentIteration + 1), $cloner->getNextCampaignIteration());
			} else {
				$this->assertEquals("real-campaign-01-step-1", $cloner->getCurrentCampaignName());
				$this->assertEquals("real-campaign-01-step-2", $cloner->getNextCampaignName());
			}
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testSetupNextCampaignInitialNameWithoutSuffix() {

		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;
		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$lastCampaignId = $clonedCampaignId;
			if ($currentIteration) {
				$this->assertEquals("real-campaign-step-{$currentIteration}", $cloner->getCurrentCampaignName());
				$this->assertEquals("real-campaign-step-" . ($currentIteration + 1), $cloner->getNextCampaignName());

				$this->assertEquals($currentIteration, $cloner->getCurrentCampaignIteration());
				$this->assertEquals(($currentIteration + 1), $cloner->getNextCampaignIteration());
			} else {
				$this->assertEquals("real-campaign-step-1", $cloner->getCurrentCampaignName());
				$this->assertEquals("real-campaign-step-2", $cloner->getNextCampaignName());
			}
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testCreatePrevCampaignInitialNameWithoutSuffix() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;
		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$lastCampaignId = $clonedCampaignId;
			if ($currentIteration > 1) {
				$this->assertEquals(
					"real-campaign-step-" . ($currentIteration - 1),
					$cloner->getPreviousCampaignName()
				);
			} else {
				$this->assertFalse($cloner->getPreviousCampaignName());
			}
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 * @throws CampaignCreationException Campaign failed.
	 */
	public function testCreateFirstCampaignInitialNameWithoutSuffix() {

		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;
		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();
			$lastCampaignId = $clonedCampaignId;
			$this->assertEquals("real-campaign-step-1", $cloner->getFirstCampaignName());
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 */
	public function testSetupNextCampaignIterationSettings() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;

		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign();

			$this->assertEquals($currentIteration + 1, api_campaigns_setting_getsingle($clonedCampaignId, CAMPAIGN_SETTING_CASCADING_ITERATION));

			if ($currentIteration > 0) {
				$this->assertEquals(
					$lastCampaignId,
					api_campaigns_setting_getsingle($clonedCampaignId, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID)
				);
			} else {
				$this->assertFalse(api_campaigns_setting_getsingle($clonedCampaignId, CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID));
			}
			$lastCampaignId = $clonedCampaignId;
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 */
	public function testSetupNextCampaignActiveSettings() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;

		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign(true);
			$lastCampaignId = $clonedCampaignId;
			$this->assertEquals(CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE, api_campaigns_setting_getsingle($clonedCampaignId, CAMPAIGN_SETTING_STATUS));
		}
	}

	/**
	 * @return void
	 * @throws TargetCreationException Could not create target.
	 */
	public function testsetupNextCampaignNotActiveSettings() {
		$this->createTemplates();
		$lastCampaignId = $this->initialTemplateCampaignId;

		for ($currentIteration = 0; $currentIteration <= 5; $currentIteration++) {
			if (!api_campaigns_setting_getsingle($lastCampaignId, CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE)) {
				break;
			}
			$cloner = (new CascadingCampaignCreatorFactory())->makeCreator(
				$lastCampaignId,
				!$currentIteration ? "real-campaign" : null
			);
			$clonedCampaignId = $cloner->setupNextCampaign(false);
			$lastCampaignId = $clonedCampaignId;
			$this->assertEquals(CAMPAIGN_SETTING_STATUS_VALUE_DISABLED, api_campaigns_setting_getsingle($clonedCampaignId, CAMPAIGN_SETTING_STATUS));
		}
	}
}
