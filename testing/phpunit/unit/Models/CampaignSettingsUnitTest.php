<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\CampaignType;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Phake;
use Services\Campaign\Classification\CampaignClassificationEnum;
use testing\unit\AbstractModelTestCase;

/**
 * Class CampaignSettingsUnitTest
 */
class CampaignSettingsUnitTest extends AbstractModelTestCase
{
	/**
	 * @return array
	 */
	protected function getData() {
		return [
			'id' => 12323,
			'type' => Phake::mock(CampaignType::class),
			'specificTimes' => Phake::mock(ArrayCollection::class),
			'recurringTimes' => Phake::mock(ArrayCollection::class),
			'classificationEnum' => Phake::mock(CampaignClassificationEnum::class),
			'region' => Phake::mock(Region::class),
			'timingDescriptor' => Phake::mock(TimingDescriptor::class),
			'timeZone' => Phake::mock(\DateTimeZone::class),
			'callerIdWithHeld' => true
		];
	}

	/**
	 * @return CampaignSettings
	 */
	protected function getObject() {
		return new CampaignSettings();
	}

	/**
	 * @return void
	 */
	public function testDefaults() {
		$this->assertInstanceOf(ArrayCollection::class, $this->getObject()->getSpecificTimes());
		$this->assertInstanceOf(ArrayCollection::class, $this->getObject()->getRecurringTimes());
		$this->assertFalse($this->getObject()->isCallerIdWithHeld());
	}
}
