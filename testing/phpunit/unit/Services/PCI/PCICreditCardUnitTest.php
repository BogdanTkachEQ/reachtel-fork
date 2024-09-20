<?php
/**
 * PCIRecorderUnitTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\PCI;

use PHPUnit_Framework_TestCase;
use Services\ConfigReader;
use Services\PCI\PCICreditCard;

/**
 * Unit test for PCICreditCard class
 */
class PCICreditCardUnitTest extends PHPUnit_Framework_TestCase
{
	/** @var PCICreditCard */
	private $pciCreditCard;

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$mockConfigReader = $this
			->getMockBuilder(ConfigReader::class)
			->disableOriginalConstructor()
			->getMock();

		$mockConfigReader
			->method('getConfig')
			->with('pci')
			->willReturn(
				[
				'cards' => [
					'card_1_no_luhn' => [
						'pattern' => '/^800\d+/',
						'length' => [5, 7],
						'luhn' => false,
					],
					'card_2_luhn' => [
						'pattern' => '/^5019/',
						'length' => [16],
						'luhn' => true,
					],
				]
				]
			);

		$this->pciCreditCard = PCICreditCard::getInstance($mockConfigReader);
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function getValidateProvider() {
		return [
			// failures
			[false, 'not a match'],
			[false, 'AB1234'],
			[false, '800123'],
			[false, '80012345'],
			[false, '5019717010103740'],
			[false, '50197170101037420'],

			// success
			['card_1_no_luhn', '80012'],
			['card_1_no_luhn', '8001234'],
			['card_2_luhn', '5019717010103742'],

			// whitelist test
			[false, '80012', 'card_1_no_luhn'],
			['card_2_luhn', '5019717010103742', 'card_1_no_luhn'],
			[false, '5019717010103742', ['CARD_2_LUHN']],
			[false, '5019717010103742', ['card_1_no_luhn', 'card_2_luhn']],
		];
	}

	/**
	 * @dataProvider getValidateProvider
	 * @param boolean $expected
	 * @param string  $number
	 * @param mixed   $whitelist
	 * @return void
	 */
	public function testValidate($expected, $number, $whitelist = false) {
		$this->assertEquals(
			$expected,
			$this->pciCreditCard->validate($number, $whitelist)
		);
	}
}
