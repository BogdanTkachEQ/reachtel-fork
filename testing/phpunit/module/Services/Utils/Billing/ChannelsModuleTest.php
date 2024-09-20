<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\Utils\Billing;

use Services\Utils\Billing\Channels;
use testing\module\AbstractPhpunitModuleTest;

/**
 * Class ChannelsModuleTest
 */
class ChannelsModuleTest extends AbstractPhpunitModuleTest
{
	/**
	 * @return void
	 */
	public function testGetChannelMap() {
		$expected = [
			'WEB' => ['id' => '1', 'code' => '48'],
			'API' => ['id' => '2', 'code' => '49'],
		];

		$actual = Channels::getChannelMap();

		$this->assertSameEquals($expected, $actual);
	}

	/**
	 * @return array
	 */
	public function getChannelIdByNameData() {
		return [
			'Web' => ['WEB', 1],
			'Api' => ['API', 2],
		];
	}

	/**
	 * @dataProvider getChannelIdByNameData
	 * @param string  $name
	 * @param integer $expected
	 * @return void
	 */
	public function testGetChannelIdByName($name, $expected) {
		$actual = Channels::getChannelIdByName($name);
		$this->assertSameEquals($expected, $actual);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Invalid channel name passed INVALID_NAME
	 * @return void
	 */
	public function testGetChannelIdByNameThrowsException() {
		Channels::getChannelIdByName('INVALID_NAME');
	}
}
