<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Validators\Disclaimers;

use Services\Campaign\Validators\Disclaimers\CallerIdDisclaimerProvider;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CallerIdDisclaimerProviderTest
 */
class CallerIdDisclaimerProviderTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testGetDisclaimer() {
		$disclaimer = new CallerIdDisclaimerProvider();
		$this->assertContains(
			"The Telecommunications (Telemarketing and Research Calls)",
			$disclaimer->getDisclaimer()
		);
	}
}
