<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models\Entities;

use Models\Entities\CampaignTimingRule;
use Models\Entities\TimingDescriptor;
use Models\Entities\TimingGroup;
use testing\unit\AbstractModelTestCase;

/**
 * Class CampaignTimingRule
 */
class CampaignTimingRuleUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 123,
			'timingDescriptor' => \Phake::mock(TimingDescriptor::class),
			'timingGroup' => \Phake::mock(TimingGroup::class)
		];
	}

	/**
	 * @return mixed
	 */
	protected function getObject() {
		return new CampaignTimingRule();
	}
}
