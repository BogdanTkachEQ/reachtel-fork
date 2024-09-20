<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Models\Entities\QueueItem;
use Services\Queue\QueueProcessTypeEnum;
use testing\unit\AbstractModelTestCase;

/**
 * Class QueueItemTest
 */
class QueueItemTest extends AbstractModelTestCase {

	/**
	 * @return void
	 */
	public function testDeriveFriendlyStatus() {
		$qi = new QueueItem();
		$qi->setIsRunning(true);
		$this->assertEquals("Processing", $qi->deriveFriendlyStatus());

		$qi->setIsRunning(false)
			->setCanRun(true)
			->setHasRun(false);

		$this->assertEquals("Waiting to process", $qi->deriveFriendlyStatus());

		$qi->setIsRunning(false)
			->setHasRun(true)
			->setReturnCode(1);

		$this->assertEquals("Complete", $qi->deriveFriendlyStatus());

		$qi->setIsRunning(false)
			->setHasRun(true)
			->setReturnCode(-1);

		$this->assertEquals("Error", $qi->deriveFriendlyStatus());
	}

	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 567,
			'processType' => QueueProcessTypeEnum::FILEUPLOAD(),
			'campaignId' => 1,
			'userId' => 2,
			'hasRun' => true,
			'canRun' => false,
			'returnText' => 'test123',
			'returnCode' => '-1'
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new QueueItem();
	}
}
