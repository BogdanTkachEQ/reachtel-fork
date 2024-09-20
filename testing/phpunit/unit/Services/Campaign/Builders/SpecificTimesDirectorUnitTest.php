<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Builders;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSpecificTime;
use Phake;
use Services\Campaign\Builders\SpecificTimeBuilder;
use Services\Campaign\Builders\SpecificTimesDirector;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SpecificTimesDirectorUnitTest
 */
class SpecificTimesDirectorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SpecificTimeBuilder */
	private $builder;

	/** @var SpecificTimesDirector */
	private $director;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->builder = Phake::mock(SpecificTimeBuilder::class);
		Phake::when($this->builder)->reset()->thenReturnSelf();
		$this->director = new SpecificTimesDirector($this->builder);
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

		$specific = [
			[
				'starttime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-20 00:15:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'endtime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-20 15:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'status' => -1
			],
			[
				'starttime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-22 09:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'endtime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-22 20:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'status' => 1
			],
			[
				'starttime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-21 10:25:50',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'endtime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-21 15:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'status' => 0
			],
		];

		$this->mock_function_param_value(
			'api_restrictions_time_specific_listall',
			[
				['params' => $campaignId, 'return' => $specific]
			],
			false
		);

		$expected = ['timezone' => $timeZone, 'specific' => $specific];
		Phake::when($this->builder)
			->getSpecificTime()
			->thenReturn(new CampaignSpecificTime())
			->thenReturn(new CampaignSpecificTime())
			->thenReturn(new CampaignSpecificTime());

		$specificTimes = $this->director->buildFromCampaignId($campaignId);
		$this->assertInstanceOf(ArrayCollection::class, $specificTimes);
		$this->assertSpecificTimes($expected, $specificTimes);
		$this->remove_mocked_functions('api_campaigns_gettimezone');
		$this->remove_mocked_functions('api_restrictions_time_specific_listall');
	}

	/**
	 * @return void
	 */
	public function testBuildFromArray() {
		$specific = [
			[
				'starttime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-20 00:15:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'endtime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-20 15:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'status' => -1
			],
			[
				'starttime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-22 09:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'endtime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-22 20:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'status' => 1
			],
			[
				'starttime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-21 10:25:50',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'endtime' => \DateTime::createFromFormat(
					'Y-m-d H:i:s',
					'2019-12-21 15:00:00',
					new \DateTimeZone('Australia/Brisbane')
				)->getTimestamp(),
				'status' => 0
			],
		];

		$settings = [
			'timezone' => new \DateTimeZone('Australia/Brisbane'),
			'specific' => $specific
		];

		Phake::when($this->builder)
			->getSpecificTime()
			->thenReturn(new CampaignSpecificTime())
			->thenReturn(new CampaignSpecificTime())
			->thenReturn(new CampaignSpecificTime());

		$specificTimes = $this->director->buildFromArray($settings);
		$this->assertInstanceOf(ArrayCollection::class, $specificTimes);
		$this->assertSpecificTimes($settings, $specificTimes);
	}

	/**
	 * @param array           $expected
	 * @param ArrayCollection $specificTimes
	 * @return void
	 */
	private function assertSpecificTimes(array $expected, ArrayCollection $specificTimes) {
		$timeZone = $expected['timezone'];

		$i = 0;
		/** @var CampaignSpecificTime $specificTime */
		foreach ($specificTimes->toArray() as $specificTime) {
			$specific = $expected['specific'][$i];
			$this->assertInstanceOf(CampaignSpecificTime::class, $specificTime);
			$this->assertEquals($timeZone, $specificTime->getStartDateTime()->getTimezone());
			$this->assertEquals($timeZone, $specificTime->getEndDateTime()->getTimezone());
			$this->assertSameEquals($specific['starttime'], $specificTime->getStartDateTime()->getTimestamp());
			$this->assertSameEquals($specific['endtime'], $specificTime->getEndDateTime()->getTimestamp());
			$this->assertSameEquals($specific['status'], $specificTime->getStatus());
			$i++;
		}
	}
}
