<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Validators\Disclaimers;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\Day;
use Models\Entities\Country;
use Models\Entities\Region;
use Models\Entities\TimingPeriod;
use Services\Campaign\CampaignTimingAccessor;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Campaign\Validators\Disclaimers\TimingDisclaimerProvider;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class TimingDisclaimerProviderTest
 */
class TimingDisclaimerProviderTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function discDataProvider() {
		return [
			[
				Day::MONDAY(),
				Day::WEDNESDAY(),
				"AU",
				"Australia/Brisbane",
				new \DateTime("last monday 10:40:00"),
				new \DateTime("last wednesday 12:00:00"),
				new \DateTime("last wednesday 12:00:00"),
				new \DateTime("last wednesday 15:00:00"),
				CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH()
			],
			[
				Day::SATURDAY(),
				Day::SUNDAY(),
				"AU",
				"Australia/Sydney",
				new \DateTime("last saturday 10:40:00"),
				new \DateTime("last saturday 12:00:00"),
				new \DateTime("last sunday 12:00:00"),
				new \DateTime("last sunday 23:00:00"),
				CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT()
			]
		];
	}

	/**
	 * @dataProvider discDataProvider
	 * @param Day                        $day1
	 * @param Day                        $day2
	 * @param string                     $countryName
	 * @param string                     $tz
	 * @param \DateTime                  $start
	 * @param \DateTime                  $end
	 * @param \DateTime                  $start2
	 * @param \DateTime                  $end2
	 * @param CampaignClassificationEnum $classificationEnum
	 * @return void
	 */
	public function testGetDisclaimer(
		Day $day1,
		Day $day2,
		$countryName,
		$tz,
		\DateTime $start,
		\DateTime $end,
		\DateTime $start2,
		\DateTime $end2,
		CampaignClassificationEnum $classificationEnum
	) {
		$campaignSettings = \Phake::mock(CampaignSettings::class);
		$region = \Phake::mock(Region::class);
		$country = \Phake::mock(Country::class);
		\Phake::when($country)->getShortName()->thenReturn($countryName);
		\Phake::when($region)->getCountry()->thenReturn($country);
		\Phake::when($campaignSettings)->getRegion()->thenReturn($region);
		\Phake::when($campaignSettings)->getClassificationEnum()->thenReturn($classificationEnum);
		\Phake::when($campaignSettings)->getTimeZone()->thenReturn(new \DateTimeZone($tz));

		$accessor = \Phake::mock(CampaignTimingAccessor::class);
		$periodOne = \Phake::mock(TimingPeriod::class);
		\Phake::when($periodOne)->getDay()->thenReturn($day1);
		\Phake::when($periodOne)->getStart()->thenReturn($start);
		\Phake::when($periodOne)->getEnd()->thenReturn($end);
		$periodTwo = \Phake::mock(TimingPeriod::class);
		\Phake::when($periodTwo)->getDay()->thenReturn($day2);
		\Phake::when($periodTwo)->getStart()->thenReturn($start2);
		\Phake::when($periodTwo)->getEnd()->thenReturn($end2);
		$collection = new ArrayCollection([$periodOne, $periodTwo]);
		\Phake::when($accessor)->getTimingPeriods(\Phake::anyParameters())->thenReturn($collection);

		$disclaimer = new TimingDisclaimerProvider($accessor);
		$disclaimer->setCampaignSettings($campaignSettings);
		$disclaimerText = $disclaimer->getDisclaimer();

		$this->assertContains($countryName . " " . $classificationEnum->getValue(), $disclaimerText);
		$this->assertContains(
			$day1->getName() . ": " . $start->format("h:i A") . " to " . $end->format("h:i A"),
			$disclaimerText
		);
		$this->assertContains(
			$day2->getName() . ": " . $start2->format("h:i A") . " to " . $end2->format("h:i A"),
			$disclaimerText
		);
		$this->assertContains($tz, $disclaimerText);
	}
}
