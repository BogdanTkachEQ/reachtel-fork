<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign;

use Phake;
use Services\Campaign\CascadingCampaignCreatorWrapper;
use Services\Campaign\Hooks\Cascading\Creators\AbstractGenericCascadingCampaignCreator;
use Services\Campaign\Hooks\Cascading\Creators\CascadingCampaignCreatorFactory;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class CascadingCampaignCreatorWrapperUnitTest
 */
class CascadingCampaignCreatorWrapperUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @return void
	 */
	public function testCreate() {
		$factory = Phake::mock(CascadingCampaignCreatorFactory::class);
		$name = 'test-campaign';
		$sourceCampaignId = 56;
		$ownerId = 456;
		$newId = 123;
		$creator = Phake::mock(AbstractGenericCascadingCampaignCreator::class);
		Phake::when($creator)->setupNextCampaign(Phake::capture($isActive))->thenReturn($newId);
		Phake::when($factory)->makeCreator($sourceCampaignId, $name, $ownerId)->thenReturn($creator);

		$cascadingCreatorWrapper = new CascadingCampaignCreatorWrapper($factory);
		$this->assertSameEquals($newId, $cascadingCreatorWrapper->create($name, $sourceCampaignId, $ownerId));
		$this->assertFalse($isActive);
	}
}
