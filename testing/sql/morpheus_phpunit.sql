-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 24, 2018 at 04:35 PM
-- Server version: 5.6.37
-- PHP Version: 5.6.38

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+10:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `morpheus_phpunit`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('AUDIO','ASSET','CAMPAIGNS','DIALPLANS','CRON','DIDS','DONOTCONTACT','SMSDIDS','SURVEYWEIGHT','SYSTEM','VOICESERVERS','VOICESUPPLIER','EMAILTEMPLATES','RATEPLANS','USERS','SECURITYZONE','GROUPS','LISTS','SETTINGS','SMSSUPPLIER','HLRSUPPLIER','RATELIMITS') NOT NULL,
  `userid` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `item` varchar(100) DEFAULT NULL,
  `value` varchar(1024) NOT NULL,
  `objectid` int(10) UNSIGNED NOT NULL,
  `from` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bad_data`
--

DROP TABLE IF EXISTS `bad_data`;
CREATE TABLE `bad_data` (
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum('phone','email') NOT NULL DEFAULT 'phone',
  `destination` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `call_results`
--

DROP TABLE IF EXISTS `call_results`;
CREATE TABLE `call_results` (
  `resultid` int(9) UNSIGNED NOT NULL,
  `eventid` int(10) UNSIGNED NOT NULL,
  `campaignid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `targetid` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `value` enum('GENERATED','ANSWER','NOANSWER','CANCEL','DNC','HLRFAIL','HANGUP','SENT','DISCONNECTED','CONGESTION','BUSY','CHANUNAVAIL') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `call_results_archive`
--

DROP TABLE IF EXISTS `call_results_archive`;
CREATE TABLE `call_results_archive` (
  `resultid` int(10) UNSIGNED NOT NULL,
  `eventid` int(10) UNSIGNED NOT NULL,
  `campaignid` int(10) UNSIGNED NOT NULL,
  `targetid` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `value` enum('GENERATED','ANSWER','NOANSWER','CANCEL','DNC','HLRFAIL','HANGUP','SENT','DISCONNECTED','CONGESTION','BUSY','CHANUNAVAIL') NOT NULL,
  `archive_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `competition_entries`
--

DROP TABLE IF EXISTS `competition_entries`;
CREATE TABLE `competition_entries` (
  `entryid` mediumint(8) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `competition` varchar(100) NOT NULL,
  `number` varchar(20) NOT NULL,
  `entry` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conferences`
--

DROP TABLE IF EXISTS `conferences`;
CREATE TABLE `conferences` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `serverid` mediumint(8) UNSIGNED DEFAULT NULL,
  `accesscode` varchar(10) NOT NULL,
  `accesscodeexpiry` timestamp NULL DEFAULT NULL,
  `accesscoderedeemed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conferences_status`
--

DROP TABLE IF EXISTS `conferences_status`;
CREATE TABLE `conferences_status` (
  `participantid` int(10) UNSIGNED NOT NULL,
  `conferenceid` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left` timestamp NULL DEFAULT NULL,
  `status` enum('CONNECTED','DISCONNECTED') NOT NULL DEFAULT 'DISCONNECTED',
  `channel` varchar(255) NOT NULL,
  `callerid` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `data_collector`
--

DROP TABLE IF EXISTS `data_collector`;
CREATE TABLE `data_collector` (
  `dataid` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` varchar(255) NOT NULL,
  `group` varchar(100) NOT NULL,
  `item` varchar(100) NOT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `do_not_contact_data`
--

DROP TABLE IF EXISTS `do_not_contact_data`;
CREATE TABLE `do_not_contact_data` (
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `listid` mediumint(9) NOT NULL,
  `type` enum('phone','email') NOT NULL,
  `destination` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `event_queue`
--

DROP TABLE IF EXISTS `event_queue`;
CREATE TABLE `event_queue` (
  `eventid` int(10) UNSIGNED NOT NULL,
  `queue` enum('sms','sms_out','cron','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync') NOT NULL,
  `locked` mediumint(8) UNSIGNED NOT NULL DEFAULT '0',
  `notbefore` timestamp NULL DEFAULT NULL,
  `errors` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `details` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `key_store`
--

DROP TABLE IF EXISTS `key_store`;
CREATE TABLE `key_store` (
  `type` enum('AUDIO','ASSET','CAMPAIGNS','DIALPLANS','CRON','DIDS','DONOTCONTACT','SMSDIDS','SURVEYWEIGHT','SYSTEM','VOICESERVERS','VOICESUPPLIER','EMAILTEMPLATES','RATEPLANS','USERS','SECURITYZONE','GROUPS','LISTS','SETTINGS','SMSSUPPLIER','HLRSUPPLIER','RATELIMITS') NOT NULL,
  `id` int(10) UNSIGNED NOT NULL,
  `item` varchar(100) NOT NULL,
  `value` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `key_store`
--

INSERT INTO `key_store` (`type`, `id`, `item`, `value`) VALUES
('AUDIO', 0, 'nextid', '1'),
('ASSET', 0, 'nextid', '1'),
('CAMPAIGNS', 0, 'nextid', '1'),
('DIALPLANS', 0, 'nextid', '1'),
('CRON', 0, 'lastrun', '0'),
('CRON', 0, 'nextid', '1'),
('DIDS', 0, 'nextid', '1'),
('DONOTCONTACT', 0, 'nextid', '1'),
('SMSDIDS', 0, 'nextid', '1'),
('SURVEYWEIGHT', 0, 'nextid', '1'),
('SYSTEM', 0, 'defaultcid', '0731038300'),
('SYSTEM', 0, 'lastgeo', '1424450365.06'),
('SYSTEM', 0, 'LASTPING', '1353297269'),
('SYSTEM', 0, 'maxchannels', '800'),
('SYSTEM', 0, 'ringtime', '30'),
('SYSTEM', 0, 'smsout', '28'),
('SYSTEM', 0, 'tags', 'a:7:{s:14:"pgp-systemkeys";s:22:"0xE063861F, 0xC9884E8E";s:19:"NSW-public-holidays";s:149:"1st January, 26th January, 3rd April, 4th April, 5th April, 6th April, 25th April, 8th June, 5th October, 25th December, 26th December, 28th December";s:19:"ACT-public-holidays";s:150:"1st January, 26th January, 9th March, 3rd April, 4th April, 6th April, 25th April, 8th June, 28th September, 5th October, 25th December, 28th December";s:18:"AU-public-holidays";s:89:"1st January, 26th January, 3rd April, 6th April, 25th April, 25th December, 28th December";s:19:"VIC-public-holidays";s:161:"1st January, 26th January, 9th March, 3rd April, 4th April, 5th April, 6th April, 25th April, 8th June, 3rd November, 25th December, 26th December, 28th December";s:19:"QLD-public-holidays";s:138:"1st January, 26th January, 3rd April, 4th April, 6th April, 25th April, 8th June, 5th October, 25th December, 26th December, 28th December";s:18:"NZ-public-holidays";s:127:"1st January, 2nd January, 6th February, 30th March, 2nd April, 25th April, 4th June, 22nd October, 25th December, 26th December";}'),
('SYSTEM', 0, 'uniqueid', '123484024'),
('SYSTEM', 0, 'washerrors', '49139'),
('SYSTEM', 0, 'washid', '0'),
('VOICESERVERS', 0, 'nextid', '1'),
('VOICESUPPLIER', 0, 'nextid', '1'),
('EMAILTEMPLATES', 0, 'nextid', '1'),
('RATEPLANS', 0, 'nextid', '4'),
('RATEPLANS', 0, 'smsaumobile', 'SMS - AU mobile'),
('RATEPLANS', 0, 'smsgbmobile', 'SMS - GB mobile'),
('RATEPLANS', 0, 'smsnzmobile', 'SMS - NZ mobile'),
('RATEPLANS', 0, 'smsothermobile', 'SMS - Other mobile'),
('RATEPLANS', 0, 'smsphmobile', 'SMS - PH mobile'),
('RATEPLANS', 0, 'smssgmobile', 'SMS - SG mobile'),
('RATEPLANS', 0, 'washaufixedline', 'Wash - AU fixed line'),
('RATEPLANS', 0, 'washaumobile', 'Wash - AU mobile'),
('RATEPLANS', 0, 'washauoneeighthundred', 'Wash - AU 1800'),
('RATEPLANS', 0, 'washauonethreehundred', 'Wash - AU 1300'),
('RATEPLANS', 0, 'washgbmobile', 'Wash - GB mobile'),
('RATEPLANS', 0, 'washnzfixedline', 'Wash - NZ fixed line'),
('RATEPLANS', 0, 'washnzmobile', 'Wash - NZ mobile'),
('RATEPLANS', 0, 'washnzzeroeight', 'Wash - NZ 0800'),
('RATEPLANS', 0, 'washother', 'Wash - Other'),
('RATEPLANS', 0, 'washsgfixedline', 'Wash - SG fixed line'),
('RATEPLANS', 0, 'washsgmobile', 'Wash - SG mobile'),
('RATEPLANS', 4, 'email', '0.1'),
('RATEPLANS', 4, 'emailcampaign', '0'),
('RATEPLANS', 4, 'firstaufixedline', '0.25'),
('RATEPLANS', 4, 'firstaumobile', '0.30'),
('RATEPLANS', 4, 'firstauoneeight', '0.00'),
('RATEPLANS', 4, 'firstauonethree', '0.30'),
('RATEPLANS', 4, 'firstinterval', '30'),
('RATEPLANS', 4, 'firstnzfixedline', '0.25'),
('RATEPLANS', 4, 'firstnzmobile', '0.30'),
('RATEPLANS', 4, 'firstnzzeroeight', '0.00'),
('RATEPLANS', 4, 'name', 'Test Rate Plan 1'),
('RATEPLANS', 4, 'nextaufixedline', '0.03'),
('RATEPLANS', 4, 'nextaumobile', '0.04'),
('RATEPLANS', 4, 'nextauoneeight', '0.00'),
('RATEPLANS', 4, 'nextauonethree', '0.00'),
('RATEPLANS', 4, 'nextinterval', '6'),
('RATEPLANS', 4, 'nextnzfixedline', '0.03'),
('RATEPLANS', 4, 'nextnzmobile', '0.04'),
('RATEPLANS', 4, 'nextnzzeroeight', '0.00'),
('RATEPLANS', 4, 'phonecampaign', '0'),
('RATEPLANS', 4, 'serial', 'a:38:{s:5:\"email\";s:3:\"0.1\";s:13:\"emailcampaign\";s:1:\"0\";s:16:\"firstaufixedline\";s:4:\"0.25\";s:13:\"firstaumobile\";s:4:\"0.30\";s:15:\"firstauoneeight\";s:4:\"0.00\";s:15:\"firstauonethree\";s:4:\"0.30\";s:13:\"firstinterval\";s:2:\"30\";s:16:\"firstnzfixedline\";s:4:\"0.25\";s:13:\"firstnzmobile\";s:4:\"0.30\";s:16:\"firstnzzeroeight\";s:4:\"0.00\";s:4:\"name\";s:16:\"Test Rate Plan 1\";s:15:\"nextaufixedline\";s:4:\"0.03\";s:12:\"nextaumobile\";s:4:\"0.04\";s:14:\"nextauoneeight\";s:4:\"0.00\";s:14:\"nextauonethree\";s:4:\"0.00\";s:12:\"nextinterval\";s:1:\"6\";s:15:\"nextnzfixedline\";s:4:\"0.03\";s:12:\"nextnzmobile\";s:4:\"0.04\";s:15:\"nextnzzeroeight\";s:4:\"0.00\";s:13:\"phonecampaign\";s:1:\"0\";s:11:\"smsaumobile\";s:4:\"0.15\";s:11:\"smscampaign\";s:1:\"0\";s:11:\"smsgbmobile\";s:4:\"0.25\";s:11:\"smsnzmobile\";s:4:\"0.15\";s:14:\"smsothermobile\";s:4:\"0.25\";s:11:\"smssgmobile\";s:4:\"0.25\";s:15:\"washaufixedline\";s:4:\"0.05\";s:12:\"washaumobile\";s:4:\"0.05\";s:21:\"washauoneeighthundred\";s:4:\"0.05\";s:21:\"washauonethreehundred\";s:4:\"0.05\";s:12:\"washcampaign\";s:1:\"0\";s:12:\"washgbmobile\";s:4:\"0.05\";s:15:\"washnzfixedline\";s:4:\"0.05\";s:12:\"washnzmobile\";s:4:\"0.05\";s:15:\"washnzzeroeight\";s:4:\"0.05\";s:9:\"washother\";s:4:\"0.05\";s:15:\"washsgfixedline\";s:4:\"0.05\";s:12:\"washsgmobile\";s:4:\"0.05\";}'),
('RATEPLANS', 4, 'smsaumobile', '0.15'),
('RATEPLANS', 4, 'smscampaign', '0'),
('RATEPLANS', 4, 'smsgbmobile', '0.25'),
('RATEPLANS', 4, 'smsnzmobile', '0.15'),
('RATEPLANS', 4, 'smsothermobile', '0.25'),
('RATEPLANS', 4, 'smssgmobile', '0.25'),
('RATEPLANS', 4, 'washaufixedline', '0.05'),
('RATEPLANS', 4, 'washaumobile', '0.05'),
('RATEPLANS', 4, 'washauoneeighthundred', '0.05'),
('RATEPLANS', 4, 'washauonethreehundred', '0.05'),
('RATEPLANS', 4, 'washcampaign', '0'),
('RATEPLANS', 4, 'washgbmobile', '0.05'),
('RATEPLANS', 4, 'washnzfixedline', '0.05'),
('RATEPLANS', 4, 'washnzmobile', '0.05'),
('RATEPLANS', 4, 'washnzzeroeight', '0.05'),
('RATEPLANS', 4, 'washother', '0.05'),
('RATEPLANS', 4, 'washsgfixedline', '0.05'),
('RATEPLANS', 4, 'washsgmobile', '0.05'),
('USERS', 0, 'nextid', '2'),
('USERS', 2, 'apirateplan', '4'),
('USERS', 2, 'apirequest.get.limit', '60'),
('USERS', 2, 'apirequest.post.limit', '60'),
('USERS', 2, 'autherrors', '0'),
('USERS', 2, 'created', '1444795394'),
('USERS', 2, 'description', 'Admin Test User'),
('USERS', 2, 'emailaddress', 'admin@morpheus.dev'),
('USERS', 2, 'firstname', 'Admin'),
('USERS', 2, 'groupowner', '2'),
('USERS', 2, 'ipaccesslist', ''),
('USERS', 2, 'smsapidid', ''),
('USERS', 2, 'jobpriority', 'normal'),
('USERS', 2, 'lastname', 'Test'),
('USERS', 2, 'passwordresetcount', '0'),
('USERS', 2, 'passwordresettime', UNIX_TIMESTAMP()),
('USERS', 2, 'region', 'AU'),
('USERS', 2, 'saltedpassword', '$2y$05$oUM4HXqrEDYoxr5mO4fQUet0sLTSiF4cJd590BIp4v1QYnW7wxcuK'),
('USERS', 2, 'securityzones', 'a:154:{i:0;s:3:\"140\";i:1;s:2:\"96\";i:2;s:3:\"125\";i:3;s:3:\"112\";i:4;s:2:\"95\";i:5;s:2:\"94\";i:6;s:3:\"177\";i:7;s:3:\"174\";i:8;s:3:\"176\";i:9;s:3:\"175\";i:10;s:3:\"172\";i:11;s:3:\"163\";i:12;s:3:\"170\";i:13;s:3:\"164\";i:14;s:3:\"165\";i:15;s:3:\"155\";i:16;s:3:\"152\";i:17;s:3:\"153\";i:18;s:3:\"154\";i:19;s:3:\"151\";i:20;s:3:\"150\";i:21;s:3:\"149\";i:22;s:3:\"127\";i:23;s:3:\"128\";i:24;s:3:\"123\";i:25;s:3:\"124\";i:26;s:3:\"119\";i:27;s:3:\"120\";i:28;s:3:\"137\";i:29;s:3:\"138\";i:30;s:3:\"167\";i:31;s:3:\"169\";i:32;s:3:\"171\";i:33;s:3:\"168\";i:34;s:3:\"166\";i:35;s:3:\"141\";i:36;s:3:\"121\";i:37;s:3:\"162\";i:38;s:3:\"122\";i:39;s:3:\"142\";i:40;s:3:\"117\";i:41;s:3:\"118\";i:42;s:3:\"116\";i:43;s:3:\"115\";i:44;s:2:\"93\";i:45;s:2:\"92\";i:46;s:3:\"161\";i:47;s:3:\"114\";i:48;s:2:\"97\";i:49;s:3:\"111\";i:50;s:3:\"113\";i:51;s:2:\"37\";i:52;s:2:\"38\";i:53;s:2:\"40\";i:54;s:2:\"39\";i:55;s:2:\"41\";i:56;s:2:\"42\";i:57;s:2:\"43\";i:58;s:2:\"44\";i:59;s:2:\"83\";i:60;s:2:\"87\";i:61;s:2:\"86\";i:62;s:2:\"84\";i:63;s:3:\"104\";i:64;s:2:\"82\";i:65;s:2:\"80\";i:66;s:2:\"81\";i:67;s:2:\"85\";i:68;s:3:\"156\";i:69;s:2:\"90\";i:70;s:3:\"139\";i:71;s:3:\"157\";i:72;s:3:\"158\";i:73;s:3:\"159\";i:74;s:3:\"160\";i:75;s:2:\"55\";i:76;s:2:\"56\";i:77;s:2:\"57\";i:78;s:2:\"58\";i:79;s:3:\"179\";i:80;s:2:\"78\";i:81;s:2:\"74\";i:82;s:2:\"73\";i:83;s:2:\"88\";i:84;s:2:\"75\";i:85;s:2:\"76\";i:86;s:2:\"51\";i:87;s:2:\"52\";i:88;s:2:\"53\";i:89;s:2:\"54\";i:90;s:3:\"133\";i:91;s:3:\"134\";i:92;s:3:\"135\";i:93;s:3:\"136\";i:94;s:2:\"99\";i:95;s:3:\"106\";i:96;s:3:\"107\";i:97;s:3:\"110\";i:98;s:3:\"105\";i:99;s:3:\"109\";i:100;s:3:\"108\";i:101;s:3:\"182\";i:102;s:3:\"183\";i:103;s:2:\"45\";i:104;s:3:\"178\";i:105;s:3:\"144\";i:106;s:3:\"145\";i:107;s:3:\"173\";i:108;s:3:\"146\";i:109;s:3:\"147\";i:110;s:3:\"143\";i:111;s:1:\"9\";i:112;s:2:\"11\";i:113;s:1:\"8\";i:114;s:2:\"10\";i:115;s:2:\"79\";i:116;s:2:\"48\";i:117;s:2:\"49\";i:118;s:2:\"50\";i:119;s:2:\"12\";i:120;s:2:\"13\";i:121;s:2:\"14\";i:122;s:2:\"15\";i:123;s:3:\"130\";i:124;s:3:\"131\";i:125;s:3:\"132\";i:126;s:3:\"129\";i:127;s:3:\"102\";i:128;s:3:\"101\";i:129;s:3:\"100\";i:130;s:3:\"103\";i:131;s:2:\"47\";i:132;s:2:\"46\";i:133;s:2:\"33\";i:134;s:2:\"34\";i:135;s:2:\"35\";i:136;s:2:\"36\";i:137;s:2:\"29\";i:138;s:2:\"30\";i:139;s:2:\"31\";i:140;s:2:\"32\";i:141;s:2:\"25\";i:142;s:2:\"26\";i:143;s:2:\"27\";i:144;s:2:\"28\";i:145;s:2:\"16\";i:146;s:2:\"17\";i:147;s:2:\"18\";i:148;s:2:\"59\";i:149;s:2:\"19\";i:150;s:2:\"20\";i:151;s:2:\"21\";i:152;s:2:\"22\";i:153;s:2:\"24\";}'),
('USERS', 2, 'smstokendestination', '0401000111'),
('USERS', 2, 'status', '1'),
('USERS', 2, 'timezone', 'Australia/Brisbane'),
('USERS', 2, 'usergroups', 'a:1:{i:0;s:1:\"2\";}'),
('USERS', 2, 'username', 'admin@morpheus.dev'),
('USERS', 2, 'usertype', 'admin'),
('USERS', 2, 'yubikeyprivate', ''),
('USERS', 2, 'yubikeypublic', ''),
('SECURITYZONE', 0, 'nextid', '182'),
('SECURITYZONE', 8, 'name', 'RATE PLANS - Show All'),
('SECURITYZONE', 9, 'name', 'RATE PLANS - Create New'),
('SECURITYZONE', 10, 'name', 'RATE PLANS - Update Existing'),
('SECURITYZONE', 11, 'name', 'RATE PLANS - Delete'),
('SECURITYZONE', 12, 'name', 'SMS DIDS - Create New'),
('SECURITYZONE', 13, 'name', 'SMS DIDS - Delete'),
('SECURITYZONE', 14, 'name', 'SMS DIDS - Show All'),
('SECURITYZONE', 15, 'name', 'SMS DIDS - Update Existing'),
('SECURITYZONE', 16, 'name', 'VOICE SERVERS - Create New'),
('SECURITYZONE', 17, 'name', 'VOICE SERVERS - Delete'),
('SECURITYZONE', 18, 'name', 'VOICE SERVERS - Show All'),
('SECURITYZONE', 19, 'name', 'VOICE SERVERS - Update Existing'),
('SECURITYZONE', 20, 'name', 'VOICE SUPPLIERS - Create New'),
('SECURITYZONE', 21, 'name', 'VOICE SUPPLIERS - Delete'),
('SECURITYZONE', 22, 'name', 'VOICE SUPPLIERS - Show All'),
('SECURITYZONE', 24, 'name', 'VOICE SUPPLIERS - Update Existing'),
('SECURITYZONE', 25, 'name', 'VOICE DIDS - Create New'),
('SECURITYZONE', 26, 'name', 'VOICE DIDS - Delete'),
('SECURITYZONE', 27, 'name', 'VOICE DIDS - Show All'),
('SECURITYZONE', 28, 'name', 'VOICE DIDS - Update Existing'),
('SECURITYZONE', 29, 'name', 'USERS - Create New'),
('SECURITYZONE', 30, 'name', 'USERS - Delete'),
('SECURITYZONE', 31, 'name', 'USERS - Show All'),
('SECURITYZONE', 32, 'name', 'USERS - Update Existing'),
('SECURITYZONE', 33, 'name', 'USER GROUPS - Create New'),
('SECURITYZONE', 34, 'name', 'USER GROUPS - Delete'),
('SECURITYZONE', 35, 'name', 'USER GROUPS - Show All'),
('SECURITYZONE', 36, 'name', 'USER GROUPS - Update Existing'),
('SECURITYZONE', 37, 'name', 'ASSETS - Create New'),
('SECURITYZONE', 38, 'name', 'ASSETS - Delete'),
('SECURITYZONE', 39, 'name', 'ASSETS - Show All'),
('SECURITYZONE', 40, 'name', 'ASSETS - Download'),
('SECURITYZONE', 41, 'name', 'AUDIO - Create New'),
('SECURITYZONE', 42, 'name', 'AUDIO - Delete'),
('SECURITYZONE', 43, 'name', 'AUDIO - Download'),
('SECURITYZONE', 44, 'name', 'AUDIO - Show All'),
('SECURITYZONE', 45, 'name', 'PBX STATUS - View'),
('SECURITYZONE', 46, 'name', 'TOOLS - View'),
('SECURITYZONE', 47, 'name', 'SYSTEM SETTINGS - View'),
('SECURITYZONE', 48, 'name', 'SECURITY ZONES - Create New'),
('SECURITYZONE', 49, 'name', 'SECURITY ZONES - Delete'),
('SECURITYZONE', 50, 'name', 'SECURITY ZONES - Show All'),
('SECURITYZONE', 51, 'name', 'EMAIL TEMPLATES - Create New'),
('SECURITYZONE', 52, 'name', 'EMAIL TEMPLATES - Delete'),
('SECURITYZONE', 53, 'name', 'EMAIL TEMPLATES - Show All'),
('SECURITYZONE', 54, 'name', 'EMAIL TEMPLATES - Update Existing'),
('SECURITYZONE', 55, 'name', 'DIAL PLANS - Create New'),
('SECURITYZONE', 56, 'name', 'DIAL PLANS - Delete'),
('SECURITYZONE', 57, 'name', 'DIAL PLANS - Show All'),
('SECURITYZONE', 58, 'name', 'DIAL PLANS - Update Existing'),
('SECURITYZONE', 59, 'name', 'VOICE SERVERS - Update Existing'),
('SECURITYZONE', 73, 'name', 'DO NOT CONTACT - Delete'),
('SECURITYZONE', 74, 'name', 'DO NOT CONTACT - Create New'),
('SECURITYZONE', 75, 'name', 'DO NOT CONTACT - Show All'),
('SECURITYZONE', 76, 'name', 'DO NOT CONTACT - Update Existing'),
('SECURITYZONE', 78, 'name', 'DO NOT CONTACT - Add Record'),
('SECURITYZONE', 79, 'name', 'SEARCH - Search'),
('SECURITYZONE', 80, 'name', 'CAMPAIGNS - Update Existing'),
('SECURITYZONE', 81, 'name', 'CAMPAIGNS - Upload Data'),
('SECURITYZONE', 82, 'name', 'CAMPAIGNS - Save Settings'),
('SECURITYZONE', 83, 'name', 'CAMPAIGNS - Add/Edit Time Periods'),
('SECURITYZONE', 84, 'name', 'CAMPAIGNS - List Time Periods'),
('SECURITYZONE', 85, 'name', 'CAMPAIGNS - Use Campaign Data Tools'),
('SECURITYZONE', 86, 'name', 'CAMPAIGNS - List Settings'),
('SECURITYZONE', 87, 'name', 'CAMPAIGNS - List All'),
('SECURITYZONE', 88, 'name', 'DO NOT CONTACT - Download'),
('SECURITYZONE', 90, 'name', 'COMPETITIONS - Ten Brisbane interface'),
('SECURITYZONE', 92, 'name', 'API - SMS - Send SMS'),
('SECURITYZONE', 93, 'name', 'API - SMS - Get SMS status'),
('SECURITYZONE', 94, 'name', 'API - Postback - Add postback'),
('SECURITYZONE', 95, 'name', 'API - Ping'),
('SECURITYZONE', 96, 'name', 'API - Competitions - Add entry'),
('SECURITYZONE', 97, 'name', 'API - Targets - Add target'),
('SECURITYZONE', 99, 'name', 'INVOICING - Generate Invoice'),
('SECURITYZONE', 100, 'name', 'SURVEY WEIGHT - Show All'),
('SECURITYZONE', 101, 'name', 'SURVEY WEIGHT - Delete'),
('SECURITYZONE', 102, 'name', 'SURVEY WEIGHT - Create New'),
('SECURITYZONE', 103, 'name', 'SURVEY WEIGHT - Update Existing'),
('SECURITYZONE', 104, 'name', 'CAMPAIGNS - Open specific campaign'),
('SECURITYZONE', 105, 'name', 'LISTS - Show all'),
('SECURITYZONE', 106, 'name', 'LISTS - Create New'),
('SECURITYZONE', 107, 'name', 'LISTS - Delete'),
('SECURITYZONE', 108, 'name', 'LISTS - Upload data'),
('SECURITYZONE', 109, 'name', 'LISTS - Update existing'),
('SECURITYZONE', 110, 'name', 'LISTS - Download list'),
('SECURITYZONE', 111, 'name', 'API - Targets - Get target status'),
('SECURITYZONE', 112, 'name', 'API - Generate system email'),
('SECURITYZONE', 113, 'name', 'API - Washing - Is Connected'),
('SECURITYZONE', 114, 'name', 'API - Speech Recognition'),
('SECURITYZONE', 115, 'name', 'API - SMS - Bulk Send'),
('SECURITYZONE', 116, 'name', 'API - SMS - Bulk SMS status'),
('SECURITYZONE', 117, 'name', 'API - REST - Wash - Get'),
('SECURITYZONE', 118, 'name', 'API - REST - Wash - Post'),
('SECURITYZONE', 119, 'name', 'API - REST - SMS - Get'),
('SECURITYZONE', 120, 'name', 'API - REST - SMS - Post'),
('SECURITYZONE', 121, 'name', 'API - REST - Voice - Get'),
('SECURITYZONE', 122, 'name', 'API - REST - Voice - Post'),
('SECURITYZONE', 123, 'name', 'API - REST - IMSI - Get'),
('SECURITYZONE', 124, 'name', 'API - REST - IMSI - Post'),
('SECURITYZONE', 125, 'name', 'API - Email to SMS'),
('SECURITYZONE', 127, 'name', 'API - REST - Email Validate - Get'),
('SECURITYZONE', 128, 'name', 'API - REST - Email Validate - Post'),
('SECURITYZONE', 129, 'name', 'SMS SUPPLIERS - Update Existing'),
('SECURITYZONE', 130, 'name', 'SMS SUPPLIERS - Create New'),
('SECURITYZONE', 131, 'name', 'SMS SUPPLIERS - Delete'),
('SECURITYZONE', 132, 'name', 'SMS SUPPLIERS - Show All'),
('SECURITYZONE', 133, 'name', 'HLR SUPPLIERS - Create New'),
('SECURITYZONE', 134, 'name', 'HLR SUPPLIERS - Delete'),
('SECURITYZONE', 135, 'name', 'HLR SUPPLIERS - Show All'),
('SECURITYZONE', 136, 'name', 'HLR SUPPLIERS - Update Existing'),
('SECURITYZONE', 137, 'name', 'API - REST - Target - Get'),
('SECURITYZONE', 138, 'name', 'API - REST - Target - Post'),
('SECURITYZONE', 139, 'name', 'COMPETITIONS - Word Cloud portal'),
('SECURITYZONE', 140, 'name', 'ACE - Login'),
('SECURITYZONE', 141, 'name', 'API - REST - Voice - Delete'),
('SECURITYZONE', 142, 'name', 'API - REST - Voice - Put'),
('SECURITYZONE', 143, 'name', 'PORTALS - Access wbchos.reachtel.com.au'),
('SECURITYZONE', 144, 'name', 'PORTALS - '),
('SECURITYZONE', 145, 'name', 'PORTALS - Access boqfraud.reachtel.com.au'),
('SECURITYZONE', 146, 'name', 'PORTALS - Access mss.reachtel.com.au'),
('SECURITYZONE', 147, 'name', 'PORTALS - Access smsvote.reachtel.com.au'),
('SECURITYZONE', 149, 'name', 'API - REST - DNC - Post'),
('SECURITYZONE', 150, 'name', 'API - REST - DNC - Get'),
('SECURITYZONE', 151, 'name', 'API - REST - DNC - Delete'),
('SECURITYZONE', 152, 'name', 'API - REST - Conferences - Get'),
('SECURITYZONE', 153, 'name', 'API - REST - Conferences - Post'),
('SECURITYZONE', 154, 'name', 'API - REST - Conferences - Put'),
('SECURITYZONE', 155, 'name', 'API - REST - Conferences - Delete'),
('SECURITYZONE', 156, 'name', 'COMPETITIONS - Competition portal'),
('SECURITYZONE', 157, 'name', 'CRON - Create New'),
('SECURITYZONE', 158, 'name', 'CRON - Delete'),
('SECURITYZONE', 159, 'name', 'CRON - Show All'),
('SECURITYZONE', 160, 'name', 'CRON - Update Existing'),
('SECURITYZONE', 161, 'name', 'API - SMTP API'),
('SECURITYZONE', 162, 'name', 'API - REST - Voice - Patch'),
('SECURITYZONE', 163, 'name', 'API - REST - Campaign - Get'),
('SECURITYZONE', 164, 'name', 'API - REST - Campaign - Post'),
('SECURITYZONE', 165, 'name', 'API - REST - Campaign - Put'),
('SECURITYZONE', 166, 'name', 'API - REST - User - Put'),
('SECURITYZONE', 167, 'name', 'API - REST - User - Act on others'),
('SECURITYZONE', 168, 'name', 'API - REST - User - Post'),
('SECURITYZONE', 169, 'name', 'API - REST - User - Delete'),
('SECURITYZONE', 170, 'name', 'API - REST - Campaign - Patch'),
('SECURITYZONE', 171, 'name', 'API - REST - User - Patch'),
('SECURITYZONE', 172, 'name', 'API - REST - Campaign - Delete'),
('SECURITYZONE', 173, 'name', 'PORTALS - Access monitor.reachtel.com.au'),
('SECURITYZONE', 174, 'name', 'API - REST - Audio - Get'),
('SECURITYZONE', 175, 'name', 'API - REST - Audio - Post'),
('SECURITYZONE', 176, 'name', 'API - REST - Audio - Patch'),
('SECURITYZONE', 177, 'name', 'API - REST - Audio - Delete'),
('SECURITYZONE', 178, 'name', 'PLOTTER - Login'),
('SECURITYZONE', 179, 'name', 'DIALPLANGEN - Login'),
('SECURITYZONE', 182, 'name', 'MORPHEUS - Login'),
('SECURITYZONE', 183, 'name', 'NATIONAL SURVEY WEIGHT - Generate'),
('GROUPS', 0, 'nextid', '2'),
('GROUPS', 2, 'abn', '12 345 678 900'),
('GROUPS', 2, 'autogenerateinvoice', ''),
('GROUPS', 2, 'created', '1444187396'),
('GROUPS', 2, 'customeraddress', 'Level 6\n303 Coronation Dr\nMilton QLD 4064'),
('GROUPS', 2, 'customername', 'Admin Group'),
('GROUPS', 2, 'groupowner', 'i:2;'),
('GROUPS', 2, 'invoiceemailcc', 'admin@morpheus.dev'),
('GROUPS', 2, 'invoiceemailto', 'admin@morpheus.dev'),
('GROUPS', 2, 'name', 'Admin Group'),
('GROUPS', 2, 'paymentdays', '30'),
('LISTS', 0, 'nextid', '1'),
('SETTINGS', 0, 'ABR_LOOKUP_GUID', 'fa1e0011-0011-0011-0011-001100110011'),
('SETTINGS', 0, 'API_RATE_LIMIT_PERMIN', '300'),
('SETTINGS', 0, 'ASSET_LOCATION', '/assets'),
('SETTINGS', 0, 'AUDIO_LOCATION', '/audio'),
('SETTINGS', 0, 'AUDIT_DONTLOG', '0'),
('SETTINGS', 0, 'BAD_DATA_BACKCHECK_DAYS_EMAIL', '60'),
('SETTINGS', 0, 'BAD_DATA_BACKCHECK_DAYS_PHONE', '20'),
('SETTINGS', 0, 'BASE_LOCATION', '/mnt/morpheus'),
('SETTINGS', 0, 'CALL_DEBUG', '0'),
('SETTINGS', 0, 'CRYPTO_IV', 'fAkEcRyPtOKeYabcJKP0NA=='),
('SETTINGS', 0, 'CRYPTO_KEY', 'FakeCrytoKey'),
('SETTINGS', 0, 'DAEMON_RESTART', '1439766383'),
('SETTINGS', 0, 'DB_DEBUG', '0'),
('SETTINGS', 0, 'DB_LOG', '0'),
('SETTINGS', 0, 'DB_LOG_MIN', '0'),
('SETTINGS', 0, 'DB_MYSQL_WRITE_HOST', 'localhost'),
('SETTINGS', 0, 'DB_MYSQL_WRITE_PASSWORD', 'morpheus_phpunit'),
('SETTINGS', 0, 'DB_MYSQL_WRITE_PORT', '3306'),
('SETTINGS', 0, 'DB_MYSQL_WRITE_USERNAME', 'morpheus_phpunit'),
('SETTINGS', 0, 'DB_SINGLE_CONNECTION', '1'),
('SETTINGS', 0, 'DB_USE', 'gui'),
('SETTINGS', 0, 'DEFAULT_REGION', 'AU'),
('SETTINGS', 0, 'DEFAULT_TIMEZONE', 'Australia/Brisbane'),
('SETTINGS', 0, 'DIALPLAN_LOCATION', '/dialplans'),
('SETTINGS', 0, 'EMAILAPI_HMAC_SECRET', 'FAKE_HMAC_SECRET'),
('SETTINGS', 0, 'EMAILBODY_LOCATION', '/emailbody'),
('SETTINGS', 0, 'EMAILTEMPLATE_LOCATION', '/emailtemplates'),
('SETTINGS', 0, 'EMAILVALIDATE_FROM', 'test@reachtel.com.au'),
('SETTINGS', 0, 'EMAIL_ABUSE', 'support@ReachTEL.com.au'),
('SETTINGS', 0, 'EMAIL_DEFAULT_FROM', 'ReachTEL Support <support@ReachTEL.com.au>'),
('SETTINGS', 0, 'EMAIL_FBL_PASSWORD', 'fakePassword'),
('SETTINGS', 0, 'EMAIL_FBL_USERNAME', 'test@reachtel.com.au'),
('SETTINGS', 0, 'EMAIL_IMAP_CONNECTION', '{fake.mail.reachtel.com.au:993/imap4rev1/ssl/norsh/notls}INBOX'),
('SETTINGS', 0, 'EMAIL_SENDER', 'webapp@broadcast.reachtel.com.au'),
('SETTINGS', 0, 'EMAIL_SENDER_DOMAIN', 'fake.broadcast.reachtel.com.au'),
('SETTINGS', 0, 'EMAIL_SMTP_SETTINGS', 'a:8:{s:7:\"kartman\";a:7:{s:4:\"host\";s:31:\"ssl://fake.mail.reachtel.com.au\";s:4:\"port\";i:465;s:4:\"auth\";b:1;s:8:\"username\";s:30:\"test@broadcast.reachtel.com.au\";s:8:\"password\";s:4:\"test\";s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:5:\"kenny\";a:5:{s:4:\"host\";s:26:\"fake.kenny.reachtel.com.au\";s:4:\"port\";i:25;s:4:\"auth\";b:0;s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:8:\"sendgrid\";a:7:{s:4:\"host\";s:22:\"fake.smtp.sendgrid.net\";s:4:\"port\";i:587;s:4:\"auth\";b:1;s:8:\"username\";s:20:\"test@reachtel.com.au\";s:8:\"password\";s:4:\"test\";s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:8:\"critsend\";a:7:{s:4:\"host\";s:22:\"fake.smtp.critsend.com\";s:4:\"port\";i:587;s:4:\"auth\";b:1;s:8:\"username\";s:20:\"test@reachtel.com.au\";s:8:\"password\";s:4:\"test\";s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:9:\"amazonaws\";a:7:{s:4:\"host\";s:45:\"ssl://fake.email-smtp.us-east-1.amazonaws.com\";s:4:\"port\";i:465;s:4:\"auth\";b:1;s:8:\"username\";s:4:\"test\";s:8:\"password\";s:4:\"test\";s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:12:\"reachtelsmtp\";a:5:{s:4:\"host\";s:25:\"fake.smtp.reachtel.com.au\";s:4:\"port\";i:25;s:4:\"auth\";b:0;s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:7:\"mailjet\";a:7:{s:4:\"host\";s:19:\"fake.in.mailjet.com\";s:4:\"port\";i:587;s:4:\"auth\";b:1;s:8:\"username\";s:4:\"test\";s:8:\"password\";s:4:\"test\";s:10:\"pipelining\";b:0;s:7:\"persist\";b:1;}s:8:\"mandrill\";a:7:{s:4:\"host\";s:25:\"fake.smtp.mandrillapp.com\";s:4:\"port\";i:587;s:4:\"auth\";b:1;s:8:\"username\";s:20:\"test@reachtel.com.au\";s:8:\"password\";s:4:\"test\";s:7:\"persist\";b:1;s:10:\"pipelining\";b:0;}}'),
('SETTINGS', 0, 'EMAIL_TIMEOUT', '60'),
('SETTINGS', 0, 'EMAIL_WEBAPP_PASSWORD', 'FakeEmailPassword'),
('SETTINGS', 0, 'EMAIL_WEBAPP_USERNAME', 'webapp@broadcast.reachtel.com.au'),
('SETTINGS', 0, 'EMATTERS_XMLAPI_URL_PAYMENT', 'https://fake.ematters.com.au/cmaonline.nsf/XML?OpenAgent'),
('SETTINGS', 0, 'EMATTERS_XMLAPI_URL_TOKENISE', 'https://fake.eMatters.com.au/billsmart.nsf/Add?OpenAgent'),
('SETTINGS', 0, 'ERROR_STACK', 'morpheus'),
('SETTINGS', 0, 'EVENTQUEUE_ERROR_BACKOFF', '2'),
('SETTINGS', 0, 'EVENTQUEUE_MAXERROR', '6'),
('SETTINGS', 0, 'EVENTQUEUE_MAXERROR_POSTBACK', '18'),
('SETTINGS', 0, 'EVENTQUEUE_SPOOLER_CHILDREN', '20'),
('SETTINGS', 0, 'EVENTQUEUE_SPOOLER_CHILDREN_MAXJOBS', '2000'),
('SETTINGS', 0, 'EZIDEBIT_URL_PAYMENT', 'https://api.ezidebit.com.au/v3-5/pci?singleWSDL'),
('SETTINGS', 0, 'EZIDEBIT_URL_TOKENISE', 'https://api.ezidebit.com.au/V3-5/public-rest/AddCustomer'),
('SETTINGS', 0, 'GUI_PAGINATE_LIMIT', '25'),
('SETTINGS', 0, 'HLR_TIMEOUT', '40'),
('SETTINGS', 0, 'HMAC_HASH_BASE', 'fakeHMAChashBASE'),
('SETTINGS', 0, 'IAX_LOCATION', '/iax'),
('SETTINGS', 0, 'INVOICES_LOCATION', '/invoices'),
('SETTINGS', 0, 'LINKSHORTEN_ALLOWEDCHARS', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
('SETTINGS', 0, 'LISTS_LOCATION', '/lists'),
('SETTINGS', 0, 'MEMCACHE_DATACACHE_TIME', '2'),
('SETTINGS', 0, 'MEMCACHE_DEBUG', '0'),
('SETTINGS', 0, 'MEMCACHE_DISABLE', '1'),
('SETTINGS', 0, 'MEMCACHE_LOCATION', 'localhost'),
('SETTINGS', 0, 'MEMCACHE_PORT', '11211'),
('SETTINGS', 0, 'METRICS_LIBRATO_AUTHENTICATION', 'test@reachtel.com.au:fakeMetricsLibratoPwd'),
('SETTINGS', 0, 'METRICS_LIBRATO_URL', 'https://fake-api.librato.com/v1/metrics'),
('SETTINGS', 0, 'MULTIMAP_KEY', 'MultiMapKey'),
('SETTINGS', 0, 'PASSWORD_ALGO', '1'),
('SETTINGS', 0, 'PASSWORD_COST', '5'),
('SETTINGS', 0, 'PAYMENTS_PAYMENTEXPRESS_DEFAULTCURRENCY', 'AUD'),
('SETTINGS', 0, 'PAYMENTS_PAYMENTEXPRESS_DEFAULTTRANSTYPE', ''),
('SETTINGS', 0, 'PAYMENTS_PAYMENTEXPRESS_PASSWORD', ''),
('SETTINGS', 0, 'PAYMENTS_PAYMENTEXPRESS_SUBMISSIONPORT', '443'),
('SETTINGS', 0, 'PAYMENTS_PAYMENTEXPRESS_SUBMISSIONURL', 'ssl://fake.paymentexpress.com'),
('SETTINGS', 0, 'PAYMENTS_PAYMENTEXPRESS_USERNAME', ''),
('SETTINGS', 0, 'PING_FREQUENCY', '120'),
('SETTINGS', 0, 'PROFILE', '0'),
('SETTINGS', 0, 'QUEUE_GEARMAN_DEFAULTQUEUE', 'morpheus-job'),
('SETTINGS', 0, 'QUEUE_GEARMAN_QUEUESERVERS', 'localhost'),
('SETTINGS', 0, 'QUEUE_GEARMAN_QUEUESERVER_WORKERS', '::1'),
('SETTINGS', 0, 'READ_LOCATION', '/mnt/morpheus'),
('SETTINGS', 0, 'REMOTEATTACHMENTS_LOCATION', '/emailattachments'),
('SETTINGS', 0, 'REST_TOKEN_ACCESS_VALIDITYPERIOD', '30'),
('SETTINGS', 0, 'REST_TOKEN_REFRESH_VALIDITYPERIOD', '720'),
('SETTINGS', 0, 'REST_TOKEN_VALIDITYPERIOD', '30'),
('SETTINGS', 0, 'SAVE_LOCATION', '/mnt/morpheus'),
('SETTINGS', 0, 'SECURITY_TOKEN_TEXT', 'Your security code is '),
('SETTINGS', 0, 'SESSION_NAME', 'REACHTELSID'),
('SETTINGS', 0, 'SESSION_PATH', '/'),
('SETTINGS', 0, 'SESSION_TIMEOUT', '3600'),
('SETTINGS', 0, 'SFTP_KEY_PASSPHRASE', 'fakesftppassphrase'),
('SETTINGS', 0, 'SIPQOS_ALERTPERCENT', '2'),
('SETTINGS', 0, 'SIP_LOCATION', '/sip'),
('SETTINGS', 0, 'SMPP_TIMEOUT', '45'),
('SETTINGS', 0, 'SMSSCRIPTS_LOCATION', '/smsscripts'),
('SETTINGS', 0, 'SMS_CONCAT_TIMEOUT', '10'),
('SETTINGS', 0, 'SMS_TIMEOUT', '30'),
('SETTINGS', 0, 'SPOOLER_DEBUG', '0'),
('SETTINGS', 0, 'SPOOLER_DELAY', '50000'),
('SETTINGS', 0, 'SPOOLER_DELAY_INCREMENT', '10000'),
('SETTINGS', 0, 'SPOOLER_DELAY_MAX', '500000'),
('SETTINGS', 0, 'SPOOLER_PROFILE', '0'),
('SETTINGS', 0, 'SPOOLER_PROFILE_TIME', '120'),
('SETTINGS', 0, 'STORAGE_PATH', '/var/lib/morpheus'),
('SETTINGS', 0, 'TE_IMAGE_LOCATION', '//static.reachtel.com.au/'),
('SETTINGS', 0, 'TE_SMARTY_COMPILE_DIR', '/tmp'),
('SETTINGS', 0, 'TE_TEMPLATE_DIRECTORY', '/usr/share/php/Morpheus/templates'),
('SETTINGS', 0, 'USER_LOGIN_PASSWORD_EXPIRATION', '90'),
('SETTINGS', 0, 'USER_LOGIN_MAX_ATTEMPTS', '6'),
('SETTINGS', 0, 'WASH_BACKCHECK_HOURS', '1'),
('SETTINGS', 0, 'WASH_RING_TIME', '8'),
('SETTINGS', 0, 'WASH_VOICE_DID', '175'),
('SETTINGS', 0, 'XERO_API_CONSUMERKEY', 'FAKEXEROAPICONSUMERKEY'),
('SETTINGS', 0, 'XERO_API_CONSUMERSECRET', 'FAKEXEROAPICONSUMERSECRET'),
('SETTINGS', 0, 'XERO_API_ENDPOINT', 'https://fake.xero.com/fake.api.xro/2.0/'),
('SETTINGS', 0, 'XERO_API_PRIVATEKEY', '-----BEGIN RSA PRIVATE KEY-----\nFAKE XERO PRIVATE KEY\n-----END RSA PRIVATE KEY-----'),
('SETTINGS', 0, 'XERO_API_PUBLICCERT', '-----BEGIN CERTIFICATE-----\nFAKE_XERO_API_PUBLIC_CERTTIFICATE\n-----END CERTIFICATE-----'),
('SETTINGS', 0, 'YUBICO_AESKEY', 'fake_yubico_aeskey'),
('SETTINGS', 0, 'YUBICO_MAXATTEMPTS', '3'),
('SETTINGS', 0, 'REACHTEL_HOST_TRACK', 'track.reachtel.com.au'),
('SETTINGS', 0, 'SFTP_REACHTEL_HOST_NAME', 'sftp.reachtel.com.au'),
('SETTINGS', 0, 'SFTP_GLOBALSCAPE_HOST_NAME', 'xfer.veda.com.au'),
('SETTINGS', 0, 'SFTP_REACHTEL_USERNAME', 'reachtelautomation'),
('SETTINGS', 0, 'SFTP_GLOBALSCAPE_USERNAME', 'reachtel'),
('SETTINGS', 0, 'SFTP_CACHE_LOCATION', 'sftp_cache'),
('SETTINGS', 0, 'PLOTTER_EXPORT_LOCATION', '/plotter_export'),
('SETTINGS', 0, 'CALL_SPOOLER_BOOST_MAX', 1),
('SMSSUPPLIER', 5, 'capabilities', 'a:1:{i:0;s:8:\"nzmobile\";}'),
('SMSSUPPLIER', 5, 'counter', '0'),
('SMSSUPPLIER', 5, 'name', 'Bulletin'),
('SMSSUPPLIER', 5, 'priority', '12'),
('SMSSUPPLIER', 5, 'smspersecond', '30'),
('SMSSUPPLIER', 5, 'status', 'ACTIVE'),
('SMSSUPPLIER', 5, 'tags', 'a:4:{s:8:\"username\";s:8:\"username\";s:8:\"password\";s:8:\"password\";s:4:\"host\";s:4:\"host\";s:13:\"fragmentation\";s:2:\"10\";}'),
('SMSSUPPLIER', 19, 'capabilities', 'a:5:{i:0;s:8:\"aumobile\";i:1;s:8:\"sgmobile\";i:2;s:8:\"gbmobile\";i:3;s:8:\"phmobile\";i:4;s:14:\"trafficonshore\";}'),
('SMSSUPPLIER', 19, 'counter', '0'),
('SMSSUPPLIER', 19, 'name', 'CLX-Telstra SMPP'),
('SMSSUPPLIER', 19, 'priority', '12'),
('SMSSUPPLIER', 19, 'smspersecond', '200'),
('SMSSUPPLIER', 19, 'status', 'ACTIVE'),
('SMSSUPPLIER', 17, 'capabilities', 'a:2:{i:0;s:8:\"aumobile\";i:1;s:11:\"othermobile\";}'),
('SMSSUPPLIER', 17, 'counter', '0'),
('SMSSUPPLIER', 17, 'name', 'CLX SMPP'),
('SMSSUPPLIER', 17, 'priority', '11'),
('SMSSUPPLIER', 17, 'smspersecond', '10'),
('SMSSUPPLIER', 17, 'status', 'ACTIVE'),
('SMSSUPPLIER', 20, 'capabilities', 'a:0:{}'),
('SMSSUPPLIER', 20, 'counter', '0'),
('SMSSUPPLIER', 20, 'name', 'Harbourtel SMPP Cloud'),
('SMSSUPPLIER', 20, 'priority', '5'),
('SMSSUPPLIER', 20, 'smspersecond', '2'),
('SMSSUPPLIER', 20, 'status', 'DISABLED'),
('SMSSUPPLIER', 0, 'nextid', '12'),
('HLRSUPPLIER', 0, 'nextid', '1');

-- --------------------------------------------------------

--
-- Table structure for table `linkshorten_urls`
--

DROP TABLE IF EXISTS `linkshorten_urls`;
CREATE TABLE `linkshorten_urls` (
  `id` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `url` varchar(1024) DEFAULT NULL,
  `targetid` int(50) UNSIGNED DEFAULT NULL,
  `referrals` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `merge_data`
--

DROP TABLE IF EXISTS `merge_data`;
CREATE TABLE `merge_data` (
  `campaignid` int(10) UNSIGNED NOT NULL,
  `targetkey` varchar(255) NOT NULL,
  `element` varchar(100) NOT NULL,
  `value` varchar(5000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `merge_data_archive`
--

DROP TABLE IF EXISTS `merge_data_archive`;
CREATE TABLE `merge_data_archive` (
  `campaignid` int(10) UNSIGNED NOT NULL,
  `targetkey` varchar(255) NOT NULL,
  `element` varchar(100) NOT NULL,
  `value` varchar(5000) DEFAULT NULL,
  `archive_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `number_washing_ranges`
--

DROP TABLE IF EXISTS `number_washing_ranges`;
CREATE TABLE `number_washing_ranges` (
  `to` varchar(15) DEFAULT NULL,
  `from` varchar(15) DEFAULT NULL,
  `carrier` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `gateway` enum('paymentexpress','securepay','ematters','ezidebit') NOT NULL DEFAULT 'paymentexpress',
  `process` enum('payment','tokenise') NOT NULL DEFAULT 'payment',
  `username` varchar(100) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responsecode` varchar(4) NOT NULL DEFAULT '0',
  `response` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `port_data`
--

DROP TABLE IF EXISTS `port_data`;
CREATE TABLE `port_data` (
  `destination` varchar(20) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `losingcarrier` char(4) NOT NULL,
  `gainingcarrier` char(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `provider_map`
--

DROP TABLE IF EXISTS `provider_map`;
CREATE TABLE `provider_map` (
  `targetid` int(10) UNSIGNED NOT NULL,
  `providerid` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `response_data`
--

DROP TABLE IF EXISTS `response_data`;
CREATE TABLE `response_data` (
  `resultid` int(9) UNSIGNED NOT NULL,
  `campaignid` int(8) UNSIGNED NOT NULL,
  `targetid` int(8) UNSIGNED NOT NULL,
  `eventid` int(10) UNSIGNED NOT NULL,
  `targetkey` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `action` varchar(255) NOT NULL,
  `value` varchar(1024) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `response_data_archive`
--

DROP TABLE IF EXISTS `response_data_archive`;
CREATE TABLE `response_data_archive` (
  `resultid` int(10) UNSIGNED NOT NULL,
  `campaignid` int(10) UNSIGNED NOT NULL,
  `targetid` int(10) UNSIGNED NOT NULL,
  `eventid` int(10) UNSIGNED NOT NULL,
  `targetkey` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `action` varchar(255) NOT NULL,
  `value` varchar(1024) DEFAULT NULL,
  `archive_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rest_tokens`
--

DROP TABLE IF EXISTS `rest_tokens`;
CREATE TABLE `rest_tokens` (
  `access_token` char(32) NOT NULL,
  `refresh_token` char(32) NOT NULL,
  `userid` mediumint(9) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_api_mapping`
--

DROP TABLE IF EXISTS `sms_api_mapping`;
CREATE TABLE `sms_api_mapping` (
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `messageunits` tinyint(4) UNSIGNED DEFAULT NULL,
  `billingtype` enum('smsaumobile','smsnzmobile','smssgmobile','smsgbmobile','smsphmobile','smsothermobile') NOT NULL,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `rid` int(10) UNSIGNED NOT NULL,
  `uid` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_concat`
--

DROP TABLE IF EXISTS `sms_concat`;
CREATE TABLE `sms_concat` (
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `source` varchar(11) NOT NULL,
  `destination` varchar(11) NOT NULL,
  `identifier` tinyint(3) UNSIGNED NOT NULL,
  `part` tinyint(3) UNSIGNED NOT NULL,
  `parts` tinyint(3) UNSIGNED NOT NULL,
  `message` varchar(1024) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_lookups`
--

DROP TABLE IF EXISTS `sms_lookups`;
CREATE TABLE `sms_lookups` (
  `lookupid` int(10) UNSIGNED NOT NULL,
  `supplier` tinyint(3) UNSIGNED DEFAULT NULL,
  `supplierid` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `msisdn` varchar(20) NOT NULL,
  `results` varchar(8192) NOT NULL,
  `status` enum('CONNECTED','DISCONNECTED') DEFAULT NULL,
  `carrier` varchar(5) DEFAULT NULL,
  `hlrcode` smallint(6) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_out`
--

DROP TABLE IF EXISTS `sms_out`;
CREATE TABLE `sms_out` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `billingtype` enum('smsaumobile','smsnzmobile','smssgmobile','smsgbmobile','smsphmobile','smsothermobile') NOT NULL,
  `supplier` tinyint(4) UNSIGNED NOT NULL,
  `supplierid` varchar(100) NOT NULL,
  `from` varchar(20) NOT NULL,
  `destination` varchar(20) NOT NULL,
  `message` varchar(1024) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_out_status`
--

DROP TABLE IF EXISTS `sms_out_status`;
CREATE TABLE `sms_out_status` (
  `id` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('created','sent','submitted','delivered','accepted','expired','undelivered','rejected','unknown') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_raw_receipts`
--

DROP TABLE IF EXISTS `sms_raw_receipts`;
CREATE TABLE `sms_raw_receipts` (
  `id` int(10) UNSIGNED NOT NULL,
  `supplier` tinyint(4) UNSIGNED NOT NULL,
  `supplierid` varchar(100) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('CREATED','SENT','SUBMITTED','DELIVERED','ACCEPTED','EXPIRED','UNDELIVERED','REJECTED','UNKNOWN') NOT NULL,
  `code` varchar(6) DEFAULT '0',
  `supplierdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_received`
--

DROP TABLE IF EXISTS `sms_received`;
CREATE TABLE `sms_received` (
  `smsid` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `received` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sms_account` mediumint(8) UNSIGNED NOT NULL,
  `from` varchar(15) NOT NULL,
  `contents` varchar(1024) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_sent`
--

DROP TABLE IF EXISTS `sms_sent`;
CREATE TABLE `sms_sent` (
  `eventid` int(10) UNSIGNED NOT NULL,
  `supplier` tinyint(3) UNSIGNED DEFAULT NULL,
  `supplieruid` varchar(100) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sms_account` smallint(6) NOT NULL,
  `to` varchar(15) NOT NULL,
  `contents` varchar(1024) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sms_status`
--

DROP TABLE IF EXISTS `sms_status`;
CREATE TABLE `sms_status` (
  `eventid` int(10) UNSIGNED DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('SUBMITTED','DELIVERED','ACCEPTED','EXPIRED','UNDELIVERED','REJECTED','UNKNOWN') NOT NULL,
  `code` varchar(6) DEFAULT '0',
  `supplierdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `smtp_events`
--

DROP TABLE IF EXISTS `smtp_events`;
CREATE TABLE `smtp_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `guid` varchar(36) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userid` int(10) UNSIGNED NOT NULL,
  `event` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `targets`
--

DROP TABLE IF EXISTS `targets`;
CREATE TABLE `targets` (
  `targetid` int(10) UNSIGNED NOT NULL,
  `campaignid` int(10) UNSIGNED NOT NULL,
  `targetkey` varchar(255) NOT NULL,
  `priority` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `status` enum('READY','INPROGRESS','REATTEMPT','ABANDONED','COMPLETE') NOT NULL DEFAULT 'READY',
  `destination` varchar(255) NOT NULL,
  `nextattempt` timestamp NULL DEFAULT NULL,
  `reattempts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ringouts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `errors` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `targets_archive`
--

DROP TABLE IF EXISTS `targets_archive`;
CREATE TABLE `targets_archive` (
  `targetid` int(10) UNSIGNED NOT NULL,
  `campaignid` int(10) UNSIGNED NOT NULL,
  `targetkey` varchar(255) NOT NULL,
  `priority` tinyint(3) UNSIGNED NOT NULL,
  `status` enum('READY','INPROGRESS','REATTEMPT','ABANDONED','COMPLETE') NOT NULL,
  `destination` varchar(255) NOT NULL,
  `nextattempt` timestamp NULL DEFAULT NULL,
  `reattempts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `ringouts` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `errors` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `archive_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `targets_out`
--

DROP TABLE IF EXISTS `targets_out`;
CREATE TABLE `targets_out` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `targetid` int(10) UNSIGNED NOT NULL,
  `destination` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `voice_out`
--

DROP TABLE IF EXISTS `voice_out`;
CREATE TABLE `voice_out` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `targetid` int(10) UNSIGNED NOT NULL,
  `destination` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wash_out`
--

DROP TABLE IF EXISTS `wash_out`;
CREATE TABLE `wash_out` (
  `id` int(10) UNSIGNED NOT NULL,
  `userid` mediumint(8) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `destination` varchar(20) NOT NULL,
  `billingtype` enum('washaufixedline','washaumobile','washnzfixedline','washnzmobile','washother','washsgfixedline','washsgmobile','washgbmobile','washgbfixedline') NOT NULL,
  `status` enum('CONNECTED','DISCONNECTED','INDETERMINATE','QUEUED') NOT NULL,
  `reason` enum('QUEUED','CACHED_RESULT','HLR_CONNECTED','HLR_DISCONNECTED','INVALID_LENGTH','INVALID_RANGE','PING_CONNECTED','PING_DISCONNECTED','PING_FAILED','PREVIOUS_ANSWER','PREVIOUS_DISCONNECTED','PREVIOUS_SMS','HLR_LOOKUPFAIL','FAILURE','UNSUPPORTED_PREFIX','UNSUPPORTED_NETWORK') NOT NULL,
  `returncarrier` tinyint(1) NOT NULL DEFAULT '0',
  `carriercode` char(5) DEFAULT NULL,
  `errors` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `sftp_cache`
--

DROP TABLE IF EXISTS `sftp_cache`;
CREATE TABLE `sftp_cache` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hostname` varchar(50) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `remotefile` varchar(200) NOT NULL,
  `filename` varchar(50) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid_action` (`userid`,`action`),
  ADD KEY `action` (`action`),
  ADD KEY `type_objectid_action_userid` (`type`,`objectid`,`action`,`userid`),
  ADD KEY `timestamp_userid` (`timestamp`,`userid`),
  ADD KEY `userid_type_item` (`userid`,`type`,`item`);

--
-- Indexes for table `bad_data`
--
ALTER TABLE `bad_data`
  ADD PRIMARY KEY (`type`,`destination`);

--
-- Indexes for table `call_results`
--
ALTER TABLE `call_results`
  ADD PRIMARY KEY (`resultid`),
  ADD KEY `targetid` (`targetid`),
  ADD KEY `campaignid` (`campaignid`),
  ADD KEY `eventid` (`eventid`);

--
-- Indexes for table `call_results_archive`
--
ALTER TABLE `call_results_archive`
  ADD KEY `resultid` (`resultid`),
  ADD KEY `eventid` (`eventid`),
  ADD KEY `campaignid` (`campaignid`),
  ADD KEY `targetid` (`targetid`),
  ADD KEY `archive_timestamp` (`archive_timestamp`);

--
-- Indexes for table `competition_entries`
--
ALTER TABLE `competition_entries`
  ADD PRIMARY KEY (`entryid`),
  ADD KEY `competition` (`competition`);

--
-- Indexes for table `conferences`
--
ALTER TABLE `conferences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `conferences_status`
--
ALTER TABLE `conferences_status`
  ADD PRIMARY KEY (`participantid`),
  ADD KEY `conferenceid` (`conferenceid`);

--
-- Indexes for table `data_collector`
--
ALTER TABLE `data_collector`
  ADD PRIMARY KEY (`dataid`),
  ADD KEY `group` (`group`);

--
-- Indexes for table `do_not_contact_data`
--
ALTER TABLE `do_not_contact_data`
  ADD PRIMARY KEY (`listid`,`type`,`destination`),
  ADD KEY `destination` (`destination`);

--
-- Indexes for table `event_queue`
--
ALTER TABLE `event_queue`
  ADD PRIMARY KEY (`eventid`),
  ADD KEY `locked` (`locked`,`notbefore`);

--
-- Indexes for table `key_store`
--
ALTER TABLE `key_store`
  ADD PRIMARY KEY (`type`,`id`,`item`),
  ADD KEY `type` (`type`,`item`,`value`(255));

--
-- Indexes for table `linkshorten_urls`
--
ALTER TABLE `linkshorten_urls`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `merge_data`
--
ALTER TABLE `merge_data`
  ADD PRIMARY KEY (`campaignid`,`targetkey`,`element`);

--
-- Indexes for table `merge_data_archive`
--
ALTER TABLE `merge_data_archive`
  ADD KEY `campaign_target_element` (`campaignid`,`targetkey`,`element`),
  ADD KEY `archive_timestamp` (`archive_timestamp`);

--
-- Indexes for table `number_washing_ranges`
--
ALTER TABLE `number_washing_ranges`
  ADD UNIQUE KEY `from` (`to`,`from`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `port_data`
--
ALTER TABLE `port_data`
  ADD UNIQUE KEY `destination` (`destination`,`timestamp`);

--
-- Indexes for table `provider_map`
--
ALTER TABLE `provider_map`
  ADD PRIMARY KEY (`providerid`,`targetid`);

--
-- Indexes for table `response_data`
--
ALTER TABLE `response_data`
  ADD PRIMARY KEY (`resultid`),
  ADD KEY `targetid` (`targetkey`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `targetid_2` (`targetid`),
  ADD KEY `campaignid` (`campaignid`,`action`);

--
-- Indexes for table `response_data_archive`
--
ALTER TABLE `response_data_archive`
  ADD KEY `resultid` (`resultid`),
  ADD KEY `targetid` (`targetid`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `targetkey` (`targetkey`),
  ADD KEY `campaignid` (`campaignid`,`action`),
  ADD KEY `archive_timestamp` (`archive_timestamp`);

--
-- Indexes for table `rest_tokens`
--
ALTER TABLE `rest_tokens`
  ADD UNIQUE KEY `access_token` (`access_token`),
  ADD UNIQUE KEY `refresh_token` (`refresh_token`);

--
-- Indexes for table `sms_api_mapping`
--
ALTER TABLE `sms_api_mapping`
  ADD KEY `userid` (`userid`,`rid`),
  ADD KEY `userid-uid` (`userid`,`uid`),
  ADD KEY `rid` (`rid`);

--
-- Indexes for table `sms_concat`
--
ALTER TABLE `sms_concat`
  ADD UNIQUE KEY `source` (`source`,`destination`,`identifier`,`part`,`parts`);

--
-- Indexes for table `sms_lookups`
--
ALTER TABLE `sms_lookups`
  ADD PRIMARY KEY (`lookupid`),
  ADD KEY `msisdn` (`msisdn`),
  ADD KEY `supplier-supplierid` (`supplier`,`supplierid`);

--
-- Indexes for table `sms_out`
--
ALTER TABLE `sms_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`),
  ADD KEY `supplierid` (`supplierid`,`supplier`),
  ADD KEY `destination` (`destination`) COMMENT 'destination';

--
-- Indexes for table `sms_out_status`
--
ALTER TABLE `sms_out_status`
  ADD KEY `eventid` (`id`);

--
-- Indexes for table `sms_raw_receipts`
--
ALTER TABLE `sms_raw_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplierid` (`supplierid`),
  ADD KEY `supplier` (`supplier`);

--
-- Indexes for table `sms_received`
--
ALTER TABLE `sms_received`
  ADD PRIMARY KEY (`smsid`),
  ADD KEY `sms_account` (`sms_account`);

--
-- Indexes for table `sms_sent`
--
ALTER TABLE `sms_sent`
  ADD PRIMARY KEY (`eventid`),
  ADD KEY `to` (`to`),
  ADD KEY `supplier` (`supplier`,`supplieruid`);

--
-- Indexes for table `sms_status`
--
ALTER TABLE `sms_status`
  ADD KEY `eventid` (`eventid`);

--
-- Indexes for table `smtp_events`
--
ALTER TABLE `smtp_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `targets`
--
ALTER TABLE `targets`
  ADD PRIMARY KEY (`targetid`),
  ADD UNIQUE KEY `campaignid` (`campaignid`,`targetkey`,`priority`),
  ADD KEY `destination` (`destination`),
  ADD KEY `campaignid-status` (`campaignid`,`status`),
  ADD KEY `status` (`status`,`campaignid`,`nextattempt`,`priority`);

--
-- Indexes for table `targets_archive`
--
ALTER TABLE `targets_archive`
  ADD KEY `targetid` (`targetid`),
  ADD KEY `targetkey` (`targetkey`),
  ADD KEY `destination` (`destination`),
  ADD KEY `campaignid` (`campaignid`),
  ADD KEY `archive_timestamp` (`archive_timestamp`);

--
-- Indexes for table `targets_out`
--
ALTER TABLE `targets_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `voice_out`
--
ALTER TABLE `voice_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `wash_out`
--
ALTER TABLE `wash_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destination` (`destination`),
  ADD KEY `userid` (`userid`),
  ADD KEY `timestamp` (`timestamp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `call_results`
--
ALTER TABLE `call_results`
  MODIFY `resultid` int(9) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competition_entries`
--
ALTER TABLE `competition_entries`
  MODIFY `entryid` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conferences`
--
ALTER TABLE `conferences`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conferences_status`
--
ALTER TABLE `conferences_status`
  MODIFY `participantid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_collector`
--
ALTER TABLE `data_collector`
  MODIFY `dataid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_queue`
--
ALTER TABLE `event_queue`
  MODIFY `eventid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `linkshorten_urls`
--
ALTER TABLE `linkshorten_urls`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `response_data`
--
ALTER TABLE `response_data`
  MODIFY `resultid` int(9) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_lookups`
--
ALTER TABLE `sms_lookups`
  MODIFY `lookupid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_out`
--
ALTER TABLE `sms_out`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_raw_receipts`
--
ALTER TABLE `sms_raw_receipts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_received`
--
ALTER TABLE `sms_received`
  MODIFY `smsid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smtp_events`
--
ALTER TABLE `smtp_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `targets`
--
ALTER TABLE `targets`
  MODIFY `targetid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `targets_out`
--
ALTER TABLE `targets_out`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voice_out`
--
ALTER TABLE `voice_out`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wash_out`
--
ALTER TABLE `wash_out`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- New enum for kml_export
--
ALTER TABLE `event_queue`
  MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync', 'kml_export', 'disable_all_users_from_group', 'delete_all_rest_tokens_from_group') NOT NULL;

--
-- New enum for bulk_export
--
ALTER TABLE `event_queue`
  MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync', 'kml_export', 'disable_all_users_from_group', 'delete_all_rest_tokens_from_group', 'bulk_export') NOT NULL;

--
-- New enum for delete_all_records_from_group
--
ALTER TABLE `event_queue`
  MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync', 'kml_export', 'disable_all_users_from_group', 'delete_all_rest_tokens_from_group', 'delete_all_records_from_group') NOT NULL;

ALTER TABLE `sms_api_mapping` ADD billing_products_region_id INT UNSIGNED DEFAULT 1;
ALTER TABLE `sms_api_mapping` ADD KEY `billing_products_region_id`(`billing_products_region_id`);

ALTER TABLE `sms_out` ADD billing_products_region_id INT UNSIGNED DEFAULT 1;
ALTER TABLE `sms_out` ADD KEY `billing_products_region_id`(`billing_products_region_id`);

ALTER TABLE `wash_out` ADD billing_products_region_id INT UNSIGNED DEFAULT 1, ADD billing_products_destination_type_id INT UNSIGNED DEFAULT 1;
ALTER TABLE `wash_out` ADD KEY `billing_products_region_id`(`billing_products_region_id`);
ALTER TABLE `wash_out` ADD KEY `billing_products_destination_type_id`(`billing_products_destination_type_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
