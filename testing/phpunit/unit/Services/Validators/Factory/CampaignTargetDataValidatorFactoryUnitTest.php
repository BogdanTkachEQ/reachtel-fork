<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators\Factory;

use Models\CampaignType;
use Services\Validators\Factory\CampaignTargetDataValidatorFactory;
use Services\Validators\WashCampaignTargetDataValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignTargetDataValidatorFactoryUnitTest
 */
class CampaignTargetDataValidatorFactoryUnitTest extends AbstractPhpunitUnitTest
{
	/** @var CampaignTargetDataValidatorFactory */
	private $factory;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->factory = new CampaignTargetDataValidatorFactory();
	}

	/**
	 * @return array
	 */
	public function createDataProvider() {
		return [
			[CampaignType::SMS()],
			[CampaignType::PHONE()],
			[CampaignType::EMAIL()],
		];
	}

	/**
	 * @dataProvider createDataProvider
	 * @param CampaignType $type
	 * @expectedException Services\Exceptions\Validators\CampaignTargetDataValidatorFactoryException
	 * @expectedExceptionMessage No validators found for the campaign type
	 * @return void
	 */
	public function testCreateThrowsException(CampaignType $type) {
		$this->factory->create($type);
	}

	/**
	 * @return void
	 */
	public function testCreate() {
		$this->assertInstanceOf(
			WashCampaignTargetDataValidator::class,
			$this->factory->create(CampaignType::WASH())
		);
	}
}
