<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Campaign\Archiver;

use Services\ActivityLogger;
use Services\Campaign\Archiver\ArchiverEnum;
use Services\Campaign\Archiver\BulkTargetArchiver;
use testing\module\AbstractDatabasePhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;

/**
 * Class BulkTargetArchiverTest
 */
class BulkTargetArchiverTest extends AbstractDatabasePhpunitModuleTest {
	use CampaignModuleHelper;

	/**
	 * @return void
	 */
	public function testArchiveSmallWorkingLimit() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(1, $activity);
		$this->assertEquals(4, $archiver->archiveCampaign($sourceCampaign, true));
		$this->assertCount(4, api_targets_get_archive($sourceCampaign));
		$this->assertCount(0, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(1, api_campaigns_get_all_targets($sourceCampaign1));
	}

	/**
	 * @return void
	 */
	public function testArchiveBigWorkingLimit() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(100000, $activity);
		$this->assertEquals(4, $archiver->archiveCampaign($sourceCampaign, true));
		$this->assertCount(4, api_targets_get_archive($sourceCampaign));
		$this->assertCount(0, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(1, api_campaigns_get_all_targets($sourceCampaign1));
	}

	/**
	 * @return void
	 */
	public function testDeArchiveBigWorkingLimit() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(100000, $activity);
		$archiver->archiveCampaign($sourceCampaign, true);
		$this->assertEquals(4, $archiver->deArchiveCampaign($sourceCampaign, true));

		$this->assertCount(0, api_targets_get_archive($sourceCampaign));
		$this->assertCount(4, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(1, api_campaigns_get_all_targets($sourceCampaign1));
	}

	/**
	 * @return void
	 */
	public function testArchiveManualDelete() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(100000, $activity);
		$archiver->archiveCampaign($sourceCampaign, false);
		$this->assertCount(4, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(4, api_targets_get_archive($sourceCampaign));
	}

	/**
	 * @return void
	 */
	public function testArchiveAutoDelete() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(100000, $activity);
		$archiver->archiveCampaign($sourceCampaign, true);
		$this->assertCount(0, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(4, api_targets_get_archive($sourceCampaign));
	}

	/**
	 * @return void
	 */
	public function testDeArchiveManualDelete() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(100000, $activity);
		$archiver->archiveCampaign($sourceCampaign, true);
		$this->assertEquals(4, $archiver->deArchiveCampaign($sourceCampaign));
		$this->assertCount(4, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(4, api_targets_get_archive($sourceCampaign));
	}

	/**
	 * @return void
	 */
	public function testDeArchiveAutoDelete() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");
		$targets = ['0711112222', '0722223333', '0744445555', '0766667777'];
		$this->add_campaign_targets($sourceCampaign, $targets);

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(100000, $activity);
		$archiver->archiveCampaign($sourceCampaign, true);
		$this->assertEquals(4, $archiver->deArchiveCampaign($sourceCampaign, true));
		$this->assertCount(4, api_campaigns_get_all_targets($sourceCampaign));
		$this->assertCount(0, api_targets_get_archive($sourceCampaign));
	}

	/**
	 * @return void
	 */
	public function testArchiveEmptyCampaign() {
		$sourceCampaign = $this->create_new_campaign(null, "phone");

		$sourceCampaign1 = $this->create_new_campaign(null, "phone");
		$this->add_campaign_targets($sourceCampaign1, ['0711112222']);

		$activity = \Phake::mock(ActivityLogger::class);
		$archiver = new BulkTargetArchiver(1, $activity);
		$this->assertEquals(0, $archiver->archiveCampaign($sourceCampaign));
		$this->assertCount(1, api_campaigns_get_all_targets($sourceCampaign1));
	}
}
