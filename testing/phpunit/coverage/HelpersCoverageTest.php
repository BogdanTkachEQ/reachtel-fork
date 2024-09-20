<?php
/**
 * UnitCoverageTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\coverage;

use Devsdmf\Annotations\Reader;
use LogicException;
use ReflectionClass;
use SimpleXMLElement;

/**
 * Unit Coverage Test
 */
class HelperCoverageTest extends AbstractCoverageTest
{
	const TEST_TYPE = 'helpers';

	/**
	 * @return array
	 * @throws LogicException If no helper files found.
	 */
	public function getTestGroups() {
		if (!$this->groups) {
			$file_filter = getenv(self::ENV_VAR_FILE_FILTER_NAME);
			$function_filter = getenv(self::ENV_VAR_FUNCTION_FILTER_NAME);

			foreach (glob(APP_PHPUNIT_PATH . '/module/helpers/*Helper.php') as $file) {
				// apply file filter
				if ($file_filter && $file_filter != basename($file)) {
					continue;
				}

				$className = pathinfo(basename($file), PATHINFO_FILENAME);
				$ref = new ReflectionClass("\\testing\\module\\helpers\\{$className}");
				$this->assertInstanceOf(ReflectionClass::class, $ref);

				foreach ($ref->getMethods() as $refMethod) {
					if ($file == $refMethod->getFileName()) {
						$reader = new Reader();
						if (is_null($reader->getAnnotation($refMethod, 'codeCoverageIgnore'))) {
							$this->groups[] = [
								$file,
								$refMethod->name
							];
						}
					}
				}
			}

			if (!$this->groups) {
				throw new LogicException("No helper files found for file filter = '{$file_filter}'");
			}
		}

		return $this->groups;
	}

	/**
	 * @param string $file
	 * @return string
	 */
	protected function getTestFile($file) {
		return str_replace('Helper.php', 'HelperTest.php', $file);
	}

	/**
	 * @param SimpleXMLElement $xml
	 * @return mixed
	 * @throws \RuntimeException If helper XML element not found.
	 */
	protected function getXMLRootDirectory(SimpleXMLElement $xml) {
		$rootDirectory = $xml->project->directory;
		foreach ($rootDirectory->directory as $directory) {
			if ($directory->attributes()['name'] === 'testing') {
				break;
			}
		}

		$directories = $directory->directory;

		foreach ($directories->children() as $child) {
			if ('directory' == $child->getName()) {
				$attr = (array) $child->attributes();
				if (isset($attr['@attributes']['name'])
					&& 'helpers' == $attr['@attributes']['name']) {
						return $child;
						break;
				}
			}
		}

		throw new \RuntimeException('Helper XML element not found');
	}
}
