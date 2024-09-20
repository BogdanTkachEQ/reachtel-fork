<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Name;

use Services\Validators\CampaignNameValidator;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class TokenDateCampaignNameTest
 */
class TokenDateCampaignNameTest extends AbstractPhpunitUnitTest {

	/**
	 * @return array
	 */
	public function provider() {
		return [
			["{PREFIX:MakeAWish}-{TOKEN}-{DATE:Y-m}", "VOL", new \DateTime("2020-01-01"), "MakeAWish-VOL-2020-01"],
			["{PREFIX:MakeAWish}-{DATE:Y-M}-{TOKEN}", "RG", new \DateTime("2020-05-01"), "MakeAWish-2020-May-RG"],
			["{PREFIX:MakeAWish}-{TOKEN}-{DATE:Ymd}", "R AND G", new \DateTime("2020-05-01"), "MakeAWish-R AND G-20200501"],
			["{PREFIX:MakeAWish}-{TOKEN}-{DATE:Ymd}", "Vol", new \DateTime("2020-10-01"), "MakeAWish-Vol-20201001"],
			["{PREFIX:MakeAWish}-{TOKEN}-{DATE:YM}", "Vol", new \DateTime("2020-05-01"), "MakeAWish-Vol-2020May"],
		];
	}

	/**
	 * @dataProvider provider
	 * @param string $template
	 * @param string $token
	 * @param string $date
	 * @param string $expected
	 * @return void
	 */
	public function testGetName($template, $token, $date, $expected) {
		$namer = new TokenDateTemplateCampaignName($template, $token, $date, new CampaignNameValidator());
		$this->assertEquals($expected, $namer->getName());
	}

	/**
	 * @return void
	 */
	public function testGetNameOverriddenDate() {
		$namer = new TokenDateTemplateCampaignName(
			"{PREFIX:MakeAWish}-{TOKEN}-{DATE:Y-m}",
			"VOL",
			new \DateTime("2020-01-01"),
			new CampaignNameValidator()
		);
		$this->assertEquals("MakeAWish-VOL-*", $namer->getSearchableName());
	}

	/**
	 * @return void
	 */
	public function testGetNameOverriddenDateWithInvalidCharacters() {
		$namer = new TokenDateTemplateCampaignName(
			"{PREFIX:MakeAWish}-{TOKEN}-{DATE:Y-m}",
			"VOL_invalid",
			new \DateTime("2020-01-01"),
			new CampaignNameValidator()
		);
		$this->assertEquals("MakeAWish-VOLinvalid-*", $namer->getSearchableName());
	}
}
