<?php

use Doctrine\ORM\EntityManager;
use Models\Entities\QueueItem;
use Models\Plotter\PlotterBulkDataExtractionParams;
use Models\Plotter\PlotterKmlDataExtractionParams;
use Services\Container\ContainerAccessor;
use Services\DataRetention\RESTTokenPolicy;
use Services\DataRetention\UserGroupRecordsPolicy;
use Services\Email\Client\EmailClientFactory;
use Services\Email\Client\Interfaces\SMTPClientInterface;
use Services\Email\Dkim\DkimKeyFactory;
use Services\Email\Dkim\DkimKeyResolver;
use Services\Email\Dkim\DkimKeystoreFactory;
use Services\File\QueuedFile;
use Services\PCI\PCIRecorder;
use Services\Plotter\PlotterBulkExtractionStrategy;
use Services\Plotter\PlotterDataExtractionContext;
use Services\Plotter\PlotterKmlExtractionStrategy;
use Services\Queue\QueueManager;
use Services\Queue\QueueProcessStatusEnum;
use Services\Queue\QueueProcessTypeEnum;
use Services\Utils\Plotter\PlotterFunctions;
use Services\Webhooks\WebhookFactory;
use Services\Webhooks\WebhookProcessor;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Services\Suppliers\Interfaces\PhoneNumberValidationServiceInterface;

$validqueues = ["sms", "sms_out", "cron", "email", "report", "postback", "restpostback", "addtarget", "wash",
                     "wash_out", "pbxcomms", "smsdr", "filesync", "disable_all_users_from_group",
                     "delete_all_rest_tokens_from_group", "delete_all_records_from_group", WebhookProcessor::QUEUE_NAME, "fileupload", "wash_out_result"];

function api_queue_add($queue, $details, $notbefore = null, $errors = 0, $options = array()){

	if(!in_array($queue, api_queue_get_valid_queue_items())) return api_error_raise("Sorry, that is not a valid queue type");

	if(($queue == "email") AND isset($details["body"]) && (strlen($details["body"]) > 102400)) {

		$uniqueid = "email-" . api_misc_randombytes();

		file_put_contents(SAVE_LOCATION . EMAILBODY_LOCATION . "/" . $uniqueid, $details["body"]);

		$details["bodyfile"] = $uniqueid;
		unset($details["body"]);

	}

	// Temporary check on dev until call to gearman on appdev is fixed
	if($notbefore == null && !api_misc_is_test_environment()) {
		require_once(__DIR__ . "/api_queue_gearman.php");
		$result = api_queue_gearman_add($queue, $details, null, $errors, $options);

		if($result) return $result;
		elseif(isset($options["block"]) AND $options["block"]) return false; // If the job failed on the line above and the job is set to block, just return an error.
	}

	$details = serialize($details);

	$sql = "INSERT INTO `event_queue` (`queue`, `locked`, `details`, `notbefore`, `errors`) VALUES (?, ?, ?, ?, ?)";
	$rs = api_db_query_write($sql, array($queue, 0, $details, $notbefore, $errors));

	if($rs !== FALSE) return api_db_lastid();
	else return false;

}

function api_queue_getjobcount(){

	$sql = "SELECT COUNT(`eventid`) AS `count` FROM `event_queue` WHERE `locked` = ? AND ((`notbefore` IS NULL) OR (`notbefore` <= NOW()))";
	$rs = api_db_query_read($sql, array(0));

	return $rs->Fields("count");

}

function api_queue_getjob(){

	global $statsd;

	$pid = getmypid();

	if($pid == FALSE) return false;

	$sql = "UPDATE `event_queue` SET `locked` = ? WHERE `locked` = ? AND ((`notbefore` IS NULL) OR (`notbefore` <= NOW())) LIMIT 1";
	$rs = api_db_query_write($sql, array($pid, 0));

	if($rs){
		$sql = "SELECT *
				FROM `event_queue`
				WHERE `locked` = ?
				ORDER BY `eventid` DESC
				LIMIT 1;";
		$rs2 = api_db_query_read($sql, [$pid]);

		if($rs2->RecordCount() > 0) {
			$row = $rs2->GetRowAssoc();
			$eventid = $row["eventid"];
			$details = @unserialize($row["details"]);
			if (false === $details) {
				$statsd->increment("morpheus.queue.errors.invalid." . $row["queue"]);

				api_queue_error_handler($eventid, $row['queue'], true);

				return api_error_raise(
					sprintf(
						"Gearman job '%s' eventid=%d has been deleted because it contains invalid serialized details: '%s'",
						$row["queue"],
						$eventid,
						$row["details"]
					)
				);
			}

			$job = [
				"eventid" => $eventid,
				"details" => $details,
				"queue" => $row["queue"],
				"errors" => $row["errors"],
			];
		}

	}

	if(isset($job) AND is_array($job)){

		$function = "api_queue_process_" . $job["queue"];

		if(!is_callable($function)) return api_error_raise("Sorry, " . $job["queue"] . " is not a valid queue name");
		//$function($job['details']) ; // Uncomment this to run the job locally
		$result = api_queue_add($job["queue"], $job["details"], null, $job["errors"]);

		if($result) {

			$sql = "DELETE FROM `event_queue` WHERE `eventid` = ?";
			$rs2 = api_db_query_write($sql, array($job["eventid"]));

		} else {
			api_queue_error_handler($job['eventid'], $job['queue'], $job['errors']);
		}

		return true;

	} else return false;

}

function api_queue_process_sms($details, array $workload = []) {

	if(empty($details["region"])) $details["region"] = "AU";

	$destination = api_data_numberformat($details["destination"], $details["region"]);

	if ($destination && CAMPAIGN_SMS_REGION_INTERNATIONAL === $details['region']) {
		api_error_audit(
			CAMPAIGN_SMS_REGION_INTERNATIONAL,
			"Processing job for {$destination['destination']} (formatted from {$details["destination"]}), eventid={$details["eventid"]}, did={$details["didid"]}"
		);
	}

	$sms = api_sms_send($details["didid"], $destination, $details["message"], $details["eventid"]);

	if($sms === FALSE) {
		if ($workload && !api_queue_should_the_job_get_reattempted($workload)) {
			if(!empty($details["campaignid"]) AND ($details["campaignid"] != "eventid") AND !empty($details["targetid"])) {
				api_targets_updatestatus($details["targetid"], "ABANDONED");
				api_data_responses_add($details["campaignid"], $details["eventid"], $details["targetid"], $details["targetkey"], "reason", 'CARRIER ERROR');
			}
		}
		return false;
	}
	elseif(!empty($details["campaignid"]) AND ($details["campaignid"] != "eventid") AND !empty($details["targetid"])) {

		api_data_responses_add($details["campaignid"], $details["eventid"], $details["targetid"], $details["targetkey"], "SENT", date("Y-m-d H:i:s"));
		api_data_callresult_add($details["campaignid"], $details["eventid"], $details["targetid"], "SENT");

	}

	return true;

}

function api_queue_process_sms_out($details) {

	if(empty($details["options"]) OR !is_array($details["options"])) $details["options"] = array();

	$sms = api_sms_out($details["from"], $details["destination"], $details["message"], $details["eventid"], $details["userid"], $details["options"]);

	if($sms === FALSE) return false;
	else return true;

}

function api_queue_process_email($details){

    global $statsd;

    if(!defined("EMAIL_HOST")) define("EMAIL_HOST", "mail.equifax.com");
    if(!defined("EMAIL_PORT")) define("EMAIL_PORT", "25");
    if(!defined("EMAIL_PIPELINING")) define("EMAIL_PIPELINING", false);
    if(!defined("EMAIL_PERSIST")) define("EMAIL_PERSIST", true);
    if(!defined("EMAIL_TIMEOUT")) define("EMAIL_TIMEOUT", 60);

    if(!isset($details['smtp_connection'])) {
        $connectionString = [
            'host' => EMAIL_HOST,
            'port' => EMAIL_PORT,
            'pipelining' => EMAIL_PIPELINING,
            'persist' => EMAIL_PERSIST,
            'localhost' => HOSTNAME,
            'timeout' => EMAIL_TIMEOUT,
            'socket_options' => array('ssl' => array('verify_peer_name' => false)),
        ];

        $smtp = Mail::factory('smtp', $connectionString);
    } else {
    	$smtp = $details['smtp_connection'];
    }

    if(PEAR::isError($smtp)) {
        return api_error_raise("Failed to connect - " . $smtp->getMessage());
    }

    if(isset($details["bodyfile"])){
        if(file_exists(SAVE_LOCATION . EMAILBODY_LOCATION . "/" . $details["bodyfile"])) {

            $details["body"] = file_get_contents(SAVE_LOCATION . EMAILBODY_LOCATION . "/" . $details["bodyfile"]);

        } else {
            return api_error_raise("Email body file not available for recipients: " . serialize($details["recipients"]));
        }
    }

	$campaignId = isset($details["target"]["campaignid"]) ? $details["target"]["campaignid"] : null;
    // dkim
    if(($campaignId && isset($details['headers']['From']))
        || isset($details['headers']['From'])) {
        try {
            $dkim_key_resolver = new DkimKeyResolver(new DkimKeystoreFactory(), new DkimKeyFactory());

            if ($campaignId) {
                $group = api_campaigns_setting_getsingle($details["target"]["campaignid"], "groupowner");
                $dkim_key_resolver->setCampaign($campaignId, $group);
            }

            $dkim_key_resolver
                ->setFromEmail($details['headers']['From'])
                ->setDefaultDomain(EMAIL_DEFAULT_DOMAIN, api_system_setting_getsingle("EMAIL_DEFAULT_DKIM_SELECTOR"))
                ->resolve();

            $dkim_selector = $dkim_key_resolver->getResolvedDkimSelector();

            if($dkim_selector) {
                    if($campaignId && $group) {
                        api_misc_audit("Sending signed email " .
                            "group: {$group}, " .
                            "campaign: {$campaignId}");
                    }
                    api_misc_audit("Sending signed email " .
                        "from: {$details['headers']['From']}, " .
                        "selector: {$dkim_selector}");

                $dkimHeaders = api_email_sign_email(
                    $dkim_selector,
                    $dkim_key_resolver->getResolvedKey(),
                    $details['headers']['From'],
                    $details['headers'],
                    $details['body']
                );

                if($dkimHeaders) {
                    $details["body"] = api_email_normalize_convert_line_breaks($details["body"], "\r\n");
                    $details["headers"]["DKIM-Signature"] = trim(explode(":", $dkimHeaders, 2)[1]);
                } else {
                    throw new \Services\Exceptions\Email\EmailSendException(
                        "Could not sign the email with DKIM!: campaign: '{$campaignId}' group owner: '{$group}', selector: {$dkim_selector}"
                    );
                }
            }
        } catch (\Exception $e) {
            if ( $campaignId) {
                api_data_responses_add(
                    $details["target"]["campaignid"],
                    $details["target"]["eventid"],
                    $details["target"]["targetid"],
                    $details["target"]["targetkey"],
                    "SENDFAILED",
                    "DKIMERROR"
                );
            }
            if(isset($details["bodyfile"])) {
                api_email_remove_email_file($details['bodyfile']);
            }
            api_error_raise($e->getMessage());
            return true;
        }
    }

    $mail = $smtp->send($details["recipients"], $details["headers"], $details["body"]);

    $statsd->increment("morpheus.email.emails");

    if(PEAR::isError($mail)) {

        if(preg_match("/Domain not found/", $mail->getMessage()) OR preg_match("/Malformed DNS server reply/", $mail->getMessage()) OR preg_match("/need fully\-qualified address/", $mail->getMessage())){

            if(isset($details["target"]["campaignid"])){
                api_data_responses_add($details["target"]["campaignid"], $details["target"]["eventid"], $details["target"]["targetid"], $details["target"]["targetkey"], "HARDBOUNCEREASON", "Domain not found");
                api_data_responses_add($details["target"]["campaignid"], $details["target"]["eventid"], $details["target"]["targetid"], $details["target"]["targetkey"], "HARDBOUNCE", "YES");
                api_restrictions_baddata_add("email", $details["target"]["destination"]);
            }

            if(isset($details["bodyfile"])) {
                api_email_remove_email_file($details['bodyfile']);
            }

            unset($smtp);

            return true;

        } else return api_error_raise("Failed to send - " . $mail->getMessage());

    } else {

        if(isset($details["campaignid"])){
            api_data_responses_add($details["campaignid"], $details["eventid"], $details["targetid"], $details["targetkey"], "SENT", date("Y-m-d H:i:s"));
            api_data_callresult_add($details["campaignid"], $details["eventid"], $details["targetid"], "SENT");
        }

        if(isset($details["bodyfile"])) {
            api_email_remove_email_file($details['bodyfile']);
        }

        unset($smtp);
        unset($mail);
        return true;

    }

    unset($mail);
}

function api_queue_process_report($details) { return api_campaigns_summaryemail($details); }

function api_queue_process_postback($details){

	if($details["url"] == false) return false;

	$sanitized_url = preg_replace('#://([^:]+):[^@]+@#', '://\1:[REDACTED]@', $details["url"]);

        //open connection
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $details["url"]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

        //execute post
	$contents = curl_exec($ch);

	curl_close($ch);

	if($contents == false) return false;

	if(preg_match("/^OK/i", $contents)) {
		api_misc_audit("POSTBACK_OK", $serialize($sanitized_url));
		return true;
	} else {
		api_misc_audit("POSTBACK_FAILED", "Returned: " . $contents . "; URL: " . serialize($sanitized_url));
		return false;
	}

}

function api_queue_process_restpostback($details){

	if(empty($details["url"]) OR empty($details["payload"])) return false;

	$sanitized_url = preg_replace('#://([^:]+):[^@]+@#', '://\1:[REDACTED]@', $details["url"]);

    //open connection
	$ch = curl_init();
	$nopayload = false;

	if (isset($details["user_id"]) && $details["user_id"]) {
		// content type option
		$nopayload = api_users_tags_get(
			$details["user_id"],
			USER_TAGS_RESTPOSTBACK_NO_PAYLOAD
		);

		// auth
		$auth = api_users_setting_get_multi_byitem(
			$details["user_id"],
			[USER_SETTING_RESTPOSTBACK_USERNAME, USER_SETTING_RESTPOSTBACK_PASSWORD]
		);
		if (2 === count(array_filter($auth))) { // array_filter checks both are set
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt(
				$ch,
				CURLOPT_USERPWD,
				sprintf(
					'%s:%s',
					$auth[USER_SETTING_RESTPOSTBACK_USERNAME],
					api_misc_decrypt_base64($auth[USER_SETTING_RESTPOSTBACK_PASSWORD])
				)
			);
		}
	}

	curl_setopt($ch, CURLOPT_URL, $details["url"]);

	if ($nopayload) {
		// http_build_query for multidimentional array
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($details["payload"]));
	} else {
		curl_setopt($ch, CURLOPT_POSTFIELDS, ["payload" => json_encode($details["payload"])]);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);

	if(defined("PROXY_EXTERNAL")) curl_setopt($ch, CURLOPT_PROXY, PROXY_EXTERNAL);

    //execute post
	$contents = curl_exec($ch);
	if($contents === false) {

		api_misc_audit("POSTBACK_REST_FAILED", "Returned: " . curl_errno($ch) . "; URL: " . serialize($sanitized_url));
		return false;

	} else $result = curl_getinfo($ch);

	curl_close($ch);

	if($result["http_code"] == 200) {
		return true;
	} else {
		api_misc_audit("POSTBACK_REST_FAILED", "Non-200 response. Returned: " . $result["http_code"] . " {$contents}; URL: " . serialize($sanitized_url));
		return false;
	}

}

function api_queue_process_addtarget($details){

	if(api_targets_add_single($details["campaignid"], $details['destination'], $details['targetkey'], $details['priority'])){

		if($details['startonload'] == 1) api_campaigns_setting_set($details["campaignid"], "status", "ACTIVE");

		return true;

	} else return false;

}

function api_queue_process_wash($details){

	$target = api_targets_getinfo($details["targetid"]);

	if($target == false) return false;

	$treatment = api_wash_prefixtreatment($details["destination"]);

	if($treatment["method"] == "hlr"){

		$result = api_hlr_process($details["destination"]["destination"]);

		if($result == FALSE){
			$status = "INDETERMINATE";
			$reason = "HLR_LOOKUPFAIL";
		} elseif($result["active"]) {
			$status = "CONNECTED";
			$reason = "HLR_CONNECTED";
		} elseif(!$result["active"]){
			$status = "DISCONNECTED";
			$reason = "HLR_DISCONNECTED";
		}

		if(!isset($result["carriercode"])) $result["carriercode"] = null;

		if($result["carriercode"] == "23455") {

			$status = "INDETERMINATE";
			$reason = "UNSUPPORTED_NETWORK";
		}

		$eventid = api_misc_uniqueid();

		api_data_responses_add($target["campaignid"], $eventid, $target["targetid"], $target["targetkey"], "status", $status);

		if(!empty($result["carriercode"])) api_data_responses_add($target["campaignid"], $eventid, $target["targetid"], $target["targetkey"], "rt-carriercode", $result["carriercode"]);

		if(isset($result["hlrcode"])) api_data_responses_add($target["campaignid"], $eventid, $target["targetid"], $target["targetkey"], "rt-hlrcode", $result["hlrcode"]);

		$sql = "INSERT INTO `wash_out` (`userid`, `destination`, `billingtype`, `status`, `reason`, `returncarrier`, `carriercode`, `errors`, `billing_products_region_id`, `billing_products_destination_type_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$rs = api_db_query_write($sql, array(49, $details["destination"]["destination"], "wash" . $details["destination"]["type"], $status, $reason, 1, $result["carriercode"], 0, $details["destination"]['billing_region_id'], $details["destination"]['billing_destination_type_id']));

		if(($status == "INDETERMINATE") AND ($reason != "UNSUPPORTED_NETWORK")) api_targets_updatestatus($target["targetid"], "REATTEMPT", time()+10, 1);
		else api_targets_updatestatus($target["targetid"], "COMPLETE", null);

		return true;

	} else return false;

}

function api_queue_process_pbxcomms($details){

	switch($details["action"]) {
		case "pbxstatus":
			return api_voice_servers_channels_json();
		case "hangup":
			return api_voice_servers_hangup($details["serverid"], $details["channel"]);
		case "hangupbytargetid":
			return api_voice_servers_hangup_bytargetid($details["targetid"]);
		case "confbridgelist":
			return serialize(api_voice_servers_confbridge_list($details["conference"], $details["servers"]));
		case "confbridgekick":
			return api_voice_servers_confbridge_kick($details["conference"], $details["serverid"], $details["channel"]);
		case "generate":
			$target = api_targets_getinfo($details["targetid"]);
			if(empty($target)) return false;
			return api_voice_generate($target);
		default:
			return true;
	}

}

function api_queue_process_smsdr($details){

	return api_sms_receive_dr($details);

}

function api_queue_process_wash_out($details){

	global $statsd;

	require_once(__DIR__ . "/api_wash.php");

	$result = api_wash_preflight($details["destination"], true, array("eventid" => $details["eventid"]));

	if(is_array($result)) return api_wash_out_save($details["eventid"], $result["status"], $result["reason"], $result["carriercode"]);

	$treatment = api_wash_prefixtreatment($details["destination"]);

	if($treatment["method"] == "none"){

		api_wash_out_save($details["eventid"], "INDETERMINATE", "UNSUPPORTED_NETWORK", $treatment["carriercode"]);
		return true;

	} elseif($treatment["method"] == "hlr"){

		$result = api_hlr_process($details["destination"]["destination"]);

		if($result == FALSE){
			$status = "INDETERMINATE";
			$reason = "HLR_LOOKUPFAIL";
		} elseif($result["active"]) {
			$status = "CONNECTED";
			$reason = "HLR_CONNECTED";
		} elseif(!$result["active"]){
			$status = "DISCONNECTED";
			$reason = "HLR_DISCONNECTED";
		}

		if(!isset($result["carriercode"])) $result["carriercode"] = null;

		if($status == "INDETERMINATE") {
			api_misc_audit("WASH_FAIL", "INDETERMINATE - HLR FAIL - " . $details["destination"]["destination"]);
			return false;
		} else {

			if($result["carriercode"] == "23455") {

				$status = "INDETERMINATE";
				$reason = "UNSUPPORTED_NETWORK";
			}

			api_wash_out_save($details["eventid"], $status, $reason, $result["carriercode"]);
			return true;
		}

	} else {
        /** @var PhoneNumberValidationServiceInterface $phoneNumberValidationService */
        $phoneNumberValidationService = ContainerAccessor::getContainer()->get(PhoneNumberValidationServiceInterface::class);
        try {
            $id = $phoneNumberValidationService->postNumber($details['destination']['destination']);
        } catch (Exception $exception) {
            api_misc_audit("WASH_FAIL", "SEND FAILED=" . $exception->getMessage());
            api_wash_out_save($details["eventid"], "INDETERMINATE", "PING_FAILED");
			return true;
        }

      	$washResultDelay = api_system_setting_getsingle('WASH_RESULT_DELAY_SECONDS') ? api_system_setting_getsingle('WASH_RESULT_DELAY_SECONDS') : '1';
        $notBefore = new \DateTime('+' . $washResultDelay . ' seconds');

      return api_queue_add('wash_out_result', ['id'=> $id, 'eventid'=> $details["eventid"]], $notBefore->format('Y-m-d H:i:s'));
        
	}

}

function api_queue_process_wash_out_result($details, $workload) {
    /** @var PhoneNumberValidationServiceInterface $phoneNumberValidationService */
  
  $sql = "SELECT * FROM `wash_out` WHERE `id` = ? and timestamp < DATE_SUB(NOW(), INTERVAL ? SECOND)";
    $rs = api_db_query_read($sql, array($details['eventid'], 5));
    if(($rs->RecordCount() > 0)) {

	    return api_wash_out_save($details["eventid"], "INDETERMINATE", "PING_FAILED");
    }
  
    $phoneNumberValidationService = ContainerAccessor::getContainer()->get(PhoneNumberValidationServiceInterface::class);
    try {
        $result = $phoneNumberValidationService->retrieveResult($details['id']);
    } catch (Exception $exception) {
        api_misc_error("WASH_FAIL", "RETRIEVE FAILED=" . $exception->getMessage());
        api_wash_out_save($details["eventid"], "INDETERMINATE", "PING_FAILED");
        return true;
    }

   //We don't need to back off exponentially because the validation result is required pretty quick and the retries will be limited.
    if ($result === PhoneNumberValidationServiceInterface::STATUS_PENDING) {
    	$notBefore = new \DateTime('+' . 1 . ' seconds');
         return api_queue_add('wash_out_result', ['id'=> $details['id'], 'eventid'=> $details["eventid"]], $notBefore->format('Y-m-d H:i:s'));
    }

    switch ($result) {
        case PhoneNumberValidationServiceInterface::STATUS_CONNECTED:
            $status = 'CONNECTED';
            $reason = "PING_CONNECTED";
            break;

        case PhoneNumberValidationServiceInterface::STATUS_DISCONNECTED:
            $status = 'DISCONNECTED';
            $reason = "PING_DISCONNECTED";
            break;

        default:
            $status = 'INDETERMINATE';
            $reason = "PING_FAILED";
            break;
    }

    return api_wash_out_save($details["eventid"], $status, $reason);
}

function api_queue_process_filesync($details){

	$supportedpaths = array("audio", "dialplans", "sip", "iax");

	if(!isset($details["servers"]) OR !is_array($details["servers"])) $details["servers"] = api_voice_servers_listall_active();

	if(!isset($details["paths"])) $details["paths"] = $supportedpaths;

	foreach($details["servers"] as $serverid => $name){

		foreach($details["paths"] as $path){

			$path = strtolower($path);

			if(!in_array($path, $supportedpaths)) {
				api_misc_audit("FILESYNC_ERROR", "Got a file sync request for an unknown type. Type=" . $path);
				return false;
			} else {

				$command = "/usr/bin/rsync -ar --delete --timeout=10 -e 'ssh -i /home/deploy/.ssh/id_rsa -o StrictHostKeyChecking=no' " . READ_LOCATION . "/" . $path . "/ storage@" . api_voice_servers_setting_getsingle($serverid, "ip") . ":" . STORAGE_PATH . "/" . $path . "/";

				exec($command, $result, $return_var);

				if($return_var != 0) api_misc_audit("FILESYNC_ERROR", "Server=" . $name . "; Path=" . $path . "; Code=" . $return_var . ";");
				else api_misc_audit("FILESYNC_OK", "Server=" . $name . "; Path=" . $path);
			}
		}
	}

	return true;
}

function api_queue_process_cron($details) {

	if(empty($details["cronid"]) OR !is_numeric($details["cronid"])) return false;

	$scriptname = api_cron_setting_getsingle($details["cronid"], "scriptname");

	if(preg_match("/^[a-z0-9\/_\-]+\.php$/i", $scriptname) AND is_readable(__DIR__ . "/scripts/" . $scriptname)) {

		api_misc_audit("CRON", "Executing=" . $details["cronid"]);

		$command = 'CRON_ID=' . escapeshellarg($details['cronid']) .' ';
		$command .= escapeshellcmd('/usr/bin/php');
		$command .= ' '.escapeshellarg(__DIR__  . '/scripts/' . $scriptname);

		// Pass optional parameters to the cron task
		if(!empty($details["parameters"]) AND is_array($details["parameters"])) {
			foreach($details["parameters"] as $parameter) {
				$command .= " " . escapeshellarg($parameter);
			}
		}

		exec($command, $output);

		api_cron_setting_set($details["cronid"], "lastrunoutput", implode("\n", $output));
		api_cron_setting_set($details["cronid"], "lastrun", time());

	} else api_misc_audit("CRON", "Failed=" . $details["cronid"]);

	return true;
}

function api_queue_process_kml_export($details, array $workload) {
	$campaignId = $details[PlotterKmlDataExtractionParams::CAMPAIGN_ID_KEY];
	$notificationEmails = $details[PlotterKmlDataExtractionParams::NOTIFICATION_EMAILS_KEY];
	$userId = $details[PlotterKmlDataExtractionParams::USER_ID_KEY];
	$export_type = 'KML';

	if (!_api_queue_is_extract_plotter_data_ready($details, $campaignId, $export_type)) {
		// postponed
		return false;
	}
	api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT, strtotime('now'));


	$localFile = tempnam('/tmp', 'plotter-kml-' . $campaignId . '-' . api_misc_uniqueid());
	if (!PlotterFunctions::plotterDataExportGetFile($details['filename'], $localFile)) {
		api_error_raise('Failed to read kml export file for plotter. File name:' . $details['filename']);
		// Do not need to retry since it is a system failure.
		return true;
	}

	$kml = file_get_contents($localFile);
	if (!@unlink($localFile)) {
		api_error_raise('Failed to remove plotter kml file from the local directory');
	}

	$details[PlotterKmlDataExtractionParams::KML_KEY] = $kml;

	$plotterExtractionStrategy = new PlotterKmlExtractionStrategy();
	$plotterExtractionContext = new PlotterDataExtractionContext($plotterExtractionStrategy, $userId, $campaignId, $notificationEmails);

	if(!_api_queue_extract_plotter_data($plotterExtractionContext, $details, $campaignId, $workload, $export_type)) {
		api_campaigns_setting_delete_single($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT);

		return false;
	}

	if (!PlotterFunctions::plotterDataExportRemoveFile($details['filename'])) {
		api_error_raise('Failed to remove plotter kml remote file. File name: ' . $details['filename']);
	}

	api_campaigns_setting_delete_single($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT);

	return true;
}

function api_queue_process_bulk_export($details, array $workload) {
	$campaignId = $details[PlotterBulkDataExtractionParams::CAMPAIGN_ID_KEY];
	$notificationEmails = $details[PlotterBulkDataExtractionParams::NOTIFICATION_EMAILS_KEY];
	$userId = $details[PlotterBulkDataExtractionParams::USER_ID_KEY];
	$export_type = 'BULK';

	if (!_api_queue_is_extract_plotter_data_ready($details, $campaignId, $export_type)) {
		// postponed
		return false;
	}
	api_campaigns_setting_set($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT, strtotime('now'));

	$plotterExtractionStrategy = new PlotterBulkExtractionStrategy();
	$plotterExtractionContext = new PlotterDataExtractionContext($plotterExtractionStrategy, $userId, $campaignId, $notificationEmails);

	$res = _api_queue_extract_plotter_data($plotterExtractionContext, $details, $campaignId, $workload, $export_type);

	api_campaigns_setting_delete_single($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT);

	return $res;
}

function api_queue_process_fileupload($details, array $workload) {

    $queueId = $details['queue_id'];
    $attempts = isset($details['attempts']) ? $details['attempts']: 0;
    $maxAttempts = defined('FILE_UPLOAD_QUEUE_MAX_ATTEMPTS') ? FILE_UPLOAD_QUEUE_MAX_ATTEMPTS : 10;

    $qm = ContainerAccessor::getContainer()->get(QueueManager::class);
    $em = ContainerAccessor::getContainer()->get(EntityManager::class);

    /**
     * @var $queuedItem QueueItem
     */
    $queuedItem = $em->getRepository(QueueItem::class)->find($queueId);

    if ((array_key_exists("errors", $workload) && !api_queue_should_the_job_get_reattempted($workload)) || $attempts > $maxAttempts) {
        api_error_raise("FILE_UPLOAD_FAILURE: Queue item: {$queueId} exceeded the max attempts.");
        $qm->failedToRun(
            $queuedItem,
            QueueProcessStatusEnum::FAIL()->getValue(),
            null,
            json_encode(["Could not not run in the allocated time frame - please try again"])
        );
        return true;
    }

    if (!$queuedItem->isCanRun()) {
        api_misc_audit(
            "FILE_UPLOAD_QUEUE", "A queued item was passed to be processed - but is not in can run state - cancelled"
        );
        return true; // Dead
    }

    if (!$qm->canRunNow($queuedItem)) {
        $nextTry = new DateTime("now +1 minute");
        api_misc_audit(
            "FILE_UPLOAD_QUEUE",
            "Item can't run yet, {$queuedItem->getId()} requeuing for " . $nextTry->format("Y-m-d H:i:s")
        );
        $payload = [
            "queue_id" => $queuedItem->getId(),
            "attempts" => ($attempts + 1)
        ];
        $queuedItem->setData(json_encode(["Trying again " . $nextTry->format("Y-m-d H:i:s")]));
        $qm->persistToQueue($queuedItem);
        api_queue_add(QueueProcessTypeEnum::FILEUPLOAD()->getValue(),
            $payload,
            $nextTry->format("Y-m-d H:i:s")
        );
        return true;
    }

    if ($queuedItem && ($queueFiles = $queuedItem->getQueueFiles())) {

        if ($queueFiles->isEmpty()) {
            api_error_raise(
                "FILE_UPLOAD_FAILURE: No files to process for file for queue id {$queuedItem->getId()}"
            );
            return true; // Dead
        }

        $tmpPath = "";
        try {
            $qm->startRun($queuedItem);
            /**
             * @var $queuedFile \Models\Entities\QueueFile
             */
            $queuedFile = $queuedItem->getQueueFiles()->first();

            PCIRecorder::getInstance()->start();

            // Write the blob to the filesystem - api_targets_fileupload requires it
            $uniqFileName = uniqid("fileupload-")."-".$queuedFile->getFileName();
            $tmpDir = defined('FILEPROCESS_TMP_LOCATION') ? FILEPROCESS_TMP_LOCATION : "/tmp";
            $tmpPath = $tmpDir . "/" . $uniqFileName;

            $queuedFile->storeTmpFile($tmpPath);

            $results = api_targets_fileupload(
                $queuedItem->getCampaignId(),
                $tmpPath,
                $uniqFileName,
                true
            );

            if($results) {
                $qm->endRun(
                    $queuedItem,
                    QueueProcessStatusEnum::SUCCESS()->getValue(),
                    json_encode($results),
                    json_encode(api_targets_fileupload_result_builder($results))
                );
                api_misc_audit("FILE_UPLOAD","Queued upload was successful: ".$queuedItem->getReturnText());
                PCIRecorder::getInstance()->stop();
                return true; // Success
            } else {
                throw new Exception("Failed to upload targets from file - check the logs");
            }
        } catch (Exception $e) {
            $qm->endRun($queuedItem, QueueProcessStatusEnum::FAIL()->getValue(), $e->getMessage(), json_encode(api_error_geterrors()));
            api_error_raise("FILE_UPLOAD_FAILURE: ".$e->getMessage()) ;
        } finally {
            $fs = new Filesystem();
            if ($fs->exists($tmpPath)) {
                $fs->remove($tmpPath);
            }
            PCIRecorder::getInstance()->stop();
        }
        return true; // Dead
    }

    api_error_raise("FILE_UPLOAD_FAILURE: Could not find files for id {$queueId}");
    return true; // Dead
}

function api_queue_process_webhook($details) {
	$factory = new WebhookFactory();
	$processor = new WebhookProcessor($factory);

	try {
		return $processor->processQueuedJob($details);
	} catch (\Exception $e) {
		return false;
	}
}

/**
 * @param PlotterDataExtractionContext $plotterExtractionContext
 * @param array                        $details
 * @param integer                      $campaignId
 * @param array                        $workload
 * @param string                       $export_type
 * @return boolean
 */
function _api_queue_extract_plotter_data(
	PlotterDataExtractionContext $plotterExtractionContext,
	array $details,
	$campaignId,
	array $workload,
	$export_type
) {
	try {
		if (
		!$plotterExtractionContext->extractAndUpdateCampaign(
			$details,
			[
				'subject' => '[REACHTEL] - ' . $export_type . ' export successful',
				'body' => $export_type . ' export for campaign id: ' . $campaignId . ' has been successfully completed.'
			],
			!api_queue_should_the_job_get_reattempted($workload)
		)
		) {
			api_misc_audit($export_type . '_EXPORT', 'Plotter ' . $export_type . ' export failed for campaign id:' . $campaignId . '. Error count:' . $workload["errors"]);
			return false;
		}
	} catch (\Exception $e) {
		api_error_raise('Something went wrong during plotter extract for campaignid ' . $campaignId . '. ' . $e->getMessage());
		// The job need not be retried if it is an exception as there will be some issue that needs to be looked at.
	}

	return true;
}

/**
 * @param array    $details
 * @param integer  $campaignId
 * @return boolean
 */
function _api_queue_is_extract_plotter_data_ready(array $details, $campaignId, $export_type) {
		$plotterimport = api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT);

		// postpone this job in case of multiple dedupe for the same campaign
		if ($plotterimport) {
			// if plotterimport has not been deleted for some reasons
			// then we force deleting it to avoid an infinite postpone queue loop
			if ($plotterimport < strtotime('2 hours ago')) {
				api_campaigns_setting_delete_single($campaignId, CAMPAIGN_SETTING_PLOTTER_IMPORT);

				// is ready again
				return true;
			} else {
				$seconds = 60;
				if (isset($details["eventid"]) && $details["eventid"]) {
					$sql = "UPDATE `event_queue` SET `notbefore` = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE `eventid` = ? LIMIT 1;";
					api_db_query_write($sql, [$seconds, $details["eventid"]]);
				} else {
					// making sure we set notbefore to not re-call api_queue_gearman_add()
					$eventid = api_queue_add(
						$details["queue"],
						$details["details"],
						date('Y-m-d H:i:s', strtotime("+{$seconds} seconds")) // important to not run gearman !!!!
					);

					if (!$eventid) {
						return api_error_raise(
							"{$export_type}_EXPORT CAMPAIGN_DEDUPE Postponed failed: Failed add campaign {$campaignId} to queue"
						);
					}
				}

				api_misc_audit(
					$export_type . '_EXPORT',
					"Plotter {$export_type} has been postponed ({$seconds} seconds) for campaign id: {$campaignId}"
				);

				// will try again in X seconds
				return false;
			}
		}

		return true;
}

/**
 * Instead of using a simple UPDATE user query, loop over each user
 * to make sure we trigger activity logs.
 *
 * @param array $details
 * @return boolean
 */
function api_queue_process_disable_all_users_from_group($details) {

	if(empty($details["groupid"]) OR !is_numeric($details["groupid"])) {
		api_error_raise(
			"Group id not set or does not exists when disabling all users"
		);
		return true;
	}

	// Get all user ids with that group owner
	$sql = "SELECT `id` FROM `key_store` WHERE `type` = ? AND `item` = ? AND `value` = ?;";
	$rs = api_db_query_read($sql, ['USERS', 'groupowner', $details["groupid"]]);

	if ($rs->RecordCount() > 0) {
		$closed = [];
		foreach ($rs->GetArray() as $user) {
			api_users_setting_set($user['id'], 'status', USER_STATUS_CLOSED);
			// kill current session
			api_users_setting_set($user['id'], 'sessionid', null);
			$closed[] = $user['id'];
		}

		if ($closed) {
			sort($closed);
			api_misc_audit(
				'DISABLE_ALL_USERS',
				sprintf(
					'Group id=%s, disabled all active users ids=%s',
					$details["groupid"],
					implode(',', $closed)
				)
			);
		}
	}

	return true;
}

/**
 * @param array $details
 * @return boolean
 */
function api_queue_process_delete_all_rest_tokens_from_group($details) {
	try {
		$policy = new RESTTokenPolicy($details["groupid"]);
		$policy->removeTokens();
	} catch (Exception $e) {
		api_error_raise(
			"Error when deleting all REST tokens for group={$details["groupid"]}: " . $e->getMessage()
		);
	}

	return true;
}

/**
 * @param array $details
 * @return boolean
 */
function api_queue_process_delete_all_records_from_group($details) {
	try {
		$policy = new UserGroupRecordsPolicy($details["groupid"]);		
		$policy->removeDoNotContactLists();
		$policy->removeSMSAndCampaignRecords();
		$policy->removeSMSReceived();
		$policy->removeTargetsOut();
		$policy->removeSMSOutRecords();
		$policy->removeWashOut();
	} catch (Exception $e) {
		api_error_raise(
			"Error when deleting all records for group={$details["groupid"]}: " . $e->getMessage()
		);

		$email["to"] 	      = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["from"]        = "ReachTEL Support <support@ReachTEL.com.au>";
		$email["subject"]     = "[ReachTEL] Error when deleting all records for group={$details["groupid"]}";
		$email["textcontent"] = "Hello,\n\nWe have received an error when deleting all records for group={$details["groupid"]}: " . $e->getMessage();
		$email["htmlcontent"] = "Hello,\n\nWe have received an error when deleting all records for group={$details["groupid"]}: " . $e->getMessage();

		api_email_template($email);
	}
}

/**
 * Valid queue items contain all queue items that the application allows to be sent to gearman
 * @return array
 */
function api_queue_get_valid_queue_items() {
	global $validqueues;

	return array_merge($validqueues, api_queue_get_isolated_queue_items());
}

/**
 * This returns queue items that runs in isolation and should not be send to the generic gearman_worker daemon
 * @return array
 */
function api_queue_get_isolated_queue_items() {
	return [QUEUE_NAME_PLOTTER_KML_EXPORT, QUEUE_NAME_PLOTTER_BULK_EXPORT];
}

function api_queue_should_the_job_get_reattempted(array $workload) {
	return (array_key_exists('errors', $workload) && $workload["errors"] < EVENTQUEUE_MAXERROR);
}

/**
 * Error handler
 *
 * @param int      $eventid
 * @param string   $queue
 * @param true|int $errors Set to true to force delete the job.
 * @return boolean
 */
function api_queue_error_handler($eventid, $queue, $errors = 0) {
	global $statsd;

	// Most jobs should fail after a short period (~60 seconds) however post backs should reattempt for much longer (~3 days)
	$maxerrors = ($queue == "restpostback" ? EVENTQUEUE_MAXERROR_POSTBACK : EVENTQUEUE_MAXERROR);

	if(true === $errors || $errors >= $maxerrors) {
		file_put_contents("/tmp/job-permerror-" . uniqid() . ".txt", serialize([
			'eventid' => $eventid,
			'queue' => $queue,
			'errors' => $errors,
			'maxerrors' => $maxerrors,
		]));

		$statsd->increment("morpheus.queue.errors.permanent." . $queue);

		api_misc_audit("JOB_PERMERROR", "Type=" . $queue . ";");

		$sql = "DELETE FROM `event_queue` WHERE `eventid` = ?";
		$rs = api_db_query_write($sql, [$eventid]);

	} else {
		$statsd->increment("morpheus.queue.errors.retry." . $queue);

		$sql = "UPDATE `event_queue` SET `errors` = `errors` + 1, `locked` = ?, `notbefore` = DATE_ADD(NOW(), INTERVAL ? SECOND)  WHERE `eventid` = ?";
		$rs = api_db_query_write($sql, [0, pow(EVENTQUEUE_ERROR_BACKOFF, $errors+1), $eventid]);
	}

	return (bool) $rs;
}

/**
 * Get EventId and NotBefore from `event_queue` by Queue and GroupId
 * 
 * @param string   	$queue
 * @param int 		$groupId
 * @return array()
 */
function api_queue_get_eventId_and_notBefore_for_queue_in_group($queue, $groupId)
{
	$eventId_and_notBefore = array();
	$sql = "SELECT `eventid`, `notbefore`, `details` FROM `event_queue` WHERE `queue` = ?";
	$rs = api_db_query_read($sql, [$queue]);
	while(!$rs->EOF){
		$details = unserialize($rs->Fields("details"));
		if($details["groupid"] == $groupId) {
			$eventId_and_notBefore[] = array('eventid' => $rs->Fields("eventid"), 'notbefore' => $rs->Fields("notbefore"));
		}
		$rs->MoveNext();
	}
	if(count($eventId_and_notBefore) > 0) {
		return array('eventid' => $eventId_and_notBefore[0]["eventid"], 'notbefore' => $eventId_and_notBefore[0]["notbefore"]);
	} else {
		return array();
	}
}
