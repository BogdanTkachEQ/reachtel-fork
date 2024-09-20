<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\unit\Services\Campaign\Builders;

use Phake;
use Services\Campaign\Builders\SpecificTimeBuilder;
use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class SpecificTimeBuilderUnitTest
 */
class SpecificTimeBuilderUnitTest extends AbstractPhpunitUnitTest
{
	/** @var SpecificTimeBuilder */
	private $builder;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->builder = new SpecificTimeBuilder();
	}

	/**
	 * @return void
	 */
	public function testGetSpecificTime() {
		$this->assertDefaults();
		$start = Phake::mock(\DateTime::class);
		$end = Phake::mock(\DateTime::class);
		$status = 2;
		$this->builder->setStartDateTime($start);
		$this->builder->setEndDateTime($end);
		$this->builder->setStatus($status);

		$specificTime = $this->builder->getSpecificTime();
		$this->assertSameEquals($start, $specificTime->getStartDateTime());
		$this->assertSameEquals($end, $specificTime->getEndDateTime());
		$this->assertSameEquals($status, $specificTime->getStatus());
		$this->builder->reset();
		$this->assertDefaults();
	}

	/**
	 * @return void
	 */
	private function assertDefaults() {
		$specificTime = $this->builder->getSpecificTime();
		$this->assertNull($specificTime->getStartDateTime());
		$this->assertNull($specificTime->getEndDateTime());
		$this->assertNull($specificTime->getStatus());
	}
}
