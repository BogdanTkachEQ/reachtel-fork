<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignRecurringTime;
use Models\Day;
use Phake;
use Services\Campaign\Builders\RecurringTimeBuilder;
use Services\Campaign\Builders\RecurringTimesDirector;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class RecurringTimesDirectorUnitTest
 */
class RecurringTimesDirectorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var RecurringTimeBuilder | \Phake_IMock */
	private $builder;

	/** @var RecurringTimesDirector */
	private $director;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->builder = Phake::mock(RecurringTimeBuilder::class);
		Phake::when($this->builder)->reset()->thenReturnSelf();
		$this->director = new RecurringTimesDirector($this->builder);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Timezone is required
	 * @return void
	 */
	public function testBuildFromArrayThrowsInvalidArgumentException() {
		$this->director->buildFromArray([]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Campaign id does not exist
	 * @return void
	 */
	public function testBuildFromCampaignIdThrowsException() {
		$campaignId = 123;
		$this->mock_function_param_value(
			'api_campaigns_gettimezone',
			[
				['params' => $campaignId, 'return' => false]
			],
			false
		);

		$this->director->buildFromCampaignId($campaignId);
		$this->remove_mocked_functions('api_campaigns_gettimezone');
	}

	/**
	 * @return void
	 */
	public function testBuildFromCampaignId() {
		$campaignId = 1234;
		$timeZone = new \DateTimeZone('Australia/Sydney');
		$this->mock_function_param_value(
			'api_campaigns_gettimezone',
			[
				['params' => $campaignId, 'return' => $timeZone]
			],
			false
		);

		$recurring = [
			[
				'starttime' => '08:00:00',
				'endtime' => '11:00:00',
				'daysofweek' => 72 // Thu, Sun
			],
			[
				'starttime' => '14:00:00',
				'endtime' => '17:00:00',
				'daysofweek' => 37 // Mon, Wed, Sat
			],
			[
				'starttime' => '10:00:00',
				'endtime' => '12:00:00',
				'daysofweek' => 18 // Tue, Fri
			],
		];

		$this->mock_function_param_value(
			'api_restrictions_time_recurring_listall',
			[
				['params' => $campaignId, 'return' => $recurring]
			],
			false
		);

		$expected = [
			'timezone' => $timeZone,
			'recurring' => $recurring,
			'daysofweek' => [
				[Day::THURSDAY(), Day::SUNDAY()],
				[Day::MONDAY(), Day::WEDNESDAY(), Day::SATURDAY()],
				[Day::TUESDAY(), Day::FRIDAY()],
			]
		];

		Phake::when($this->builder)
			->getRecurringTime()
			->thenReturn(new CampaignRecurringTime())
			->thenReturn(new CampaignRecurringTime())
			->thenReturn(new CampaignRecurringTime());

		$recurringTimes = $this->director->buildFromCampaignId($campaignId);
		$this->assertInstanceOf(ArrayCollection::class, $recurringTimes);
		$this->assertRecurringTimes($expected, $recurringTimes);
		$this->remove_mocked_functions('api_campaigns_gettimezone');
		$this->remove_mocked_functions('api_restrictions_time_recurring_listall');
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$daysofweek = [
			[72, [Day::THURSDAY(), Day::SUNDAY()]],
			[37, [Day::MONDAY(), Day::WEDNESDAY(), Day::SATURDAY()]],
			[18, [Day::TUESDAY(), Day::FRIDAY()]],
		];

		$settings = [
			'timezone' => new \DateTimeZone('Australia/Brisbane'),
			'recurring' => [
				[
					'starttime' => '06:00:00',
					'endtime' => '10:00:00',
					'daysofweek' => $daysofweek[0][0] // Wed, Sat
				],
				[
					'starttime' => '17:00:00',
					'endtime' => '20:00:00',
					'daysofweek' => $daysofweek[1][0] // Sun, Tue, Fri
				],
				[
					'starttime' => '12:00:00',
					'endtime' => '12:37:49',
					'daysofweek' => $daysofweek[2][0] // Mon, Thu
				],
			]
		];

		Phake::when($this->builder)
			->getRecurringTime()
			->thenReturn(new CampaignRecurringTime())
			->thenReturn(new CampaignRecurringTime())
			->thenReturn(new CampaignRecurringTime());

		$recurringTimes = $this->director->buildFromArray($settings);
		$this->assertInstanceOf(ArrayCollection::class, $recurringTimes);
		$settings['daysofweek'] = [
			$daysofweek[0][1],
			$daysofweek[1][1],
			$daysofweek[2][1],
		];
		$this->assertRecurringTimes($settings, $recurringTimes);
	}

	/**
	 * @param array           $expected
	 * @param ArrayCollection $recurringTimes
	 * @return void
	 */
	private function assertRecurringTimes(array $expected, ArrayCollection $recurringTimes) {
		$timeZone = $expected['timezone'];

		$i = 0;
		foreach ($recurringTimes->toArray() as $recurringTime) {
			$recurring = $expected['recurring'][$i];
			$this->assertInstanceOf(CampaignRecurringTime::class, $recurringTime);
			$this->assertEquals($timeZone, $recurringTime->getStartTime()->getTimezone());
			$this->assertEquals($timeZone, $recurringTime->getEndTime()->getTimezone());
			$this->assertSameEquals($recurring['starttime'], $recurringTime->getStartTime()->format('H:i:s'));
			$this->assertSameEquals($recurring['endtime'], $recurringTime->getEndTime()->format('H:i:s'));
			$this->assertInstanceOf(ArrayCollection::class, $recurringTime->getActiveDays());
			$daysOfWeek = $expected['daysofweek'][$i];
			$this->assertSameEquals($daysOfWeek, $recurringTime->getActiveDays()->toArray());
			$i++;
		}
	}
}
