<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\Services\Customers\Toyota\Autoload;

use Phake;
use Services\Autoload\Command\Customers\Toyota\LineProcessorCommand;
use Services\Campaign\Interfaces\CampaignCreatorInterface;
use Services\Customers\Toyota\Autoload\AutoloadStrategy;
use testing\unit\Services\Autoload\AbstractLineItemProcessorStrategyUnitTest;

/**
 * Class AutoloadStrategyUnitTest
 */
class AutoloadStrategyUnitTest extends AbstractLineItemProcessorStrategyUnitTest
{
	/**
	 * @var array
	 */
	protected $brandCampaignMap = [
		'brand1' => 'campaign{{jFy}}Name1',
		'brand2' => 'campaign{{jFy}}Name2',
		'brand3' => 'campaign{{jFy}}Name3',
	];

	/**
	 * @var \DateTimeZone
	 */
	protected $timezone;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->timezone = new \DateTimeZone('Australia/Sydney');
		parent::setUp();
	}

	/**
	 * @return array
	 */
	public function sendRateQuotientDataProvider() {
		return [
			'when quotient is 0' => [0],
			'when quotient is less that 0' => [-2]
		];
	}

	/**
	 * @dataProvider sendRateQuotientDataProvider
	 * @expectedException \Exception
	 * @expectedExceptionMessage Send rate quotient should be greater than 0
	 * @param integer $quotient
	 * @return void
	 */
	public function testSetSendRateQuotientThrowsException($quotient) {
		$this->strategy->setSendRateQuotient($quotient);
	}

	/**
	 * @return AutoloadStrategy
	 */
	protected function getStrategy() {
		return new AutoloadStrategy(
			$this->fileProcessor,
			$this->brandCampaignMap,
			$this->timezone,
			Phake::mock(CampaignCreatorInterface::class),
			Phake::mock(LineProcessorCommand::class)
		);
	}

	/**
	 * @return array
	 */
	protected function getRequiredColumns() {
		return ['vchFinancier'];
	}
}
