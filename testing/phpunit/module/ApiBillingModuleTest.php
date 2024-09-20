<?php
/**
 * ApiBillingModuleTest
 * Module test for api_billing.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module;

/**
 * Api billing Module Test
 */
class ApiBillingModuleTest extends AbstractPhpunitModuleTest
{
	const DEFAULT_MAX_IDS = 32;

	/**
	 * @group api_billing_products_listall
	 * @return void
	*/
	public function test_api_billing_products_listall() {
		$products = api_billing_products_listall();
		$this->assertInternalType('array', $products);
		$this->assertGreaterThanOrEqual(self::DEFAULT_MAX_IDS, count($products)); // min 40 products
		foreach ($products as $product) {
			// assert each product values
			$this->assertArrayHasKey('status', $product);
			$this->assertArrayHasKey('billing_type_id', $product);
			$this->assertArrayHasKey('name', $product);
			$this->assertArrayHasKey('code', $product);
			$this->assertArrayHasKey('created', $product);
			$this->assertArrayHasKey('updated', $product);
			$this->assertArrayHasKey('category_name', $product);
			// assert number of values
			$this->assertCount(7, $product);
			// assert products are enabled by default
			$this->assertSameEquals('1', $product['status']);
			// assert product code match format
			$this->assertRegExp('/^RT[0-9]{2}$/', $product['code']);
		}

		// invalid hydrator
		$this->assertFalse(api_billing_products_listall('whatever_hydrator'));
		$this->assertSameEquals(
			"Sorry, 'whatever_hydrator' is not a valid product hydrator",
			api_error_printiferror(['return' => true])
		);

		// status hydrator
		$products = api_billing_products_listall('status');
		$this->assertInternalType('array', $products);
		$this->assertCount(2, $products);
		$this->assertArrayHasKey(0, $products);
		$this->assertArrayHasKey(1, $products);

		// billing_type_id hydrator
		$products = api_billing_products_listall('billing_type_id');
		$this->assertInternalType('array', $products);
		$billingTypeIds = array_keys(api_billing_products_billingtypes_listall());
		$this->assertCount(count($billingTypeIds), $products);
		foreach ($billingTypeIds as $billingTypeId) {
			$this->assertArrayHasKey($billingTypeId, $products);
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_billing_products_add_data() {
		return [
			// failures
			'invalid product name' => [false, '(wh@t*ver)', 1, 'RT99', 'Sorry, that is not a valid product name'],
			'invalid product name plus' => [false, 'product + name', 2, 'RT98', 'Sorry, that is not a valid product name'],
			'invalid product billing type' => [false, 'product name', 999, 'RT97', 'Sorry, that is not a valid product billing type'],
			'invalid product billing code' => [false, 'product name', 1, 'Whatever', 'Sorry, that is not a valid product code'],
			'invalid product name already exists' => [
				false,
				'Email - Email Unit',
				2,
				'RT99',
				"Sorry, this product could not be created: Already exists"
			],
			'invalid billing code already exists' => [
				false,
				'product name',
				1,
				'RT01',
				"Sorry, this product could not be created: Already exists"
			],
			// success
			'valid product without code' => [true, uniqid('product name'), 1],
			'valid product with code (NEEDS DB RELOAD)' => [true, uniqid('product name'), 1, 'RT99'],
		];
	}

	/**
	 * @group api_billing_products_add
	 * @dataProvider api_billing_products_add_data
	 * @param boolean $expected
	 * @param mixed   $name
	 * @param mixed   $billingType
	 * @param mixed   $code
	 * @param mixed   $error
	 * @return void
	 */
	public function test_api_billing_products_add($expected, $name, $billingType, $code = null, $error = false) {
		$countBefore = count(api_billing_products_listall());
		$value = api_billing_products_add($name, $billingType, $code);
		if ($expected) {
			$this->assertInternalType('integer', $value);
			$this->assertCount($countBefore + 1, api_billing_products_listall());
		} else {
			$this->assertFalse($value);
			$this->assertCount($countBefore, api_billing_products_listall());
		}
		$this->assertSameEquals(
			$error,
			api_error_printiferror(['return' => true])
		);
	}

	/**
	 * @group api_billing_products_getbyid
	 * @return void
	 */
	public function test_api_billing_products_getbyid() {
		// does not exists
		$this->assertFalse(api_billing_products_getbyid(999));
		// exists
		$product = api_billing_products_getbyid(1);
		$this->assertArrayHasKey('id', $product, 'Key id:');
		$this->assertArrayHasKey('status', $product, 'Key status:');
		$this->assertArrayHasKey('billing_type_id', $product, 'Key billing_type_id:');
		$this->assertArrayHasKey('name', $product, 'Key name:');
		$this->assertArrayHasKey('code', $product, 'Key code:');
		$this->assertArrayHasKey('created', $product, 'Key created:');
		$this->assertArrayHasKey('updated', $product, 'Key updated:');
		// assert number of values
		$this->assertCount(7, $product);
		// assert some values
		$this->assertSameEquals('1', $product['id'], 'Product id:');
		$this->assertSameEquals('1', $product['status'], 'Product status:');
		$this->assertSameEquals('1', $product['billing_type_id'], 'Product billing type id:');
		$this->assertSameEquals('Number Wash - Fixed Line - Australia', $product['name'], 'Product name:');
		$this->assertSameEquals('RT01', $product['code'], 'Product code:');
		$this->assertRegExp(
			'/^\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}$/',
			$product['created'],
			'Product created date format:'
		);
		$this->assertNull($product['updated'], 'Product updated date:');
	}

	/**
	 * @group api_billing_products_setstatus
	 * @return void
	 */
	public function test_api_billing_products_setstatus() {
		// invalid id
		$this->assertFalse(api_billing_products_setstatus(999, 1));
		$this->assertSameEquals(
			'Sorry, that is not a valid product id',
			api_error_printiferror(['return' => true])
		);
		// valid id
		$id = array_rand(api_billing_products_listall());
		$this->assertTrue(
			api_billing_products_setstatus($id, false), // disable
			"Disable product {$id}"
		);
		$product = api_billing_products_getbyid($id);
		$this->assertSameEquals('0', $product['status']);
		$this->assertTrue(
			api_billing_products_setstatus($id, true),  // re-enable
			"Enable product {$id}"
		);
		$product = api_billing_products_getbyid($id);
		$this->assertSameEquals('1', $product['status']);
	}

	/**
	 * @group api_billing_products_update
	 * @return void
	 */
	public function test_api_billing_products_update() {
		// invalid id
		$this->assertFalse(api_billing_products_update(999, 'name', 1), 'invalid id');
		$this->assertSameEquals(
			'Sorry, that is not a valid product id',
			api_error_printiferror(['return' => true])
		);
		$this->assertFalse(api_billing_products_update(['array not scalar :)'], 'name', 2), 'invalid id');
		$this->assertSameEquals(
			'Sorry, that is not a valid product id',
			api_error_printiferror(['return' => true])
		);

		// invalid name
		$this->assertFalse(api_billing_products_update(1, '!!b@d_name!!', 1), 'invalid name');
		$this->assertSameEquals(
			'Sorry, that is not a valid product name',
			api_error_printiferror(['return' => true])
		);

		// invalid name
		$this->assertFalse(api_billing_products_update(1, 'name', 9999), 'invalid billing type');
		$this->assertSameEquals(
			'Sorry, that is not a valid product billing type',
			api_error_printiferror(['return' => true])
		);

		// valid update of random product
		$id = array_rand(api_billing_products_listall());
		$oldProduct = api_billing_products_getbyid($id);
		$name = uniqid('NEW PRODUCT NAME');
		$billingType = array_rand(api_billing_products_billingtypes_listall());
		$this->assertTrue(api_billing_products_update($id, $name, $billingType), 'valid update');
		$newProduct = api_billing_products_getbyid($id);
		$this->assertSameEquals($name, $newProduct['name']);
		$this->assertSameEquals($billingType, (int) $newProduct['billing_type_id']);

		// save values back to original
		$this->assertTrue(
			api_billing_products_update($id, $oldProduct['name'], $oldProduct['billing_type_id']),
			'save values back to original'
		);
		$newProduct = api_billing_products_getbyid($id);
		$this->assertSameEquals($oldProduct['name'], $newProduct['name']);
		$this->assertSameEquals($oldProduct['billing_type_id'], $newProduct['billing_type_id']);
	}

	/**
	 * @group api_billing_products_billingtypes_listall
	 * @return void
	 */
	public function test_api_billing_products_billingtypes_listall() {
		$this->assertSameEquals(
			[
				1 => [
					'name' => 'Daily',
					'description' => 'Products sent daily',
				],
				2 => [
					'name' => 'Adhoc',
					'description' => 'One-off products',
				],
			],
			api_billing_products_billingtypes_listall()
		);

		// invalid hydrator
		$this->assertFalse(api_billing_products_billingtypes_listall('whatever_hydrator'));
		$this->assertSameEquals(
			"Sorry, 'whatever_hydrator' is not a valid billing type hydrator",
			api_error_printiferror(['return' => true])
		);

		$this->assertSameEquals(
			[
				1 => 'Daily',
				2 => 'Adhoc',
			],
			api_billing_products_billingtypes_listall('billing_type_name')
		);
	}

	/**
	 * @return void
	 */
	public function test_api_billing_get_sms_products_config() {
		$expected = [
			1 => '24',
			2 => '25',
			3 => '26',
			4 => '27',
			5 => '28',
			6 => '29',
		];

		$this->assertSameEquals($expected, api_billing_get_sms_products_config());

		$expected_with_region_id = [
			2 => '25',
			3 => '26',
			4 => '27',
		];

		$this->assertSameEquals($expected_with_region_id, api_billing_get_sms_products_config([2, 3, 4]));
	}

	/**
	 * @return void
	 */
	public function test_api_billing_get_wash_products_config() {
		$expected = [
			['region_id' => '1', 'destination_type_id' => '2', 'product_id' => '1'],
			['region_id' => '2', 'destination_type_id' => '2', 'product_id' => '2'],
			['region_id' => '6', 'destination_type_id' => '2', 'product_id' => '3'],
			['region_id' => '1', 'destination_type_id' => '1', 'product_id' => '4'],
			['region_id' => '2', 'destination_type_id' => '1', 'product_id' => '5'],
			['region_id' => '3', 'destination_type_id' => '1', 'product_id' => '6'],
			['region_id' => '4', 'destination_type_id' => '1', 'product_id' => '7'],
			['region_id' => '6', 'destination_type_id' => '1', 'product_id' => '8'],
			['region_id' => '6', 'destination_type_id' => '13', 'product_id' => '33'],
		];

		$this->assertSameEquals($expected, api_billing_get_wash_products_config());

		$expected_with_region_id = [
			['region_id' => '2', 'destination_type_id' => '2', 'product_id' => '2'],
			['region_id' => '2', 'destination_type_id' => '1', 'product_id' => '5'],
			['region_id' => '3', 'destination_type_id' => '1', 'product_id' => '6'],
			['region_id' => '4', 'destination_type_id' => '1', 'product_id' => '7'],
		];

		$this->assertSameEquals($expected_with_region_id, api_billing_get_wash_products_config([2, 3, 4]));
	}

	/**
	 * @return void
	 */
	public function test_api_billing_get_email_products_config() {
		$this->assertSameEquals(
			[['product_id' => '9']],
			api_billing_get_email_products_config()
		);
	}

	/**
	 * @return void
	 */
	public function test_api_billing_get_phone_products_config() {
		$expected = [
			[
				'region_id' => '1',
				'destination_type_id' => '1',
				'product_id' => '10',
				'interval' => 'first',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '1',
				'product_id' => '11',
				'interval' => 'next',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '2',
				'product_id' => '12',
				'interval' => 'first',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '2',
				'product_id' => '13',
				'interval' => 'next',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '5',
				'product_id' => '14',
				'interval' => 'first',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '5',
				'product_id' => '15',
				'interval' => 'next',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '3',
				'product_id' => '16',
				'interval' => 'first',
			],
			[
				'region_id' => '1',
				'destination_type_id' => '3',
				'product_id' => '17',
				'interval' => 'next',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '1',
				'product_id' => '18',
				'interval' => 'first',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '1',
				'product_id' => '19',
				'interval' => 'next',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '2',
				'product_id' => '20',
				'interval' => 'first',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '2',
				'product_id' => '21',
				'interval' => 'next',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '3',
				'product_id' => '22',
				'interval' => 'first',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '3',
				'product_id' => '23',
				'interval' => 'next',
			],
		];

		$this->assertSameEquals($expected, api_billing_get_phone_products_config());

		$expected = [
			[
				'region_id' => '2',
				'destination_type_id' => '1',
				'product_id' => '18',
				'interval' => 'first',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '1',
				'product_id' => '19',
				'interval' => 'next',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '2',
				'product_id' => '20',
				'interval' => 'first',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '2',
				'product_id' => '21',
				'interval' => 'next',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '3',
				'product_id' => '22',
				'interval' => 'first',
			],
			[
				'region_id' => '2',
				'destination_type_id' => '3',
				'product_id' => '23',
				'interval' => 'next',
			],
		];

		$this->assertSameEquals($expected, api_billing_get_phone_products_config([2]));
	}
}
