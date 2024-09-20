<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Phake;
use Services\Campaign\Cloner\GenericCampaignCloner;
use Services\Campaign\GenericCampaignCreator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class GenericCampaignCreatorUnitTest
 */
class GenericCampaignCreatorUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testCreate() {
		$cloner = Phake::mock(GenericCampaignCloner::class);
		$newId = 4545;
		$name = 'test-campaign';
		$ownerId = 343;
		$sourceCampaignId = 676;
		Phake::when($cloner)->setOwnerId($ownerId)->thenReturn($cloner);
		Phake::when($cloner)->cloneCampaign($sourceCampaignId, $name)->thenReturn($newId);

		$creator = new GenericCampaignCreator($cloner);
		$this->assertSameEquals($newId, $creator->create($name, $sourceCampaignId, $ownerId));
	}
}
