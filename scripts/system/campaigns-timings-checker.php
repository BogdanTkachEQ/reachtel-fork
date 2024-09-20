<?php
/**
 * Check campaigns that does not match timing rules
 *
 * Cron tags:
 *  - database: Set 'slave' to use DB2
 *  - active-only: Search active campaigns only
 *  - search: Expression language search (https://symfony.com/doc/3.4/components/expression_language/syntax.html)
 *  - user-id: Campaign owned by user id
 *  - force-classification-set: Campaign that have classification value
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

require_once(__DIR__ . "/../../api.php");

use Services\Campaign\CampaignActivationPermissionService;
use Services\Container\ContainerAccessor;
use Services\Exceptions\Campaign\Validators\ValidationDisclaimerException;
use Symfony\Component\ExpressionLanguage as Exp;

// no cron id set up
$cron_id = getenv(CRON_ID_ENV_KEY);
$tags = api_cron_tags_get($cron_id);

// optionally use secondary db
if (isset($tags['database']) && 'slave' === $tags['database']) {
	print "Using '{$tags['database']}' database.\n";
	api_db_switch_connection(null, null, null, DB_MYSQL_READ_HOST_FORCED);
}

$options = [];
$title = "Listing ";
if (isset($tags['active-only']) && $tags['active-only']) {
	$options['active-only'] = true;
	$title .= "active ";
} else {
	$title .= "all ";
}

$title .= "campaigns ";

$expression = false;
if (isset($tags['search']) && $tags['search']) {
	$expression = $tags['search'];
	$title .= "like '{$expression}' ";
	$expressionLanguage = new Exp\ExpressionLanguage();
}

$userid = false;
if (isset($tags['user-id']) && $tags['user-id']) {
	$userid = $tags['user-id'];
	$title .= "with userid = {$userid} ";
}


$checkclassification = false;
if (isset($tags['force-classification-set']) && $tags['force-classification-set']) {
	$title .= "and classification is set ";
	$checkclassification = true;
}

echo "{$title}:\n";
foreach (api_campaigns_list_all(null, $userid, false, $options) as $campaignId) {
	if ($checkclassification) {
		$classification = api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_CLASSIFICATION);
		if (!$classification) {
			continue;
		}
	}

	// Apply search expression language filter
	$expressionError = false;
	if ($expression) {
		$settings = api_campaigns_setting_getall($campaignId);
		try {
			if (!$expressionLanguage->evaluate($expression, $settings)) {
				continue;
			}
		} catch (Exception $e) {
			echo "{$settings['name']} (id#{$campaignId})\n";
			echo "  ERROR in 'search' tag: " . $e->getMessage() . "\n\n";
			continue;
		}
	}

	$timings = api_restrictions_time_structure($campaignId);
	$hasTimings = (count($timings['recurring']) || count($timings['specific']));

	// check we have some timings and can not be activated
	if ($hasTimings) {
		$campaignName = api_campaigns_setting_getsingle($campaignId, 'name');

		try {
			$canBeActivated = ContainerAccessor::getContainer()
				->get(CampaignActivationPermissionService::class)
				->canBeActivated($campaignId);
			$reason = 'ACMA';
		} catch (ValidationDisclaimerException $e) {
			$reason = $e->getDisclaimer();
			$canBeActivated = false;
		} catch (Exception $exception) {
			api_error_raise($exception->getMessage());
			exit;
		}

		if (!$canBeActivated) {
			echo "{$campaignName} (id#{$campaignId})\n";
			echo "   > issue: {$reason}\n";
			if ($checkclassification) {
				echo "   > classification = {$classification}\n";
			}
			echo "   > https://morpheus.reachtel.com.au/admin_listcampaign.php?id={$campaignId}\n";
		}
	}
}

echo "\ndone\n";
