<?php

define("REQUEST_START", microtime(true));

date_default_timezone_set('Australia/Brisbane');
ini_set("auto_detect_line_endings", true);

if (file_exists(__DIR__ . "/header.php")) {
	require_once(__DIR__ . "/header.php");
}

$loader = require_once(__DIR__ . '/vendor/autoload.php');

// StatsD perf monitoring
require_once(__DIR__ . "/lib/StatsD/StatsD.php");

$statsd = new StatsD();

require_once(__DIR__ . "/lib/php-smpp/gsmencoder.class.php");

require_once(__DIR__ . "/api_audio.php");
require_once(__DIR__ . "/api_assets.php");
require_once(__DIR__ . "/api_campaigns.php");
require_once(__DIR__ . "/api_constants.php");
require_once(__DIR__ . "/api_country.php");
require_once(__DIR__ . "/api_cron.php");
require_once(__DIR__ . "/api_csv.php");
require_once(__DIR__ . "/api_conferences.php");
require_once(__DIR__ . "/api_data.php");
require_once(__DIR__ . "/api_db.php");
require_once(__DIR__ . "/api_dialplans.php");
require_once(__DIR__ . "/api_email.php");
require_once(__DIR__ . "/api_emailtemplates.php");
require_once(__DIR__ . "/api_error.php");
require_once(__DIR__ . "/api_groups.php");
require_once(__DIR__ . "/api_hosts.php");
require_once(__DIR__ . "/api_hlr_suppliers.php");
require_once(__DIR__ . "/api_invoicing.php");
require_once(__DIR__ . "/api_keystore.php");
require_once(__DIR__ . "/api_tags.php");
require_once(__DIR__ . "/api_lists.php");
require_once(__DIR__ . "/api_misc.php");
require_once(__DIR__ . "/api_payments.php");
require_once(__DIR__ . "/api_queue.php");
require_once(__DIR__ . "/api_restrictions.php");
require_once(__DIR__ . "/api_sms.php");
require_once(__DIR__ . "/api_sms_suppliers.php");
require_once(__DIR__ . "/api_security.php");
require_once(__DIR__ . "/api_session.php");
require_once(__DIR__ . "/api_survey.php");
require_once(__DIR__ . "/api_system.php");
require_once(__DIR__ . "/api_targets.php");
require_once(__DIR__ . "/api_templates.php");
require_once(__DIR__ . "/api_users.php");
require_once(__DIR__ . "/api_voice.php");
require_once(__DIR__ . "/api_voice_servers.php");
require_once(__DIR__ . "/api_sms_suppliers.php");
require_once(__DIR__ . "/api_wash.php");
require_once(__DIR__ . "/api_xero.php");
require_once(__DIR__ . "/api_billing.php");
require_once(__DIR__ . "/api_billing_transactions.php");
require_once(__DIR__ . "/api_reports.php");

if (defined('SETTINGS_ID')) {
	api_system_setting_load(SETTINGS_ID);
}

// If the release version isn't set, create a dynamic version based on the currentyear and week
if(!defined('RELEASE_VERSION')) {
    define('RELEASE_VERSION', date("YW"));
}

\Services\ActivityLogger::getInstance()->toggleLoggerActivation(!(defined('DISABLE_ACTIVITY_LOGGING') && DISABLE_ACTIVITY_LOGGING));
api_misc_loadtime_start();
