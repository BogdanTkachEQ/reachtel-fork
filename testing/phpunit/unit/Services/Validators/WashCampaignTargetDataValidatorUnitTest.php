<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Validators\WashCampaignTargetDataValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class WashCampaignTargetDataValidatorUnitTest
 */
class WashCampaignTargetDataValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/** @var WashCampaignTargetDataValidator */
	private $validator;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->validator = new WashCampaignTargetDataValidator();
	}

	/**
	 * @return void
	 */
	public function testSetTargetKey() {
		$this->assertInstanceOf(
			WashCampaignTargetDataValidator::class,
			$this->validator->setTargetKey(123)
		);
	}

	/**
	 * @return void
	 */
	public function testSetMergeData() {
		$this->assertInstanceOf(
			WashCampaignTargetDataValidator::class,
			$this->validator->setMergeData(['column' => 'value', 'column1' => 'value1'])
		);
	}

	/**
	 * @return void
	 */
	public function testSanitizedMergeData() {
		$this->validator->setMergeData(['column' => 'value', 'column1' => 'value1']);
		$this->assertSameEquals(
			['column' => 'xxxxx', 'column1' => 'xxxxxx'],
			$this->validator->getSanitizedMergeData()
		);
	}

	/**
	 * @return array
	 */
	public function sanitizedTargetKeyDataProvider() {
		return [
			[123, 123],
			['123', '123'],
			['sdf4534tfgdg545', '4534545'],
			['45-_3_-454_545-645', '45-_3_-454_545-645'],
			['45-_3*()_-4rtr54_5dgfg45-645', '45-_3_-454_545-645'],
			['-_234_', '-_234_'],
			['-_234_dfr-45 5656_65', '-_234_-45 5656_65'],
		];
	}

	/**
	 * @dataProvider sanitizedTargetKeyDataProvider
	 * @param mixed $targetKey
	 * @param mixed $expected
	 * @return void
	 */
	public function testSanitizedTargetKey($targetKey, $expected) {
		$this->validator->setTargetKey($targetKey);
		$this->assertSameEquals($expected, $this->validator->getSanitizedTargetKey());
	}

	/**
	 * @expectedException Services\Exceptions\Validators\InvalidTargetKeyException
	 * @expectedExceptionMessage Wash campaign can not have non numeric target keys
	 * @return void
	 */
	public function testIsValidThrowsException() {
		$this->validator->setTargetKey('non-numeric');
		$this->validator->isValid();
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			['23423423423'],
			[2342342342],
			['RT-TEST-12323425'],
			['RT-API-345345-435345'],
			['RT-API-4534543'],
			['23430-4545_4545-'],
			['-_234_'],
			['2354 45345 -_43534-677']
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param mixed $targetKey
	 * @return void
	 */
	public function testIsValidReturnsTrue($targetKey) {
		$this->validator->setTargetKey($targetKey);
		$this->assertTrue($this->validator->isValid());
	}
}
