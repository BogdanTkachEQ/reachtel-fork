<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Archiver;

use Services\ActivityLogger;
use Services\Campaign\Archiver\BulkTargetArchiver;
use Services\Exceptions\Targets\TargetArchiveException;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class BulkTargetArchiverUnitTest
 */
class BulkTargetArchiverUnitTest extends AbstractPhpunitUnitTest {

	/**
	 * @return array
	 */
	public function chunkProvider() {
		return [[1, 1], [1, 1], [1, 10], [10, 1], [10, 10], [10, 100], [100, 1], [100, 100], [100, 1005], [1000, 1],
				[1000, 5], [1000, 1000], [1000, 10000], [10000, 1], [100000, 99], [100000, 100000]];
	}

	/**
	 * @dataProvider chunkProvider
	 * @param integer $targetCount
	 * @param integer $workingLimit
	 * @return void
	 */
	public function testArchiveChunk($targetCount, $workingLimit) {

		$archiver = new BulkTargetArchiver($workingLimit, \Phake::mock(ActivityLogger::class));

		$this->mock_function_value('api_targets_count_campaign_total', $targetCount);
		$this->listen_mocked_function('api_targets_archive');
		$this->mock_function_value('api_targets_archive', true);

		$this->assertEquals($targetCount, $archiver->archiveCampaign(1));
		$this->assertListenMockFunctionHasBeenCalled(
			'api_targets_archive',
			true,
			ceil(($targetCount / $workingLimit))
		);

		$this->remove_mocked_functions();
	}

	/**
	 * @return void
	 */
	public function testException() {
		$archiver = new BulkTargetArchiver(10, \Phake::mock(ActivityLogger::class));

		$this->mock_function_value('api_targets_count_campaign_total', 10);
		$this->mock_function_value('api_targets_archive', false);

		$this->expectException(TargetArchiveException::class);
		$archiver->archiveCampaign(1);

		$this->remove_mocked_functions();
	}

	/**
	 * @return void
	 */
	public function testCampaignEmpty() {
		$archiver = new BulkTargetArchiver(10, \Phake::mock(ActivityLogger::class));

		$this->mock_function_value('api_targets_count_campaign_total', 0);
		$this->assertEquals(0, $archiver->archiveCampaign(1));

		$this->remove_mocked_functions();
	}
}
