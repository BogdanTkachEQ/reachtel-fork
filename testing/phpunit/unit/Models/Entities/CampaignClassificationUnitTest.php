<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Models\Entities\CampaignClassification;
use Services\Campaign\Classification\CampaignClassificationEnum;
use testing\unit\AbstractModelTestCase;

/**
 * Class CampaignClassificationUnitTest
 */
class CampaignClassificationUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 123,
			'name' => \Phake::mock(CampaignClassificationEnum::class)
		];
	}

	/**
	 * @return CampaignClassification
	 */
	protected function getObject() {
		return new CampaignClassification();
	}
}
