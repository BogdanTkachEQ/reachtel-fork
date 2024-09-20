<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\CampaignType;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Phake;
use Services\Campaign\Builders\CampaignSettingsBuilder;
use Services\Campaign\Classification\CampaignClassificationEnum;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignSettingsBuilder
 */
class CampaignSettingsBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/** @var CampaignSettingsBuilder */
	private $campaignSettingsBuilder;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->campaignSettingsBuilder = new CampaignSettingsBuilder();
	}

	/**
	 * @return void
	 */
	public function testGetCampaignSettings() {
		$this->assertDefaults();
		$id = 123;
		$type = Phake::mock(CampaignType::class);
		$specificTimes = Phake::mock(ArrayCollection::class);
		$recurringTimes = Phake::mock(ArrayCollection::class);
		$classification = Phake::mock(CampaignClassificationEnum::class);
		$region = Phake::mock(Region::class);
		$timingDesc = Phake::mock(TimingDescriptor::class);
		$timeZone = Phake::mock(\DateTimeZone::class);
		$callerIdWithHeld = true;
		$this
			->campaignSettingsBuilder
			->setId($id)
			->setType($type)
			->setSpecificTimes($specificTimes)
			->setRecurringTimes($recurringTimes)
			->setClassificationEnum($classification)
			->setRegion($region)
			->setTimingDescriptor($timingDesc)
			->setTimeZone($timeZone)
			->setCallerIdWithHeld($callerIdWithHeld);

		$campaignSettings = $this->campaignSettingsBuilder->getCampaignSettings();
		$this->assertSameEquals($id, $campaignSettings->getId());
		$this->assertSameEquals($type, $campaignSettings->getType());
		$this->assertSameEquals($specificTimes, $campaignSettings->getSpecificTimes());
		$this->assertSameEquals($recurringTimes, $campaignSettings->getRecurringTimes());
		$this->assertSameEquals($classification, $campaignSettings->getClassificationEnum());
		$this->assertSameEquals($region, $campaignSettings->getRegion());
		$this->assertSameEquals($timingDesc, $campaignSettings->getTimingDescriptor());
		$this->assertSameEquals($timeZone, $campaignSettings->getTimeZone());
		$this->assertSameEquals($callerIdWithHeld, $campaignSettings->isCallerIdWithHeld());
		$this->campaignSettingsBuilder->reset();
		$this->assertDefaults();
	}

	/**
	 * @return void
	 */
	private function assertDefaults() {
		$campaignSettings = $this->campaignSettingsBuilder->getCampaignSettings();
		$this->assertInstanceOf(CampaignSettings::class, $campaignSettings);
		$this->assertNull($campaignSettings->getId());
		$this->assertInstanceOf(ArrayCollection::class, $campaignSettings->getSpecificTimes());
		$this->assertInstanceOf(ArrayCollection::class, $campaignSettings->getRecurringTimes());
		$this->assertSameEquals(0, $campaignSettings->getSpecificTimes()->count());
		$this->assertSameEquals(0, $campaignSettings->getRecurringTimes()->count());
		$this->assertNull($campaignSettings->getTimeZone());
		$this->assertNull($campaignSettings->getType());
		$this->assertNull($campaignSettings->getClassificationEnum());
		$this->assertNull($campaignSettings->getTimingDescriptor());
		$this->assertFalse($campaignSettings->isCallerIdWithHeld());
		$this->assertNull($campaignSettings->getRegion());
	}
}
