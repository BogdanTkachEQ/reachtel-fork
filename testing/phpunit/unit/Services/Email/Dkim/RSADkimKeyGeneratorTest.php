<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Models\Email\Dkim\DkimKey;
use testing\AbstractPhpunitTest;

/**
 * Class RSADkimKeyGeneratorTest
 */
class RSADkimKeyGeneratorTest extends AbstractPhpunitTest {

	/**
	 * @return void
	 */
	public function testCreateKey() {
		$this->assertInstanceOf(DkimKey::class, (new RSADkimKeyGenerator())->createKey(new DkimKeyFactory()));
	}
}
