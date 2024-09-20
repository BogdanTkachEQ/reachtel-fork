<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Validators;

use Models\CampaignSettings;
use Phake;
use Services\Campaign\Interfaces\Validators\CampaignValidationServiceInterface;
use Services\Campaign\Validators\CompositeCampaignValidationService;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CompositeCampaignValidationServiceUnitTest
 */
class CompositeCampaignValidationServiceUnitTest extends AbstractPhpunitUnitTest
{
	/** @var CompositeCampaignValidationService */
	private $validationService;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->validationService = new CompositeCampaignValidationService();
	}

	/**
	 * @return void
	 */
	public function testAdd() {
		$service1 = Phake::mock(CampaignValidationServiceInterface::class);
		$service2 = Phake::mock(CampaignValidationServiceInterface::class);
		$this->assertSameEquals($this->validationService, $this->validationService->add($service1));
		$this->assertSameEquals($this->validationService, $this->validationService->add($service2));
		$this->assertSameEquals($service1, $this->validationService->getValidationServices()->first());
		$this->assertSameEquals($service2, $this->validationService->getValidationServices()->next());
	}

	/**
	 * @return void
	 */
	public function testViolatesValidationRulesWithNoViolation() {
		$campaignSettings = Phake::mock(CampaignSettings::class);
		$service1 = Phake::mock(CampaignValidationServiceInterface::class);
		$service2 = Phake::mock(CampaignValidationServiceInterface::class);
		Phake::when($service1)->violatesValidationRules($campaignSettings)->thenReturn(false);
		Phake::when($service2)->violatesValidationRules($campaignSettings)->thenReturn(false);
		$this
			->validationService
			->add($service1)
			->add($service2);

		$this->assertFalse($this->validationService->violatesValidationRules($campaignSettings));
		Phake::verify($service1)->violatesValidationRules($campaignSettings);
		Phake::verify($service2)->violatesValidationRules($campaignSettings);
	}

	/**
	 * @return void
	 */
	public function testViolatesValidationRulesWithViolation() {
		$campaignSettings = Phake::mock(CampaignSettings::class);
		$service1 = Phake::mock(CampaignValidationServiceInterface::class);
		$service2 = Phake::mock(CampaignValidationServiceInterface::class);
		$service3 = Phake::mock(CampaignValidationServiceInterface::class);
		$disclaimer = 'test disclaimer';

		Phake::when($service1)->violatesValidationRules($campaignSettings)->thenReturn(false);
		Phake::when($service2)->violatesValidationRules($campaignSettings)->thenReturn(true);
		Phake::when($service3)->violatesValidationRules($campaignSettings)->thenReturn(false);

		Phake::when($service2)->getDisclaimer($campaignSettings)->thenReturn($disclaimer);
		$this
			->validationService
			->add($service1)
			->add($service2)
			->add($service3);

		$this->assertTrue($this->validationService->violatesValidationRules($campaignSettings));
		Phake::verify($service1)->violatesValidationRules($campaignSettings);
		Phake::verify($service2)->violatesValidationRules($campaignSettings);
		Phake::verify($service3, Phake::times(0))->violatesValidationRules($campaignSettings);
		$this->assertSameEquals($disclaimer, $this->validationService->getDisclaimer($campaignSettings));
		Phake::verify($service1, Phake::times(0))->getDisclaimer($campaignSettings);
		Phake::verify($service2, Phake::times(1))->getDisclaimer($campaignSettings);
		Phake::verify($service3, Phake::times(0))->getDisclaimer($campaignSettings);
	}
}
