<?php
/**
 * ApiInvoicingUnitTest
 * Unit test for api_invoicing.php
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit;

/**
 * Api Invoicing Unit Test class
 */
class ApiInvoicingUnitTest extends AbstractPhpunitUnitTest
{
	/**
	 * @codeCoverageIgnore
	 * @return array
	 */
	public function api_invoicing_lookup_data() {
		return [
			// failure invalid invoice number
			[false, '[{(!@#$%^&*();\'_+}]}'],

			// failure file does not exists
			[false, '123-456', null, false],

			// success group id is set
			[['groupid' => 'group-id'], '2-20150101-789', 'a:1:{s:7:"groupid";s:8:"group-id";}'],

			// success invoice number wrong format
			[[], '99999999'],
			[[], '[999-999]'],
			[[], '[123-20150920-ABC]'],

			// success
			[['groupid' => '123'], '123-20150920-14', 'a:0:{}'],
			[['groupid' => '123'], '[123-20150214-78]', 'a:0:{}'],
			[['groupid' => '123'], 'The invoice number is 123-20151129-90', 'a:0:{}'],
		];
	}

	/**
	 * @group api_invoicing_lookup
	 * @dataProvider api_invoicing_lookup_data
	 * @param mixed   $expected_value
	 * @param string  $invoicenumber
	 * @param string  $invoice
	 * @param boolean $file_exists
	 * @return void
	 */
	public function test_api_invoicing_lookup($expected_value, $invoicenumber = '12-20151209-9', $invoice = 'a:0:{}', $file_exists = true) {
		$this->mock_function_value('file_exists', $file_exists);
		$this->mock_function_value('file_get_contents', $invoice);

		$this->assertSameEquals($expected_value, api_invoicing_lookup($invoicenumber));
	}
}
