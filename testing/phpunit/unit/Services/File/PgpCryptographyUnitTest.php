<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\File;

use Services\File\PgpCryptography;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class PgpCryptographyUnitTest
 */
class PgpCryptographyUnitTest extends AbstractPhpunitUnitTest
{
	/** @var PgpCryptography */
	private $pgpCrypt;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->pgpCrypt = new PgpCryptography();
	}

	/**
	 * @expectedException Services\Exceptions\File\CryptoException
	 * @expectedExceptionMessage File to encrypt not set
	 * @return void
	 */
	public function testEncryptThrowsExceptionWhenFileNotSet() {
		$this->pgpCrypt->encrypt();
	}

	/**
	 * @expectedException Services\Exceptions\File\CryptoException
	 * @expectedExceptionMessage File to decrypt not set
	 * @return void
	 */
	public function testDecryptThrowsExceptionWhenFileNotSet() {
		$this->pgpCrypt->decrypt();
	}

	/**
	 * @expectedException Services\Exceptions\File\CryptoException
	 * @expectedExceptionMessage Key to encrypt needs to be set
	 * @return void
	 */
	public function testEncryptThrowsExceptionWhenKeysNotSet() {
		$this->pgpCrypt->setFile('testfile.csv');
		$this->pgpCrypt->encrypt();
	}

	/**
	 * @expectedException Services\Exceptions\File\CryptoException
	 * @expectedExceptionMessage Error happened when encrypting file
	 * @return void
	 * @throws \Exception Error happened when encrypting file.
	 */
	public function testEncryptThrowsExceptionWhenEncryptionFails() {
		$file = tempnam('/tmp', 'testfile');
		$data = 'test data';
		file_put_contents($file, $data);
		$key = 'testkey';
		$this->pgpCrypt->setFile($file)->setKeys([$key]);
		$this->mock_function_param_value(
			'api_misc_pgp_encrypt',
			[
				['params' => [['content' => $data, 'filename' => ''], $key], 'return' => false]
			],
			true
		);

		try {
			$this->pgpCrypt->encrypt();
		} catch (\Exception $exception) {
			unlink($file);
			throw $exception;
		}
	}

	/**
	 * @return void
	 */
	public function testEncrypt() {
		$file = tempnam('/tmp', 'testfile');
		$data = 'test data';
		file_put_contents($file, $data);
		$key = 'testkey';
		$this->pgpCrypt->setFile($file)->setKeys([$key]);
		$encrypted = ['content' => 'xxxxxxxxx'];
		$this->mock_function_param_value(
			'api_misc_pgp_encrypt',
			[
				['params' => [['content' => $data, 'filename' => ''], $key], 'return' => $encrypted]
			],
			false
		);

		$this->assertSameEquals('xxxxxxxxx', $this->pgpCrypt->encrypt());
		unlink($file);
	}

	/**
	 * @expectedException Services\Exceptions\File\CryptoException
	 * @expectedExceptionMessage Error happened when decrypting file
	 * @return void
	 * @throws \Exception Error happened when decrypting file.
	 */
	public function testDecryptThrowsExceptionWhenItFails() {
		$file = tempnam('/tmp', 'testfile');
		$data = 'xxxxxx';
		file_put_contents($file, $data);
		$this->pgpCrypt->setFile($file);
		$this->mock_function_param_value(
			'api_misc_pgp_decrypt',
			[
				['params' => [$data], 'return' => false]
			],
			true
		);

		try {
			$this->pgpCrypt->decrypt();
		} catch (\Exception $exception) {
			unlink($file);
			throw $exception;
		}
	}

	/**
	 * @return void
	 */
	public function testDecrypt() {
		$file = tempnam('/tmp', 'testfile');
		$data = 'xxxxxxxxxx';
		file_put_contents($file, $data);
		$this->pgpCrypt->setFile($file);
		$decrypted = 'Test Data';
		$this->mock_function_param_value(
			'api_misc_pgp_decrypt',
			[
				['params' => [$data], 'return' => $decrypted]
			],
			false
		);

		$this->assertSameEquals($decrypted, $this->pgpCrypt->decrypt());
		unlink($file);
	}
}
