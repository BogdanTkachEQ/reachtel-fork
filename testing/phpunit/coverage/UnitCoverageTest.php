<?php
/**
 * UnitCoverageTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\coverage;

use SimpleXMLElement;

/**
 * Unit Coverage Test
 */
class UnitCoverageTest extends AbstractCoverageTest
{
	const TEST_TYPE = 'unit';

	/**
	 * @return array
	 */
	public function getTestGroups() {
		return $this->getApiGroups();
	}

	/**
	 * @param SimpleXMLElement $xml
	 * @return mixed
	 */
	protected function getXMLRootDirectory(SimpleXMLElement $xml) {
		return $xml->project->directory;
	}
}
