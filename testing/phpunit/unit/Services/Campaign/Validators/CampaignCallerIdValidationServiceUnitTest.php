<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Validators;

use Models\CampaignSettings;
use Models\CampaignType;
use Phake;
use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Campaign\Validators\CampaignCallerIdValidationService;
use Services\Campaign\Validators\Disclaimers\CallerIdDisclaimerProvider;
use Services\Validators\CampaignCallerIdValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignCallerIdValidationServiceUnitTest
 */
class CampaignCallerIdValidationServiceUnitTest extends AbstractPhpunitUnitTest
{
	/** @var CampaignCallerIdValidator | \Phake_IMock */
	private $callerIdValidator;

	/** @var CallerIdDisclaimerProvider | \Phake_IMock */
	private $disclaimerProvider;

	/** @var CampaignCallerIdValidationService */
	private $validationService;

	/** @var CampaignSettings | \Phake_IMock */
	private $campaignSettings;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->campaignSettings = Phake::mock(CampaignSettings::class);
		$this->callerIdValidator = Phake::mock(CampaignCallerIdValidator::class);
		Phake::when($this->callerIdValidator)->setClassification(Phake::anyParameters())->thenReturnSelf();
		Phake::when($this->callerIdValidator)->isCallerIdWithHeld(Phake::anyParameters())->thenReturnSelf();
		$this->disclaimerProvider = Phake::mock(CallerIdDisclaimerProvider::class);
		$this->validationService = new CampaignCallerIdValidationService(
			$this->callerIdValidator,
			$this->disclaimerProvider
		);
	}

	/**
	 * @return array
	 */
	public function violatesValidationRulesBasedOnCampaignTypeDataProvider() {
		return [
			'when campaign type is wash' => [CampaignType::WASH(), false],
			'when campaign type is sms' => [CampaignType::SMS(), false],
			'when campaign type is email' => [CampaignType::EMAIL(), false],
			'when campaign type is phone' => [CampaignType::PHONE(), true],
		];
	}

	/**
	 * @dataProvider violatesValidationRulesBasedOnCampaignTypeDataProvider
	 * @param CampaignType $type
	 * @param boolean      $expected
	 * @return void
	 */
	public function testViolatesValidationRulesBasedOnCampaignType(CampaignType $type, $expected) {
		Phake::when($this->campaignSettings)->getType()->thenReturn($type);
		Phake::when($this->callerIdValidator)->isValid()->thenReturn(true);
		$classification = Phake::mock(CampaignClassificationEnum::class);
		Phake::when($this->campaignSettings)->getClassificationEnum()->thenReturn($classification);
		Phake::when($this->campaignSettings)->isCallerIdWithHeld()->thenReturn(true);
		Phake::when($this->callerIdValidator)->isValid()->thenReturn(false);
		$this->assertSameEquals($expected, $this->validationService->violatesValidationRules($this->campaignSettings));
	}

	/**
	 * @return array
	 */
	public function violatesValidationRulesDataProvider() {
		return [
			'valid calller id validation' => [true, false, false],
			'invlaid caller id validation' => [false, true, true]
		];
	}

	/**
	 * @dataProvider violatesValidationRulesDataProvider
	 * @param boolean $isValid
	 * @param boolean $isWithHeld
	 * @param boolean $expected
	 * @return void
	 */
	public function testViolatesValidationRules($isValid, $isWithHeld, $expected) {
		Phake::when($this->campaignSettings)->getType()->thenReturn(CampaignType::PHONE());
		$classification = Phake::mock(CampaignClassificationEnum::class);
		Phake::when($this->campaignSettings)->getClassificationEnum()->thenReturn($classification);
		Phake::when($this->campaignSettings)->isCallerIdWithHeld()->thenReturn($isWithHeld);
		Phake::when($this->callerIdValidator)->isValid()->thenReturn($isValid);
		$this->assertSameEquals($expected, $this->validationService->violatesValidationRules($this->campaignSettings));
		Phake::verify($this->callerIdValidator)->setClassification($classification);
		Phake::verify($this->callerIdValidator)->isCallerIdWithHeld($isWithHeld);
	}

	/**
	 * @return void
	 */
	public function testGetDisclaimer() {
		$disclaimer = 'Test disclaimer';
		Phake::when($this->disclaimerProvider)->getDisclaimer()->thenReturn($disclaimer);
		$this->assertSameEquals($disclaimer, $this->validationService->getDisclaimer($this->campaignSettings));
	}
}
