<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Validators;

use Services\Validators\CampaignNameValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CampaignNameValidatorUnitTest
 */
class CampaignNameValidatorUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testSetName() {
		$validator = new CampaignNameValidator();
		$name = 'test_name';
		$this->assertSameEquals($validator, $validator->setName($name));
		$this->assertSameEquals($name, $validator->getName());
	}

	/**
	 * @return array
	 */
	public function isValidDataProvider() {
		return [
			['test_name', false],
			['tEsT@name', false],
			['TesT  Na-me', true]
		];
	}

	/**
	 * @dataProvider isValidDataProvider
	 * @param string  $name
	 * @param boolean $expected
	 * @return void
	 */
	public function testIsValid($name, $expected) {
		$validator = new CampaignNameValidator();
		$validator
			->setName($name);

		$this->assertSameEquals($expected, $validator->isValid());
	}

	/**
	 * @return array
	 */
	public function isSanitizeProvider() {
		return [
			['test_name', 'testname'],
			['tEsT@name', 'tEsTname'],
			['TesT  Na-me', 'TesT  Na-me']
		];
	}

	/**
	 * @dataProvider isSanitizeProvider
	 * @param string $name
	 * @param string $expected
	 * @return void
	 */
	public function testSanitizeName($name, $expected) {
		$validator = new CampaignNameValidator();
		$validator->setName($name);
		$this->assertSameEquals($expected, $validator->sanitizeName());
	}
}
