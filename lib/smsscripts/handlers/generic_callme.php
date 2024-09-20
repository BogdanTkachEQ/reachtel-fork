<?php
/**
 * Tags:
 * autoreply-message-{keyword}
 * autoreply-nonkeyword-response
 * autoreply-expiry
 * api-account
 * callme-expiry
 * callme-expired
 * callme-openhour
 * callme-closehour
 * callme-openhour-{day}
 * callme-closehour-{day}
 * call-me-campaign-prefix
 * call-me-campaign-duplicate-id
 * autoreply-nonkeyword-response
 * autoreply-openhour
 * autoreply-closehour
 * autoreply-openhour-{day}
 * autoreply-closehour-{day}
 * autoreply-keyword-afterhours-message
 * autoreply-nonkeyword-afterhours-message
 * autoreply-keyword-afterhours-apiaccount
 * autoreply-nonkeyword-afterhours-apiaccount
 */

/**
 * @param $message
 * @return boolean
 * @throws Exception
 */
function handle_generic_callme($message) {
    $did = $message['sms_account'];
    $tags = api_sms_dids_tags_get($did);
    $prefix = 'autoreply-message-';
    $prefix_length = strlen($prefix);
    $day = date("N");

    if (isset($message["target"]["campaignid"])) {
        $campaignTags = api_campaigns_tags_get($message["target"]["campaignid"]);
        // Check if campaign has autoreply-message tags set
        $campaignTags = array_filter($campaignTags, function($key) use ($prefix, $prefix_length) {
            if (substr($key, 0, $prefix_length) === $prefix || $key === "autoreply-nonkeyword-response") {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_KEY);

        // Override autoreply-message values by those in campaign tags
        $tags = array_merge($tags, $campaignTags);
    }

    foreach ($tags as $key => $value) {
        if (substr($key, 0, $prefix_length) !== $prefix) {
            continue;
        }

        $keyword = substr($key, $prefix_length);
        // Anything starting with keyword or keyword followed by a space if in a sentence is matched.
        if (!preg_match("/^" . preg_quote($keyword) . "(?![^ ])/i", trim($message['contents']))) {
            continue;
        }

        if (!isset($message['target']) ||
            !isset($tags['autoreply-expiry']) ||
            !generic_call_me_handle_message_expiry(
                $message['target']['targetid'],
                $tags['autoreply-expiry'],
                $message['from'],
                $tags['api-account'],
                isset($tags['autoreply-expired']) ? $tags['autoreply-expired'] : null
            )
        ) {
            // Handle open hour and close hour for autoreply keywords
            if (!generic_call_me_handle_autoreply_openhours($message['from'], $tags, $day)) {
                return true;
            }

            api_sms_apisend($message['from'], $value, $tags['api-account']);

            if (!isset($message['target'])) {
                return true;
            }

            api_data_responses_add(
                $message["target"]["campaignid"],
                0,
                $message["target"]["targetid"],
                $message["target"]["targetkey"],
                'KEYWORD_RESPONSE',
                $message['contents']
            );

            api_data_responses_add(
                $message["target"]["campaignid"],
                0,
                $message["target"]["targetid"],
                $message["target"]["targetkey"],
                'KEYWORD_RESPONSE_TIMESTAMP',
                (new DateTime())->format('Y-m-d H:i:s')
            );
        }

        return true;
    }

    if (preg_match("/call[ ]?me/i", $message["contents"])) {
        if(!isset($message["target"]) || empty($message["target"])) {
            api_error_audit(__FILE__, "Call me was specified but there is no target?" . print_r($message, true));
            return true;
        }
        if (isset($tags['callme-expiry']) &&
            generic_call_me_handle_message_expiry(
                $message['target']['targetid'],
                $tags['callme-expiry'],
                $message['from'],
                $tags['api-account'],
                isset($tags['callme-expired']) ? $tags['callme-expired'] : null
            )
        ) {
            // Call me expired
            return true;
        }

        //Check if open and close hour for the day is set
        if (!isset($tags['callme-openhour-' . $day]) || !isset($tags['callme-closehour-' . $day])) {
            // Check if a generic open and close hour is set
            if (!isset($tags['callme-openhour']) || !$tags['callme-closehour']) {
                api_error_raise(sprintf('Open close hours tags missing for DID %s', $did));
                return true;
            }

            $callMeOpenHour = $tags['callme-openhour'];
            $callMeCloseHour = $tags['callme-closehour'];
        } else {
            $callMeOpenHour = $tags['callme-openhour-' . $day];
            $callMeCloseHour = $tags['callme-closehour-' . $day];
        }

        if (
            (time() >
                strtotime(date("Y-m-d") . " " . $callMeOpenHour) &&
                time() <
                strtotime(date("Y-m-d") . " " . $callMeCloseHour))
        ) {
            $callmeCampaignPrefixTag = 'call-me-campaign-prefix';
            $duplicateCampaignIdTag = 'call-me-campaign-duplicate-id';

            foreach ([$callmeCampaignPrefixTag, $duplicateCampaignIdTag] as $name) {
                if (!isset($tags[$name])) {
                    api_error_raise(sprintf('Mandatory tag %s missing for did id %s', $name, $did));
                    return true;
                }
            }

            $campaignname = $tags[$callmeCampaignPrefixTag] . date("FY");
            $duplicateCampaignId = $tags[$duplicateCampaignIdTag];

            $merge_data_campaign_name = api_data_merge_get_single(
                $message["target"]["campaignid"],
                $message["target"]["targetkey"],
                "campaignname"
            );

            $elements = [
                "customerrefnum" => $message["target"]["targetkey"],
                "campaignname" => $merge_data_campaign_name ?: ''
            ];
            $targetid = api_targets_add_callme($message["e164"], $elements, $message["target"]["campaignid"], $campaignname, $duplicateCampaignId, 'sms');

            if (!$targetid) {
                return  true;
            }

            // add callme flag to sms campaign
            api_data_responses_add(
                $message["target"]["campaignid"],
                0,
                $message["target"]["targetid"],
                $message["target"]["targetkey"],
                "CALLME",
                'yes'
            );

            api_data_responses_add(
                $message["target"]["campaignid"],
                0,
                $message["target"]["targetid"],
                $message["target"]["targetkey"],
                "CALLME_RESPONSE",
                $message['contents']
            );

            api_data_responses_add(
                $message["target"]["campaignid"],
                0,
                $message["target"]["targetid"],
                $message["target"]["targetkey"],
                "CALLME_RESPONSE_TIMESTAMP",
                (new DateTime())->format('Y-m-d H:i:s')
            );
        } else {
            api_sms_apisend(
                $message["target"]["destination"],
                $tags["callme-afterhours-message"],
                $tags["callme-afterhours-apiaccount"]
            );
        }
        // Handle open hour and close hour for autoreply non keyword
    } elseif (generic_call_me_handle_autoreply_openhours($message['from'], $tags, $day, 'nonkeyword')) {
        if (!isset($tags['autoreply-nonkeyword-response']) || trim($tags['autoreply-nonkeyword-response']) === '') {
            return true;
        }

        if (isset($tags["api-account"])) {
            api_sms_apisend($message['from'], trim($tags['autoreply-nonkeyword-response']), $tags["api-account"]);
        }

        if (!isset($message["target"]) || empty($message["target"])) {
            return true;
        }

        api_data_responses_add(
            $message["target"]["campaignid"],
            0,
            $message["target"]["targetid"],
            $message["target"]["targetkey"],
            'NON_KEYWORD_RESPONSE',
            $message['contents']
        );

        api_data_responses_add(
            $message["target"]["campaignid"],
            0,
            $message["target"]["targetid"],
            $message["target"]["targetkey"],
            'NON_KEYWORD_RESPONSE_TIMESTAMP',
            (new DateTime())->format('Y-m-d H:i:s')
        );
    }

    return true;
}

function generic_call_me_handle_message_expiry($targetid, $expiry_seconds, $from, $api_account, $expiry_message = null) {
    $timestamp = api_targets_get_last_sms_sent_time($targetid);

    if (!$timestamp) {
        return false;
    }

    $sent_date_time = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
    $datetime = new DateTime();

    if (($datetime->getTimestamp() - $sent_date_time->getTimestamp()) > $expiry_seconds) {
        // Message expired
        if ($expiry_message) {
            // Send expired reply if the tag is set
            api_sms_apisend($from, $expiry_message, $api_account);
        }

        return true;
    }

    return false;
}

function generic_call_me_handle_autoreply_openhours($destination, array $tags, $day, $autoreplyType = 'keyword') {
    if (isset($tags['autoreply-openhour-' . $day]) && isset($tags['autoreply-closehour-' . $day])) {
        $openHour = $tags['autoreply-openhour-' . $day];
        $closeHour = $tags['autoreply-closehour-' . $day];
    } else if (isset($tags['autoreply-openhour']) && isset($tags['autoreply-closehour'])) {
        $openHour = $tags['autoreply-openhour'];
        $closeHour = $tags['autoreply-closehour'];
    } else {
        return true;
    }

    if (
    (time() >
        strtotime(date("Y-m-d") . " " . $openHour) &&
        time() <
        strtotime(date("Y-m-d") . " " . $closeHour))
    ) {
        return true;
    }

    api_sms_apisend(
        $destination,
        $tags["autoreply-" . $autoreplyType . "-afterhours-message"],
        $tags["autoreply-" . $autoreplyType . "-afterhours-apiaccount"]
    );

    return false;
}
