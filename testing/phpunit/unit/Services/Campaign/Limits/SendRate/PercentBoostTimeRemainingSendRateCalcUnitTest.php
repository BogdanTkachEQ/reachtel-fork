<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Phake;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\GenericCampaignCreator;
use Services\Campaign\Limits\SendRate\PercentBoostTimeRemainingSendRateCalc;
use Services\Campaign\Limits\SendRate\TimeRemainingSendRateCalc;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GenericCampaignCreatorUnitTest
 */
class PercentBoostTimeRemainingSendRateCalcUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function provider() {
		return [
			[100, 100, 60, 0, 200],
			[100, 100, 30, 0, 400],
			[100, 100, 5, 0, 2400],
			[100, 100, 60, 10, 220],
			[100, 100, 30, 100, 800],
			[200, 600, 60, 1000, 8800],
			[100, 100, 60, -10, 180],
			[100, 100, 5, 50, 3600],
			[100, 0, 120, 0, 50],
			[null, 100, 60, 0, 100],
			[100, null, 60, 0, 100],
			[100, null, 240, 0, 25],
			[100, 100, 60, 100, 400]
		];
	}

	/**
	 * @dataProvider provider
	 * @param integer $ready
	 * @param integer $reattempt
	 * @param integer $minsRemaining
	 * @param integer $modifier
	 * @param integer $expected
	 * @return void
	 */
	public function testCalc($ready, $reattempt, $minsRemaining, $modifier, $expected) {
		$this->mock_function_value('api_data_target_status', ['READY' => $ready, 'REATTEMPT' => $reattempt]);
		$this->mock_function_value('api_restrictions_time_remaining', $minsRemaining * 60);
		$calc = new PercentBoostTimeRemainingSendRateCalc(1);
		$sendRate = $calc->calculateRate($modifier);
		$this->assertEquals($sendRate, $expected);
	}
}
