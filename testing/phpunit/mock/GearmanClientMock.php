<?php
/**
 * GearmanClient
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\mock;

/**
 * GearmanClient Mock class
 */
class GearmanClientMock extends \stdClass
{
	/**
	 * @var string
	 */
	const RUNNING_TASK_RESULT = 'running_task';

	/**
	 * @var string
	 */
	const GEARMAN_SUCCESS = 0;

	/**
	 * @var array
	 */
	static private $calls = [];

	/**
	 * Gearman predefined constants used in Morpheus
	 *
	 * @var array
	 * @see http://php.net/manual/en/gearman.constants.php
	 */
	private $constants_map = [
		'GEARMAN_SUCCESS' => self::GEARMAN_SUCCESS
	];

	/**
	 * @return array
	 */
	public static function getAllCalls() {
		return self::$calls;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		foreach ($this->constants_map as $name => $value) {
			if (!defined($name)) {
				define($name, $value);
			}
		}
	}

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return string
	 */
	public function __call($method, array $args = []) {
		switch ($method) {
			case 'do':
				return serialize(['supplierid' => self::RUNNING_TASK_RESULT]);

			case 'doHigh':
				return self::RUNNING_TASK_RESULT;

			case 'doHighBackground':
			case 'doLowBackground':
				return 'handle';

			case 'returnCode':
				return self::GEARMAN_SUCCESS;
		}

		self::$calls[spl_object_hash($this)][$method][] = [
			$args,
			debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file']
		];
	}
}
