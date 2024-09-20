<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Cloner;

use Services\Exceptions\CampaignValidationException;
use Services\Exceptions\Campaign\CampaignCreationException;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;
use testing\module\helpers\UserModuleHelper;

/**
 * Class GenericCampaignClonerTest
 */
class GenericCampaignClonerTest extends AbstractDatabasePhpunitModuleTest {
	use CampaignModuleHelper;
	use UserModuleHelper;

	/**
	 * @return void
	 */
	public function testCloneCampaign() {
		$user = $this->create_new_user("test-user");
		$sourceCampaign = $this->create_new_campaign("source-campaign", "phone", $user);
		$this->assertNotFalse($sourceCampaign);

		$cloner = new GenericCampaignCloner();
		$clonedCampaignId = $cloner->cloneCampaign($sourceCampaign, "cloned-campaign");
		$this->assertNotFalse($clonedCampaignId);

		$campaignDetails = api_campaigns_setting_getall($clonedCampaignId);
		$this->assertEquals($campaignDetails['name'], 'cloned-campaign');
		$this->assertEquals($campaignDetails['type'], 'phone');
		$this->purge_all_campaigns();
	}
}
