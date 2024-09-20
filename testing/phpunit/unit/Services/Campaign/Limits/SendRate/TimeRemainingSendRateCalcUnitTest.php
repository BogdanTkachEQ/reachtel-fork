<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Phake;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\GenericCampaignCreator;
use Services\Campaign\Limits\SendRate\TimeRemainingSendRateCalc;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GenericCampaignCreatorUnitTest
 */
class TimeRemainingSendRateCalcUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return array
	 */
	public function provider() {
		return [
			[100, 100, 60, 0, 204],
			[0, 100, 120, 0, 51],
			[100, 0, 120, 0, 51],
			[null, 100, 60, 0, 102],
			[100, null, 60, 0, 102],
			[100, 100, 60, 100, 304]
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
		$calc = new TimeRemainingSendRateCalc(1);
		$sendRate = $calc->calculateRate($modifier);
		$this->assertEquals($sendRate, $expected);
	}
}
