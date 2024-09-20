<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Autoload;

use Phake;
use Services\Autoload\PublicHolidayLineExclusionRule;
use Services\Utils\PublicHolidayChecker;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class PublicHolidayLineExclusionRuleUnitTest
 */
class PublicHolidayLineExclusionRuleUnitTest extends AbstractPhpunitUnitTest
{
	/** @var PublicHolidayChecker | \Phake_IMock */
	private $publicHolidayChecker;

	/** @var PublicHolidayLineExclusionRule | \Phake_IMock */
	private $rule;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->publicHolidayChecker = Phake::mock(PublicHolidayChecker::class);
		$this->rule = new PublicHolidayLineExclusionRule($this->publicHolidayChecker);
	}

	/**
	 * @return array
	 */
	public function shouldExcludeDataProvider() {
		return [
			[['country1' => 'AU', 'country2' => 'NZ', 'country3' => 'PH'], ['NZ'], true],
			[['country1' => 'PH', 'country2' => 'NZ', 'country3' => 'PH'], ['AU'], false],
			[['country1' => 'AU', 'country2' => 'NZ', 'country3' => 'NZ'], ['NZ'], true],
		];
	}

	/**
	 * @dataProvider shouldExcludeDataProvider
	 * @param array   $line
	 * @param array   $holidayValues
	 * @param boolean $expected
	 * @return void
	 */
	public function testShouldExclude(array $line, array $holidayValues, $expected) {
		$countryColumnNames = ['country1', 'country2'];
		$dateTime = new \DateTime();

		foreach ($holidayValues as $value) {
			Phake::when($this->publicHolidayChecker)->isPublicHolidayByCountryShortCode($dateTime, $value)->thenReturn(true);
		}

		$this->assertSameEquals(
			$expected,
			$this
				->rule
				->setCountryColumnNames($countryColumnNames)
				->setDateTime($dateTime)
				->shouldExclude($line)
		);
	}
}
