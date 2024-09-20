<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\Entities\CampaignTimingRule;
use Models\Entities\Region;
use Models\Entities\TimingDescriptor;
use Models\Entities\TimingGroup;
use Phake;
use Services\Campaign\CampaignTimingAccessor;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Repository\CampaignTimingRuleRepository;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignTimingAccessorUnitTest
 */
class CampaignTimingAccessorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var CampaignTimingRuleRepository | \Phake_IMock */
	private $timingRuleRepo;

	/** @var CampaignTimingAccessor */
	private $timingAccessor;

	/** @var CampaignSettings | \Phake_IMock */
	private $campaignSettings;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->timingRuleRepo = Phake::mock(CampaignTimingRuleRepository::class);
		$this->timingAccessor = new CampaignTimingAccessor($this->timingRuleRepo);
		$this->campaignSettings = Phake::mock(CampaignSettings::class);
	}

	/**
	 * @return void
	 */
	public function testGetRuleReturnsNullWhenThereIsNoRegion() {
		Phake::when($this->campaignSettings)->getRegion()->thenReturn(null);
		$this->assertNull($this->timingAccessor->getRule($this->campaignSettings));
	}

	/**
	 * @return void
	 */
	public function testGetRule() {
		$this->getRuleAssertions(null);
		$rule = Phake::mock(CampaignTimingRule::class);
		$this->getRuleAssertions($rule);
	}

	/**
	 * @return void
	 */
	public function testGetTimingGroup() {
		$this->getRuleAssertions(null);
		$this->assertNull($this->timingAccessor->getTimingGroup($this->campaignSettings));
		$rule = Phake::mock(CampaignTimingRule::class);
		$timingGroup = Phake::mock(TimingGroup::class);
		Phake::when($rule)->getTimingGroup()->thenReturn($timingGroup);
		$this->getRuleAssertions($rule);
		$this->assertEquals($timingGroup, $this->timingAccessor->getTimingGroup($this->campaignSettings));
	}

	/**
	 * @return void
	 */
	public function testGetTimingPeriods() {
		$this->getRuleAssertions(null);
		$periods = $this->timingAccessor->getTimingPeriods($this->campaignSettings);
		$this->assertInstanceOf(
			ArrayCollection::class,
			$periods
		);
		$this->assertSameEquals(0, $periods->count());
		$rule = Phake::mock(CampaignTimingRule::class);
		$timingGroup = Phake::mock(TimingGroup::class);
		$periods = Phake::mock(ArrayCollection::class);
		Phake::when($timingGroup)->getTimingPeriods()->thenReturn($periods);
		Phake::when($rule)->getTimingGroup()->thenReturn($timingGroup);
		$this->getRuleAssertions($rule);
		$this->assertSameEquals($periods, $this->timingAccessor->getTimingPeriods($this->campaignSettings));
	}

	/**
	 * @param CampaignTimingRule|null $rule
	 * @return void
	 */
	private function getRuleAssertions(CampaignTimingRule $rule = null) {
		$region = Phake::mock(Region::class);
		Phake::when($this->campaignSettings)->getRegion()->thenReturn($region);
		$classification = Phake::mock(CampaignClassificationEnum::class);
		Phake::when($this->campaignSettings)->getClassificationEnum()->thenReturn($classification);
		$timingDesc = Phake::mock(TimingDescriptor::class);
		Phake::when($this->campaignSettings)->getTimingDescriptor()->thenReturn($timingDesc);

		Phake::when($this->timingRuleRepo)
			->getTimingRules($classification, $timingDesc, $region)
			->thenReturn($rule ? [$rule] : []);
		if (!$rule) {
			$this->assertNull($this->timingAccessor->getRule($this->campaignSettings));
		} else {
			$this->assertEquals($rule, $this->timingAccessor->getRule($this->campaignSettings));
		}
	}
}
