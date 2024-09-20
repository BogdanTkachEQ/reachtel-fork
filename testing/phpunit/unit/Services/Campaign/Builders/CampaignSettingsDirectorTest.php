<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Models\CampaignType;
use Models\Entities\Country;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Services\Campaign\Builders\CampaignSettingsBuilder;
use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\Builders\RecurringTimesDirector;
use Services\Campaign\Builders\SpecificTimesDirector;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Repository\CampaignTimingRuleRepository;
use Services\Repository\CountryRepository;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignSettingsDirectorTest
 */
class CampaignSettingsDirectorTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testBuildCampaignSettings() {
		$classification = CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH();
		$campaignType = CampaignType::PHONE();
		$this->mock_function_value(
			'api_campaigns_setting_get_multi_byitem',
			[
				CAMPAIGN_SETTING_REGION => "AU",
				CAMPAIGN_SETTING_TYPE => $campaignType->getValue(),
				CAMPAIGN_SETTING_WITHHOLD_CALLER_ID => "on"
			]
		);

		$this->mock_function_value(
			'api_campaigns_getclassification',
			$classification->getValue()
		);

		$this->mock_function_value('api_campaigns_gettimezone', new \DateTimeZone('Australia/Brisbane'));

		$builder = new CampaignSettingsBuilder();
		$specificTimes = \Phake::mock(SpecificTimesDirector::class);
		\Phake::when($specificTimes)->buildFromCampaignId(\Phake::anyParameters())->thenReturn(\Phake::mock(ArrayCollection::class));
		$recurringTimes = \Phake::mock(RecurringTimesDirector::class);
		\Phake::when($recurringTimes)->buildFromCampaignId(\Phake::anyParameters())->thenReturn(\Phake::mock(ArrayCollection::class));

		$em = \Phake::mock(EntityManager::class);
		$cr = \Phake::mock(CountryRepository::class);
		$country = \Phake::mock(Country::class);

		$region = \Phake::mock(Region::class);
		\Phake::when($region)->getName()->thenReturn("AU");
		\Phake::when($country)->getRegions()->thenReturn(new ArrayCollection([$region]));
		\Phake::when($cr)->findByShortName(\Phake::anyParameters())->thenReturn($country);
		\Phake::when($em)->getRepository(Country::class)->thenReturn($cr);
		$tr = \Phake::mock(CampaignTimingRuleRepository::class);
		\Phake::when($em)->getRepository(TimingDescriptor::class)->thenReturn($tr);
		\Phake::when($tr)->find(\Phake::anyParameters())->thenReturn(new TimingDescriptor());

		$director = new CampaignSettingsDirector($builder, $specificTimes, $recurringTimes, $em);
		$settings = $director->buildCampaignSettings(1);

		\Phake::verify($specificTimes, \Phake::times(1))->buildFromCampaignId(1);
		\Phake::verify($recurringTimes, \Phake::times(1))->buildFromCampaignId(1);

		$this->assertEquals(1, $settings->getId());
		$this->assertEquals($classification, $settings->getClassificationEnum());
		$this->assertEquals($campaignType, $settings->getType());
		$this->assertTrue($settings->isCallerIdWithHeld());
		$this->assertEquals("AU", $settings->getRegion()->getName());
		$this->assertEquals(new \DateTimeZone('Australia/Brisbane'), $settings->getTimeZone());
	}
}
