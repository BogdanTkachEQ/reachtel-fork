<?php
/**
 * AssetModuleHelperTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Assets Module Helper Test
 */
class AssetModuleHelperTest extends AbstractModuleHelperTest
{
	use AssetModuleHelper;

	const EXPECTED_TYPE = 'ASSET';

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function create_new_asset_data() {
		return [
			[],
			[uniqid('test') . '.pdf'],
			[uniqid('test') . '.png'],
			[uniqid('test') . '.jpeg'],
			[uniqid('test') . '.jpg'],
			[uniqid('test') . '.png'],
			[uniqid('test') . '.gif'],
		];
	}

	/**
	 * @group create_new_asset
	 * @dataProvider create_new_asset_data
	 * @param string $asset_filename
	 * @return void
	 */
	public function test_create_new_asset($asset_filename = null) {
		$expected_id = $this->get_expected_next_asset_id();
		$asset = $this->create_new_asset($asset_filename);

		// return an array
		$this->assertInternalType('array', $asset);
		$this->assertCount(2, $asset);

		// check array keys
		$this->assertArrayHasKey('id', $asset);
		$this->assertArrayHasKey('filename', $asset);

		// check array content
		$this->assertEquals($expected_id, $asset['id']);
		if ($asset_filename) {
			$this->assertSameEquals($asset_filename, $asset['filename']);
		}

		$this->assertTrue(api_asset_delete($asset['id']));
	}

	/**
	 * @group get_asset_valid_extensions
	 * @return void
	 */
	public function test_get_asset_valid_extensions() {
		$this->assertSameEquals(['png', 'jpg', 'jpeg', 'gif', 'pdf'], $this->get_asset_valid_extensions());
	}
}
