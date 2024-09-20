<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Branding;

/**
 * Class ToyotaBrandingEnumTest
 */
class ToyotaBrandingEnumTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function reverseMapDataProvider() {
		return [
				"capitals" => ["Toyota Finance","Toyota"],
				"no capitals" => ["toyota finance","Toyota"],
				"structured" => ["toyota_finance","Toyota"],
				"structured capitals" => ["Toyota_Finance","Toyota"],
				"lexus" => ["Lexus Financial Services","Lexus"],
				"hino" => ["hino financial services","Hino"],
				"powertorque" => ["PowerTorque Finance","PowerTorque"],
				"poweralliance" => ["Power Alliance Finance","Power Alliance"]
			];
	}

	/**
	 * @dataProvider reverseMapDataProvider
	 * @param string $search Brand.
	 * @param string $brand  Display Name.
	 * @return void
	 */
	public function testSearch($search, $brand) {
		$enum = ToyotaBrandingEnum::search($search);
		$this->assertNotFalse($enum);
		$this->assertEquals($brand, $enum->getBrandName());
	}

	/**
	 * @dataProvider reverseMapDataProvider
	 * @param string $search Brand.
	 * @param string $brand  Display Name.
	 * @return void
	 */
	public function testBrand($search, $brand) {
		$enum = ToyotaBrandingEnum::search($search);
		$this->assertNotFalse($enum);
		$this->assertEquals($enum->getBrandName(), $brand);
	}
}
