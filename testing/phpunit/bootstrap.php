<?php
/**
 * PHPUnit bootstrap
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

// @codeCoverageIgnoreStart
namespace testing;

ini_set('memory_limit', -1);
set_time_limit(0);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

define('APP_ENVIRONMENT', 'phpunit');
define('DISABLE_ACTIVITY_LOGGING', 1);
define('APP_PHPUNIT_PATH', __DIR__);
define('APP_TESTING_PATH', dirname(APP_PHPUNIT_PATH));
define('APP_ROOT_PATH', dirname(APP_TESTING_PATH));
define('YABBR_API_HOST_NAME', '');
define('YABBR_API_KEY', '');

/**
 * @param string $classname
 *
 * @return void
 */
function __autoload($classname) {
	$namespace = __NAMESPACE__;
	$pathclass = APP_ROOT_PATH . DIRECTORY_SEPARATOR . str_replace(
		$namespace,
		$namespace . '/phpunit',
		sprintf('%s.php', str_replace('\\', '/', $classname))
	);
	if (file_exists($pathclass)) {
		require_once($pathclass);
	}
}

spl_autoload_register(__NAMESPACE__ . '\__autoload');

// bootstrap code base
require_once(APP_ROOT_PATH . '/api.php');
// we use an old version of sfYaml because we execute PHP code in config.yml
// @see https://github.com/fabpot-graveyard/yaml
require_once APP_ROOT_PATH . '/vendor/fabpot-graveyard/yaml/lib/sfYaml.php';

// @codeCoverageIgnoreEnd
