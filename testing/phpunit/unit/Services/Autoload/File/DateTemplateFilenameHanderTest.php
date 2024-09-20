<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\File;

use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DateTemplateFilenameHanderTest
 */
class DateTemplateFilenameHanderTest extends AbstractPhpunitUnitTest
{

	/**
	 * @return array
	 */
	public function fileNameProvider() {
		return [
			['201905Vol.csv', "{Date:Ym}{TOKEN}", "201905", "Vol"],
			['201905RG.csv', "{Date:Ym}{TOKEN}", "201905", "RG"],
			['201905CF&E.csv', "{Date:Ym}{TOKEN}", "201905", "CF&E"],
			['201905IG.csv', "{Date:Ym}{TOKEN}", "201905", "IG"],
			['201905WPG.csv', "{Date:Ym}{TOKEN}", "201905", "WPG"],
			['201905WPG-XYZ.csv', "{Date:Ym}{TOKEN}", "201905", "WPG-XYZ"],
			['2019WPG-XYZ.csv', "{Date:Y}{TOKEN}", "2019", "WPG-XYZ"],
			['19WPG-XYZ.csv', "{Date:y}{TOKEN}", "19", "WPG-XYZ"],
			['WPG201905.csv', "{TOKEN}{Date:Ym}", "201905", "WPG"],
			['WPG3201905.csv', "{TOKEN}{Date:Ym}", "201905", "WPG3"],
			['WPG3-sd-20200102.csv', "{TOKEN}{Date:Ymd}", "20200102", "WPG3-sd"],
			['WPG3-sd-20200102.csv', "{TOKEN}{Date:Ymd}", "20200102", "WPG3-sd"],
		];
	}

	/**
	 * @dataProvider fileNameProvider
	 * @param string $filename
	 * @param string $template
	 * @param string $expectedDate
	 * @return void
	 */
	public function testGetDate($filename, $template, $expectedDate) {
		$dt = new DateTemplateFilenameHandler($filename, $template);
		$this->assertEquals($expectedDate, $dt->getDate()->format($dt->getDateFormatPortion()));
	}

	/**
	 * @dataProvider fileNameProvider
	 * @param string $filename
	 * @param string $template
	 * @param string $expectedDate
	 * @param string $expectedToken
	 * @return void
	 */
	public function testGetToken($filename, $template, $expectedDate, $expectedToken) {
		$dt = new DateTemplateFilenameHandler($filename, $template);
		$this->assertEquals($expectedToken, $dt->getToken());
	}

	/**
	 * @dataProvider fileNameProvider
	 * @param string $filename
	 * @param string $template
	 * @param string $expectedDate
	 * @return void
	 */
	public function testGetDatePortion($filename, $template, $expectedDate) {
		$dt = new DateTemplateFilenameHandler($filename, $template);
		$this->assertEquals($expectedDate, $dt->getDateFormatPortion());
	}
}
