<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Phake;
use Services\Campaign\Builders\CampaignSettingsDirector;
use Services\Campaign\CampaignActivationPermissionService;
use Services\Campaign\Interfaces\Validators\CampaignValidationServiceInterface;
use Services\Exceptions\Campaign\Validators\ValidationDisclaimerException;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignActivationPermissionServiceUnitTest
 */
class CampaignActivationPermissionServiceUnitTest extends AbstractPhpunitUnitTest
{
	const TEST_CAMPAIGN_ID = 123;

	/** @var CampaignSettingsDirector | \Phake_IMock */
	private $campaignSettingsDirector;

	/** @var CampaignValidationServiceInterface | \Phake_IMock */
	private $campaignValidationService;

	/** @var CampaignActivationPermissionService */
	private $activationPermissionService;

	/** @var CampaignSettings | \Phake_IMock */
	private $campaignSettings;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->campaignSettingsDirector = Phake::mock(CampaignSettingsDirector::class);
		$this->campaignValidationService = Phake::mock(CampaignValidationServiceInterface::class);
		$this->activationPermissionService = new CampaignActivationPermissionService(
			$this->campaignSettingsDirector,
			$this->campaignValidationService
		);

		$this->campaignSettings = Phake::mock(CampaignSettings::class);
		Phake::when($this->campaignSettingsDirector)
			->buildCampaignSettings(self::TEST_CAMPAIGN_ID)
			->thenReturn($this->campaignSettings);
	}

	/**
	 * @return array
	 */
	public function campaignCannotBeActivatedIfTimesAreNotSetDataProvider() {
		return [
			'when specific times are set and recurring times are not set' => [1, 0, true],
			'when specific times are not set and recurring times are set' => [0, 3, true],
			'when no times are set' => [0, 0, false],
		];
	}

	/**
	 * @dataProvider campaignCannotBeActivatedIfTimesAreNotSetDataProvider
	 * @param integer $specificCount
	 * @param integer $recurringCount
	 * @param boolean $expected
	 * @return void
	 */
	public function testCampaignCannotBeActivatedIfTimesAreNotSet($specificCount, $recurringCount, $expected) {
		Phake::when($this->campaignValidationService)
			->violatesValidationRules($this->campaignSettings)
			->thenReturn(false);

		$specificTimes = Phake::mock(ArrayCollection::class);
		$recurringTimes = Phake::mock(ArrayCollection::class);
		Phake::when($recurringTimes)->count()->thenReturn($recurringCount);
		Phake::when($specificTimes)->count()->thenReturn($specificCount);
		Phake::when($this->campaignSettings)->getRecurringTimes()->thenReturn($recurringTimes);
		Phake::when($this->campaignSettings)->getSpecificTimes()->thenReturn($specificTimes);

		$this->assertSameEquals(
			$expected,
			$this->activationPermissionService->canBeActivated(self::TEST_CAMPAIGN_ID)
		);
	}

	/**
	 * @return void
	 */
	public function testCanBeActivated() {
		$this->setTimesInCampaignSettings();
		Phake::when($this->campaignValidationService)
			->violatesValidationRules($this->campaignSettings)
			->thenReturn(false);
		$this->assertSameEquals(
			true,
			$this->activationPermissionService->canBeActivated(self::TEST_CAMPAIGN_ID)
		);
	}

	/**
	 * @return void
	 */
	public function testCanBeActivatedThrowsException() {
		$this->setTimesInCampaignSettings();
		Phake::when($this->campaignValidationService)
			->violatesValidationRules($this->campaignSettings)
			->thenReturn(true);

		$disclaimer = 'Test disclaimer';
		Phake::when($this->campaignValidationService)->getDisclaimer($this->campaignSettings)->thenReturn($disclaimer);
		try {
			$this->activationPermissionService->canBeActivated(self::TEST_CAMPAIGN_ID);
		} catch (\Exception $exception) {
			$this->assertInstanceOf(ValidationDisclaimerException::class, $exception);
			$this->assertSameEquals($disclaimer, $exception->getDisclaimer());
		}
	}

	/**
	 * @return void
	 */
	private function setTimesInCampaignSettings() {
		$specificTimes = Phake::mock(ArrayCollection::class);
		Phake::when($specificTimes)->count()->thenReturn(3);
		Phake::when($this->campaignSettings)->getSpecificTimes()->thenReturn($specificTimes);
		$recurringTimes = Phake::mock(ArrayCollection::class);
		Phake::when($recurringTimes)->count()->thenReturn(2);
		Phake::when($this->campaignSettings)->getRecurringTimes()->thenReturn($recurringTimes);
	}
}
