<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Models\Email\Dkim\DkimKey;
use testing\AbstractPhpunitTest;

/**
 * Class DkimKeyResolverTest
 */
class DkimKeyResolverTest extends AbstractPhpunitTest
{

	/**
	 * @return void
	 */
	public function testResolveWithCampaignSelector() {
		$fakeKey = \Phake::mock(DkimKey::class);
		\Phake::when($fakeKey)->getPublicKey(\Phake::anyParameters())->thenReturn("123xyz");

		$groupKeystore = \Phake::mock(GroupDkimKeystore::class);
		\Phake::when($groupKeystore)->getKeys(\Phake::anyParameters())->thenReturn([["value" => "fake-private-key"]]);
		\Phake::when($groupKeystore)->getDkimKey(\Phake::anyParameters())->thenReturn($fakeKey);
		$keystoreFactory = \Phake::mock(DkimKeystoreFactory::class);
		\Phake::when($keystoreFactory)->build(\Phake::anyParameters())->thenReturn($groupKeystore);

		$keyFactory = \Phake::mock(DkimKeyFactory::class);

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				["params" => [1, 'dkim'], "return" => "test-selector"]
			],
			false
		);

		$resolver = new DkimKeyResolver($keystoreFactory, $keyFactory);
		$resolver->setCampaign(1, 12);

		$resolver->resolve();
		$this->assertEquals("test-selector", $resolver->getResolvedDkimSelector());
		$this->assertEquals($fakeKey, $resolver->getResolvedKey());
	}

	/**
	 * @return void
	 */
	public function testResolveWithoutCampaignSelector() {
		$groupKeystore = \Phake::mock(GroupDkimKeystore::class);
		\Phake::when($groupKeystore)->getKeys(\Phake::anyParameters())->thenReturn([["value" => "fake-private-key"]]);
		\Phake::when($groupKeystore)->getSystemKey(\Phake::anyParameters())->thenReturn([["value" => "fake-system-private-key"]]);
		\Phake::when($groupKeystore)->getSystemSelector(\Phake::anyParameters())->thenReturn("test-selector");

		$keystoreFactory = \Phake::mock(DkimKeystoreFactory::class);
		\Phake::when($keystoreFactory)->build(\Phake::anyParameters())->thenReturn($groupKeystore);

		$keyFactory = \Phake::mock(DkimKeyFactory::class);
		\Phake::when($keyFactory)->createKey(\Phake::anyParameters())->thenReturn("123xyz");

		$this->mock_function_param_value(
			'api_campaigns_setting_getsingle',
			[
				["params" => [1, 'dkim'], "return" => null]
			],
			false
		);

		$resolver = new DkimKeyResolver($keystoreFactory, $keyFactory);
		$resolver->setCampaign(1, 12);
		$resolver->resolve();
		$this->assertEquals("test-selector", $resolver->getResolvedDkimSelector());
		$this->assertEquals("123xyz", $resolver->getResolvedKey());
	}

	/**
	 * @return void
	 */
	public function testResolveDefaultEmail() {

		$fakeKey = \Phake::mock(DkimKey::class);
		\Phake::when($fakeKey)->getPublicKey(\Phake::anyParameters())->thenReturn("123xyz");

		$keyFactory = \Phake::mock(DkimKeyFactory::class);

		$keystore = \Phake::mock(SystemDkimKeystore::class);
		\Phake::when($keystore)->getDkimKey(\Phake::anyParameters())->thenReturn($fakeKey);

		$keystoreFactory = \Phake::mock(DkimKeystoreFactory::class);
		\Phake::when($keystoreFactory)->build(
			DkimKeystoreTypeEnum::SYSTEM(),
			null,
			"testselector"
		)->thenReturn($keystore);

		$resolver = new DkimKeyResolver($keystoreFactory, $keyFactory);
		$resolver
			->setFromEmail("ReachTEL Support <support@ReachTEL.com.au>")
			->setDefaultDomain(EMAIL_DEFAULT_DOMAIN, "testselector");

		$resolver->resolve();
		$this->assertEquals("testselector", $resolver->getResolvedDkimSelector());
		$this->assertEquals($fakeKey, $resolver->getResolvedKey());
	}
}
