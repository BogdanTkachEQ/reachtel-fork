<?php
/**
 * GUI bootstrap
 *
 * @author			nick.adams@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 */

define("PROFILE_USE", "morpheus_gui");

require_once("api.php");

if (isset($_GET['profile']) || isset($_POST['profile'])) {
	xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	$PROFILE_STARTED = true;
	register_shutdown_function('api_misc_profiling_save');
}

if (defined('EXTEND_SESSION')) {
	api_session_checklogin(EXTEND_SESSION);
} else {
	api_session_checklogin();
}

// Check for morpheus access
api_security_check(ZONE_MORPHEUS_ACCESS);
