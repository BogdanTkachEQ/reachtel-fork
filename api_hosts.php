<?php
/**
 * Hosts Functions
 *
 * @author			kevin.ohayon@reachtel.com.au
 * @copyright		ReachTel (ABN 40 133 677 933)
 * @testCoverage	full
 */

/**
 * Get ReachTEL track host
 *
 * @param boolean $scheme
 * @return string
 */
function api_hosts_gettrack($scheme = true) {
	$host = 'track.reachtel.com.au';
	if (defined('REACHTEL_HOST_TRACK')) {
		$host = constant('REACHTEL_HOST_TRACK');
	}

	return ($scheme ? sprintf('https://%s', $host) : $host);
}
