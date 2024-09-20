<?php
/**
 * ModuleCoverageTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\coverage;

use SimpleXMLElement;

/**
 * Module Coverage Test
 */
class ModuleCoverageTest extends AbstractCoverageTest
{
	const TEST_TYPE = 'module';

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
