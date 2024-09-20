<?php
/**
 * Constants used across Morpheus
 *
 * @author Christopher.Colborne@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

const SESSION_KEY_USERID = 'userid';

// User statuses
const USER_STATUS_CLOSED = '-5';
const USER_STATUS_DISABLED = '-4';
const USER_STATUS_INACTIVE = '-3';
const USER_STATUS_LOCKED = '-2';
const USER_STATUS_INITIAL = '-1';
const USER_STATUS_INITIAL_LEGACY = 'disabled';
const USER_STATUS_DISABLED_LEGACY = '0';
const USER_STATUS_ACTIVE = '1';

// User settings
const USER_SETTING_SESSION_ID = 'sessionid';
const USER_SETTING_RESTPOSTBACK_URL = 'restpostback.smsdr';
const USER_SETTING_RESTPOSTBACK_USERNAME = 'restpostback.smsdr.user';
const USER_SETTING_RESTPOSTBACK_PASSWORD = 'restpostback.smsdr.password';
const USER_SETTINGS_API_RATE_PLAN = 'apirateplan';
const USER_SETTING_GROUP_OWNER = 'groupowner';
const USER_SETTING_USERNAME = 'username';
const USER_SETTING_EMAIL = 'emailaddress';

// User tags
const USER_TAGS_RESTPOSTBACK_NO_PAYLOAD = 'restpostback-no-payload';

// User login config
const USER_LOGIN_PASSWORD_EXPIRATION = 90; // days

// User Groups
const ADMIN_GROUP_OWNER_ID = 2;

// Security Zones
const ZONE_USER_GROUPS_LISTALL = 35;
const ZONE_CAMPAIGNS_LISTALL = 87;
const ZONE_PLOTTER_ACCESS = 178;
const ZONE_DIALPLANGEN_ACCESS = 179;
const ZONE_MORPHEUS_ACCESS = 182;
const ZONE_NATIONAL_SURVEY_WEIGHT_GENERATE = 183;
// @TODO Set Security Zone Id
const ZONE_PRODUCTS_LISTALL = '????';

// Campaign data
const CAMPAIGN_TARGET_WARNING_THRESHOLD = 200000;

// Campaign type names
const CAMPAIGN_TYPE_VOICE = 'phone';
const CAMPAIGN_TYPE_SMS = 'sms';
const CAMPAIGN_TYPE_EMAIL = 'email';
const CAMPAIGN_TYPE_WASH = 'wash';

// Campaign settings names
const CAMPAIGN_SETTING_DUPLICATE_CHECK = 'duplicatecheck';
const CAMPAIGN_SETTING_DUPLICATED_FROM = 'duplicatedfrom';
const CAMPAIGN_SETTING_PROOF_SENT = 'proofsent';
const CAMPAIGN_SETTING_PROOF_APPROVED = 'proofapproved';
const CAMPAIGN_SETTING_GROUP_OWNER = 'groupowner';
const CAMPAIGN_SETTING_LAST_SEND = 'lastsend';
const CAMPAIGN_SETTING_UNSUB_WASHED = 'unsubwashed';
const CAMPAIGN_SETTING_FINISH_TIME = 'finishtime';
const CAMPAIGN_SETTING_CREATED_TIME = 'created';
const CAMPAIGN_SETTING_STATUS = 'status';
const CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE = 'ACTIVE';
const CAMPAIGN_SETTING_STATUS_VALUE_DISABLED = 'DISABLED';
const CAMPAIGN_SETTING_CLASSIFICATION = 'classification';
const CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING = 'telemarketing';
const CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH = 'research';
const CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT = 'exempt';
const CAMPAIGN_SETTING_REGION = 'region';
const CAMPAIGN_SETTING_REGION_AUSTRALIA = 'AU';
const CAMPAIGN_SETTING_OWNER = 'owner';
const CAMPAIGN_SETTING_BOOST_SPOOLER = 'boostspooler';
const CAMPAIGN_SETTING_CONTENT = 'content';
const CAMPAIGN_SETTING_NAME = 'name';
const CAMPAIGN_SETTING_PLOTTER_IMPORT = 'plotterimport';
const CAMPAIGN_SETTING_SEND_RATE = 'sendrate';
const CAMPAIGN_SETTING_WITHHOLD_CALLER_ID = 'withholdcid';
const CAMPAIGN_SETTING_TIMING = 'timing';
const CAMPAIGN_SETTING_DKIM = "dkim";
const CAMPAIGN_SETTING_TYPE = 'type';
const CAMPAIGN_SETTING_DISABLE_DOWNLOAD = 'disabledownload';

// Cascading Campaign settings
const CAMPAIGN_SETTING_TIMEZONE = 'timezone';
const CAMPAIGN_SETTING_CASCADING_CAMPAIGN = "cascadingcampaign";
const CAMPAIGN_SETTING_CASCADING_TEMPLATE_ID = 'cascadingcampaigntemplateid'; // The template which this campaign is based off
const CAMPAIGN_SETTING_CASCADING_BASE_TEMPLATE = 'cascadingcampaignbasetemplate'; // Is the template the first in the series
const CAMPAIGN_SETTING_CASCADING_NEXT_TEMPLATE = 'cascadingcampaignnexttemplate'; // The next template in the series
const CAMPAIGN_SETTING_CASCADING_ITERATION = 'cascadingcampaigniteration'; // The current iteration of the cascading campaign
const CAMPAIGN_SETTING_CASCADING_DELAY = "cascadingcampaigndelay";
const CAMPAIGN_SETTING_CASCADING_RATE_MODIFIER = "cascadingcampaignsendratemodifier";
const CAMPAIGN_SETTING_CASCADING_PREVIOUS_ITERATION_ID = "cascadingcampaignpreviousiterationid"; // The campaign id of the parent

// SMS Campaign region international
const CAMPAIGN_SMS_REGION_INTERNATIONAL = 'INTERNATIONAL_SMS';

// Cron Settings
const CRON_SETTING_LAST_RUN = 'lastrun';
const CRON_SETTING_TIMEZONE = 'timezone';

const CRON_ID_ENV_KEY = 'CRON_ID';

// Key store types
const KEYSTORE_TYPE_CAMPAIGNS = 'CAMPAIGNS';
const KEYSTORE_TYPE_VOICESUPPLIER = 'VOICESUPPLIER';

// Rate Plans
const RATE_PLAN_ID_COST_PRICE = 7;

// User group
const USER_GROUP_SETTING_NOTIFICATION_SFTP_EMAIL = 'sftpemailnotificationto';

// SMS DID
const SMS_DID_SETTING_USE_ON_SHORE_PROVIDER = 'useonshoreprovider';
const SMS_DID_SETTING_NAME = 'name';
const SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_NOT_REQUIRED = 'Not Required';
const SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_PREFERRED = 'Preferred';
const SMS_DID_SETTING_USE_ON_SHORE_PROVIDER_VALUE_REQUIRED = 'Required';

// SMS supplier
const SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE = 'trafficonshore'; // This means it doesn't use providers off shore to send sms
const SMS_SUPPLIER_CAPABILITY_TRAFFIC_ON_SHORE_LABEL = 'On shore traffic only';
const SMS_SUPPLIER_SETTING_NAME = 'name';

//Voice Supplier
const VOICE_SUPPLIER_SETTING_PRIORITY = 'priority';
const VOICE_SUPPLIER_SETTING_LASTCALL = 'lastcall';
const VOICE_SUPPLIER_SETTING_STATUS = 'status';
const VOICE_SUPPLIER_SETTING_VOICESERVER = 'voiceserver';
const VOICE_SUPPLIER_SETTING_STATUS_ACTIVE = 'ACTIVE';

// Queue
const QUEUE_NAME_PLOTTER_KML_EXPORT = 'kml_export';
const QUEUE_NAME_PLOTTER_BULK_EXPORT = 'bulk_export';
const QUEUE_NOT_BEFORE_DELETE_GROUP_RECORDS = "2 months";

const VALUE_ON = 'on';

const KEY_STORE_TYPE_SYSTEM = 'SYSTEM';
const KEY_STORE_TYPE_SMS_DIDS = 'SMSDIDS';
const KEY_STORE_TYPE_VOICE_DIDS = 'DIDS';
const KEY_STORE_TYPE_GROUPS = 'GROUPS';
const KEY_STORE_TYPE_USERS = 'USERS';

// Log actions
const LOG_WARNING_INVALID_DATA = 'INVALID_DATA';
const LOG_WARNING_DATA_NOT_FOUND = 'DATA_NOT_FOUND';

//Billing
const BILLING_PHONE_INTERVAL_FIRST_KEY = 'first';
const BILLING_PHONE_INTERVAL_NEXT_KEY = 'next';
const BILLING_ADHOC_ITEMNAME_MAX_LENGTH = 80;

//billing runs
const BILLING_RUN_IN_PROGRESS = 0;
const BILLING_RUN_COMPLETE = 1;

//DKIM
const KEY_TYPE_PUBLIC = 'public';
const KEY_TYPE_PRIVATE = 'private';
