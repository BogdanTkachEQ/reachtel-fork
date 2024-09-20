<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Branding;

use Services\Customers\Toyota\Branding\ToyotaBrandingEnum;

/**
 * Class BrandingFactoryTest
 */
class BrandingFactoryTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return void
	 */
	public function testBuild() {
		$factory = new BrandingFactory();
		$brand = $factory->build(ToyotaBrandingEnum::TOYOTA_FINANCE());
		$this->assertInstanceOf(ToyotaBrandingEnum::class, $brand);
		$this->assertNotFalse($brand);
	}

	/**
	 * @return void
	 */
	public function testSearch() {
		$factory = new BrandingFactory();
		$brand = $factory->build("Toyota Finance");
		$this->assertInstanceOf(ToyotaBrandingEnum::class, $brand);
		$this->assertNotFalse($brand);
	}
}
