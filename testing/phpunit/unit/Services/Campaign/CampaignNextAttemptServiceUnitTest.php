<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Models\Entities\TimingPeriod;
use Phake;
use Services\Campaign\CampaignNextAttemptService;
use Services\Campaign\CampaignTimingAccessor;
use Services\Campaign\Validators\CampaignTimingValidationService;
use Services\Exceptions\Campaign\Validators\PublicHolidayValidationFailure;
use Services\Exceptions\Campaign\Validators\TimingRuleValidationFailure;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignNextAttemptServiceUnitTest
 */
class CampaignNextAttemptServiceUnitTest extends AbstractPhpunitUnitTest {

	/** @var CampaignTimingValidationService | \Phake_IMock*/
	private $timingValidationService;

	/** @var CampaignTimingAccessor | \Phake_IMock*/
	private $campaignTimingAccessor;

	/** @var CampaignNextAttemptService | \Phake_IMock*/
	private $nextAttemptService;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->timingValidationService = Phake::mock(CampaignTimingValidationService::class);
		$this->campaignTimingAccessor = Phake::mock(CampaignTimingAccessor::class);
		$this->nextAttemptService = new CampaignNextAttemptService(
			$this->timingValidationService,
			$this->campaignTimingAccessor
		);
	}

	/**
	 * @return void
	 */
	public function testGetValidNextAttemptDateTimeWhenDateTimePassedIsValid() {
		$campaignSettings = Phake::mock(CampaignSettings::class);
		$currentTime = Phake::mock(\DateTime::class);
		$nextAttemptInterval = Phake::mock(\DateInterval::class);
		$nextAttemptTime = Phake::mock(\DateTime::class);
		Phake::when($currentTime)->add($nextAttemptInterval)->thenReturn($nextAttemptTime);
		Phake::when($this->timingValidationService)
			->isValidDateTime($nextAttemptTime, $campaignSettings)
			->thenReturn(true);

		$this->assertSameEquals(
			$nextAttemptTime,
			$this
				->nextAttemptService
				->getValidNextAttemptDateTime($campaignSettings, $currentTime, $nextAttemptInterval)
		);
	}

	/**
	 * @return void
	 */
	public function testGetValidNextAttemptDateTimeWhenPublicHolidayValidationFails() {
		$campaignSettings = Phake::mock(CampaignSettings::class);

		$currentTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2019-12-26 10:00:00');
		$nextAttemptInterval = new \DateInterval('PT300S');

		$nextAttemptTime = clone $currentTime;
		$nextAttemptTime->add($nextAttemptInterval);

		Phake::when($this->timingValidationService)
			->isValidDateTime($nextAttemptTime, $campaignSettings)
			->thenThrow(new PublicHolidayValidationFailure());

		$nextAttemptTimeReturned = clone $nextAttemptTime;
		$nextAttemptTimeReturned
			->setTime('00', '00', '00')
			->add(new \DateInterval('P1D'));

		Phake::when($this->timingValidationService)
			->isValidDateTime(
				$nextAttemptTimeReturned,
				$campaignSettings
			)
			->thenReturn(true);

		$this->assertEquals(
			$nextAttemptTimeReturned,
			$this
				->nextAttemptService
				->getValidNextAttemptDateTime($campaignSettings, $currentTime, $nextAttemptInterval)
		);
	}

	/**
	 * @return void
	 */
	public function testGetValidNextAttemptOnTimingRuleValidationFailureReturnsNextDay() {
		$this->getValidNextAttemptOnTimingRuleValidationFailureTest(false);
	}

	/**
	 * @return void
	 */
	public function testGetValidNextAttemptOnTimingRuleValidationFailureReturnsPeriodStartDate() {
		$this->getValidNextAttemptOnTimingRuleValidationFailureTest(true);
	}

	/**
	 * @param boolean $nextAttemptTimeLesserThanPeriodStart
	 * @return void
	 */
	private function getValidNextAttemptOnTimingRuleValidationFailureTest(
		$nextAttemptTimeLesserThanPeriodStart = false
	) {
		$campaignSettings = Phake::mock(CampaignSettings::class);

		$currentTime = \DateTime::createFromFormat('Y-m-d H:i:s', '2019-12-26 20:00:00');
		$nextAttemptInterval = new \DateInterval('PT300S');

		$nextAttemptTime = clone $currentTime;
		$nextAttemptTime->add($nextAttemptInterval);

		Phake::when($this->timingValidationService)
			->isValidDateTime($nextAttemptTime, $campaignSettings)
			->thenThrow(new TimingRuleValidationFailure());

		$timingGroup = Phake::mock(TimingGroup::class);
		$timingPeriod = Phake::mock(TimingPeriod::class);

		$periodStart = clone $nextAttemptTime;

		if (!$nextAttemptTimeLesserThanPeriodStart) {
			$periodStart->sub(new \DateInterval('PT5S'));
			$nextAttemptTimeReturned = clone $nextAttemptTime;
			$nextAttemptTimeReturned
				->setTime('00', '00', '00')
				->add(new \DateInterval('P1D'));

			Phake::when($this->timingValidationService)
				->isValidDateTime(
					$nextAttemptTimeReturned,
					$campaignSettings
				)
				->thenReturn(true);
		} else {
			$nextAttemptTimeReturned = $periodStart;
		}

		Phake::when($timingPeriod)
			->getStartByDate($nextAttemptTime)
			->thenReturn($periodStart);

		Phake::when($timingGroup)->getTimingPeriodByDateTime($nextAttemptTime)->thenReturn($timingPeriod);

		Phake::when($this->campaignTimingAccessor)
			->getTimingGroup($campaignSettings)
			->thenReturn($timingGroup);

		$this->assertEquals(
			$nextAttemptTimeReturned,
			$this
				->nextAttemptService
				->getValidNextAttemptDateTime($campaignSettings, $currentTime, $nextAttemptInterval)
		);
	}
}
