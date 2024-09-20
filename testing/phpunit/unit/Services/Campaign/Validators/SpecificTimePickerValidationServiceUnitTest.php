<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Models\Entities\TimingGroup;
use Phake;
use Services\Campaign\Builders\SpecificTimesDirector;
use Services\Campaign\CampaignTimingAccessor;
use Services\Campaign\Validators\SpecificTimePickerValidationService;
use Services\Validators\CampaignPublicHolidaySettingsValidator;
use Services\Validators\SpecificTimesTimingPeriodValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SpecificTimePickerValidationServiceUnitTest
 */
class SpecificTimePickerValidationServiceUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SpecificTimesDirector | \Phake_IMock */
	private $specificTimesDirector;

	/** @var SpecificTimesTimingPeriodValidator | \Phake_IMock */
	private $specificTimesTimingPeriodValidator;

	/** @var CampaignTimingAccessor | \Phake_IMock */
	private $timingAccessor;

	/** @var CampaignPublicHolidaySettingsValidator | \Phake_IMock */
	private $publicHolidaySettingsValidator;

	/** @var SpecificTimePickerValidationService | \Phake_IMock */
	private $validationService;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->specificTimesDirector = Phake::mock(SpecificTimesDirector::class);
		$this->specificTimesTimingPeriodValidator = Phake::mock(SpecificTimesTimingPeriodValidator::class);
		$this->timingAccessor = Phake::mock(CampaignTimingAccessor::class);
		$this->publicHolidaySettingsValidator = Phake::mock(CampaignPublicHolidaySettingsValidator::class);
		$this->validationService = new SpecificTimePickerValidationService(
			$this->specificTimesDirector,
			$this->specificTimesTimingPeriodValidator,
			$this->timingAccessor,
			$this->publicHolidaySettingsValidator
		);
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			'is public holiday' => [false, true, false],
			'invalid timing period' => [true, false, false],
			'is public holiday and invalid timing period' => [false, false, false],
			'not a public holiday and timing period is valid' => [true, true, true]
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param boolean $holidayValidationIsValid
	 * @param boolean $timingPeriodValidationIsValid
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValid($holidayValidationIsValid, $timingPeriodValidationIsValid, $expected) {
		$specificTimes = Phake::mock(ArrayCollection::class);
		$settings = ['specific time settings'];
		Phake::when($this->specificTimesDirector)
			->buildFromArray($settings)
			->thenReturn($specificTimes);

		$timingGroup = Phake::mock(TimingGroup::class);
		$campaignSettings = Phake::mock(CampaignSettings::class);
		Phake::when($this->timingAccessor)->getTimingGroup($campaignSettings)->thenReturn($timingGroup);
		Phake::when($this->specificTimesTimingPeriodValidator)->setSpecificTimes($specificTimes)->thenReturnSelf();
		Phake::when($this->specificTimesTimingPeriodValidator)->isValid()->thenReturn($timingPeriodValidationIsValid);
		Phake::when($this->publicHolidaySettingsValidator)->setCampaignSettings($campaignSettings)->thenReturnSelf();
		Phake::when($this->publicHolidaySettingsValidator)->isValid()->thenReturn($holidayValidationIsValid);

		$this->assertSameEquals($expected, $this->validationService->isValid($campaignSettings, $settings));
		Phake::verify($campaignSettings)->setSpecificTimes($specificTimes);
		Phake::verify($this->specificTimesTimingPeriodValidator)->setTimingGroup($timingGroup);
	}
}
