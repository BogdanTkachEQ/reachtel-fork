<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Builder;

use Models\CampaignSettings;
use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Container\ContainerAccessor;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;

/**
 * Class CampaignSettingsDirectorModuleTest
 */
class CampaignSettingsDirectorModuleTest extends AbstractDatabasePhpunitModuleTest
{
	use CampaignModuleHelper;

	/**
	 * Test if campaign building CampaignSettings work with default settings
	 * @return void
	 */
	public function testBuildCampaignSettings() {
		$campaignId = $this->create_new_campaign(null, CAMPAIGN_TYPE_VOICE);
		$settingsDirector = ContainerAccessor::getContainer()
			->get(CampaignSettingsDirector::class);

		$settings = $settingsDirector->buildCampaignSettings($campaignId);
		$this->assertInstanceOf(CampaignSettings::class, $settings);
	}
}
