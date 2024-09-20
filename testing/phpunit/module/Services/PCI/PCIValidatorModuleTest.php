<?php
/**
 * PCIValidatorUnitTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\PCI;

use PHPUnit_Framework_TestCase;
use Services\PCI\PCIValidator;

/**
 * Module test for PCIValidator class
 */
class PCIValidatorModuleTest extends PHPUnit_Framework_TestCase
{
	/** @var PCIRecorder */
	private $pciValidator;

	/**
	 * {@inheritDoc}
	 * @see PHPUnit_Framework_TestCase::setUp()
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->pciValidator = new PCIValidator();
	}

	/**
	 * @return array
	 */
	public function regExpConstantProvider() {
		return [
			// failures
			'no digit' => [false, 'nothing'],
			'1 digit' => [false, 'just 1 integer'],
			'11 digits' => [false, '12345678901'],
			'12 digits but text in between' => [false, '12345 blah 678901'],
			'12 digits but wrong separator' => [false, '1234*5678*9012'],
			'(3 6 6) pattern no match' => [false, '123 123456 123456'],
			'(3/6/6) pattern no match' => [false, '123/123456/123456'],
			'(3-6-6) pattern no match' => [false, '123-123456-123456'],
			'(5 4 5) pattern no match' => [true, '123456 1234 12345'],
			'(5/4/5) pattern no match' => [true, '123456/1234/12345'],
			'(5-4-5) pattern no match' => [true, '123456-1234-12345'],
			// success
			'12 digits' => [true, '123456789012'],
			'12 digits space separator' => [true, '1234 5678 9012'],
			'12 digits slash separator' => [true, '1234/5678/9012'],
			'12 digits dash separator' => [true, '1234-5678-9012'],
			'(4 4 4 4) pattern' => [true, '1234 1234 1234 1234'],
			'(4/4/4/4) pattern' => [true, '1234/1234/1234/1234'],
			'(4-4-4-4) pattern' => [true, '1234-1234-1234-1234'],
			'(4 6 5) pattern' => [true, '1234 123456 12345'],
			'(4/6/5) pattern' => [true, '1234/123456/12345'],
			'(4-6-5) pattern' => [true, '1234-123456-12345'],
			'(6 13) pattern' => [true, '123456 1234567890123'],
			'(6/13) pattern' => [true, '123456/1234567890123'],
			'(6-13) pattern' => [true, '123456-1234567890123'],
			'(4 4 5) pattern' => [true, '1234 1234 12345'],
			'(4/4/5) pattern' => [true, '1234/1234/12345'],
			'(4-4-5) pattern' => [true, '1234-1234-12345'],
			'(4 4 4 4 3) pattern' => [true, '1234 1234 1234 1234 123'],
			'(4/4/4/4/3) pattern' => [true, '1234/1234/1234/1234/123'],
			'(4-4-4-4-3) pattern' => [true, '1234-1234-1234-1234-123'],
			'(4 6 4) pattern' => [true, '1234 123456 1234'],
			'(4/6/4) pattern' => [true, '1234/123456/1234'],
			'(4-6-4) pattern' => [true, '1234-123456-1234'],
			'(4 5 6) pattern' => [true, '1234 12345 123456'],
			'(4/5/6) pattern' => [true, '1234/12345/123456'],
			'(4-5-6) pattern' => [true, '1234-12345-123456'],
		];
	}

	/**
	 * @dataProvider regExpConstantProvider
	 * @param boolean $expected
	 * @param string  $subject
	 * @return void
	 */
	public function testRegExpConstant($expected, $subject) {
		$this->assertEquals(
			(bool) $expected,
			preg_match(sprintf('/^%s$/', PCIValidator::PAN_REGEXP), $subject, $matches)
		);
	}

	/**
	 * @return array
	 */
	public function matchAllPANDataProvider() {
		return [
			// no match
			[false, 'nothing'],
			[false, '1234'],
			[false, 'Date 2018/02/25'],
			[false, 'Datetime 2018/02/25 12-02-01'],
			[false, '4917300800000000378282246310005'], // 2 cards without separator
			// match
			[['378282246310005'], '378282246310005'],
			[['4917300800000000'], 'my card is 4917300800000000'],
			[
				['378282246310005', '620467 9475679144515'],
				'I have 2 cards 378282246310005 and 620467 9475679144515'
			],
			[
				['378282246310005', '620467 9475679144515'],
				'this is card 1 378282246310005 this is card 2 620467 9475679144515'
			],
			[
				['3670-010200-0000'],
				'you card on 2018/02/25 12-02-01 is 3670-010200-0000'
			],
			[
				['378282246310005'],
				'test with dot after 378282246310005.'
			],
			[
				['378282246310005'],
				'test with colon before :378282246310005'
			],
			[
				['378282246310005'],
				'test with colon before :378282246310005. and dot after.'
			],
			// stopOnFirstMatch tests
			[
				false,
				'no PAN data in this string',
				true
			],
			[
				'378282246310005',
				'I have 2 cards 378282246310005 and 620467 9475679144515',
				true
			],
			[
				'378282246310005',
				'this is card 1 378282246310005 this is card 2 620467 9475679144515',
				true
			],
			[
				'3670-010200-0000',
				'you card on 2018/02/25 12-02-01 is 3670-010200-0000',
				true
			],
		];
	}

	/**
	 * @dataProvider matchAllPANDataProvider
	 * @param mixed   $expected
	 * @param string  $string
	 * @param boolean $stopOnFirstMatch
	 * @return void
	 */
	public function testMatchAllPANData($expected, $string, $stopOnFirstMatch = false) {
		$this->assertEquals(
			$expected,
			$this->pciValidator->matchAllPANData($string, $stopOnFirstMatch)
		);
	}

	/**
	 * @return array
	 */
	public function creditCardsProvider() {
		return [
			// valid data
			'null value' => [false, null],
			'empty value' => [false, ''],
			'false value' => [false, false],
			'not integers' => [false, 'hello'],
			'integers and letter' => [false, 'hell0'],
			'id' => [false, 13573544282],
			'id like CC' => [false, 4818040872121770],
			'string like CC but invalid' => [false, '4452651888947500'],
			'string like CC with spaces but invalid' => [false, '5019 7170 1010 3740'],
			'string like CC with slashes but invalid' => [false, '6000/0009/9013/9424'],
			// invalid data CC
			// test CC comes from Inacho\CreditCard\Test::$validCards
			'CC visaelectron as integer' => [true, 4917300800000000],
			'CC visaelectron' => [true, '4917300800000000'],
			'CC maestro' => [true, '6759649826438453'],
			'CC maestro long' => [true, '6799990100000000019'],
			'CC maestro long (4 4 4 4 3)' => [true, '6799 9901 0000 0000 019'],
			'CC maestro long (4-4-4-4-3)' => [true, '6799-9901-0000-0000-019'],
			'CC maestro long (4/4/4/4/3)' => [true, '6799/9901/0000/0000/019'],
			'CC forbrugsforeningen' => [true, '4093285377433443'],
			'CC dankort' => [true, '5019717010103742'],
			'CC visa' => [true, '4111111111111111'],
			'CC visa short' => [true, '4222222222222'],
			'CC mastercard' => [true, '5555555555554444'],
			'CC amex' => [true, '378282246310005'],
			'CC dinersclub' => [true, '36700102000000'],
			'CC dinersclub (4 6 4)' => [true, '3670 010200 0000'],
			'CC dinersclub (4-6-4)' => [true, '3670-010200-0000'],
			'CC dinersclub (4/6/4)' => [true, '3670/010200/0000'],
			'CC discover' => [true, '6011000990139424'],
			'CC unionpay' => [true, '6204679475679144515'],
			'CC unionpay (6 13)' => [true, '620467 9475679144515'],
			'CC unionpay (6-13)' => [true, '620467-9475679144515'],
			'CC unionpay (6/13)' => [true, '620467/9475679144515'],
			'CC jcb' => [true, '3530111333300000'],
			'CC visaelectron with spaces' => [true, '4917 3008 0000 0000'],
			'CC amex with dashes' => [true, '3782-8224-6310-005'],
			'CC forbrugsforeningen with slashes' => [true, '4093/2853/7743/3443'],
			'CC dankort with non trimmed and dashes' => [true, '  5019-7170-1010-3742    '],
			'CC unionpay with non trimmed and spaces' => [true, '	6204 6794 7567 9144 515	'],
			'CC unionpay with non trimmed and spaces' => [true, '	6204 6794 7567 9144 515	'],
		];
	}

	/**
	 * @dataProvider creditCardsProvider
	 * @param boolean $expected
	 * @param string  $data
	 * @return void
	 */
	public function testisPANData($expected, $data) {
		$this->assertEquals(
			$expected,
			$this->pciValidator->isPANData($data)
		);
	}

	/**
	 * @return array
	 */
	public function maskPANDataProvider() {
		return [
			// not unique
			['12', '12', false],
			['X2345', '12345', false],
			['XXXX5678', '12345678', false],
			['XXXXXXXXXXXXXXX5678', '1234 5678 1234 5678', false],
			// unique
			['99', '99', true],
			['X9999-fc07018a7e379f1c4627c874e96d4902', 'X9999', true],
			['XXXX9999-c783edf8e04f13b6d8353e048161269d', '12349999', true],
			[
				'XXXXXXXXXXXXXXX5678-839f119fd3c65a592ba3b6801c05bca4e6d1c75e58e2cdd6f6ce3284efee5ed5',
				'XXXXXXXXXXXXXXX5678',
				true
			],
		];
	}

	/**
	 * @dataProvider maskPANDataProvider
	 * @param boolean $expected
	 * @param string  $data
	 * @param boolean $unique
	 * @return void
	 */
	public function testmaskPANData($expected, $data, $unique) {
		$this->assertEquals(
			$expected,
			$this->pciValidator->maskPANData($data, $unique)
		);
	}
}
