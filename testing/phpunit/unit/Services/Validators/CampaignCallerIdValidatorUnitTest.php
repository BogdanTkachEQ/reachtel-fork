<?php
/**
 * @author		rohith.mohan@equifax.com
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Validators\CampaignCallerIdValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WeekendRunControllerUnitTest
 */
class CampaignCallerIdValidatorUnitTest extends AbstractPhpunitUnitTest
{

	/**
	 * @return array
	 */
	public function validatorProvider() {
		return [
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH(), false, true],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH(), true, false],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING(), false, true],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING(), true, false],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT(), true, true],
			[CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT(), true, true],
		];
	}

	/**
	 * @dataProvider validatorProvider
	 * @param CampaignClassificationEnum $classification
	 * @param boolean                    $callerIdWithheld
	 * @param boolean                    $expected
	 * @return void
	 */
	public function testValidator(CampaignClassificationEnum $classification, $callerIdWithheld, $expected) {
		$validator = new CampaignCallerIdValidator();
		$validator->isCallerIdWithHeld($callerIdWithheld)
			->setClassification($classification);
		$this->assertEquals($expected, $validator->isValid());
	}
}
