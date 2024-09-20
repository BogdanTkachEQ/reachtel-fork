<?php

function inbound_736($array) {
    // 61488822661

    if (!preg_match("/^call[ ]?me/i", $array["contents"])) {
        return true;
    }

    $sms_account = 736;

    $tags =api_sms_dids_tags_get($sms_account);

    $openingHourTagName = "callreservation-openhour-" . date("N");
    $closingHourTagName = "callreservation-closehour-" . date("N");

    $missingTags = array_diff([$openingHourTagName, $closingHourTagName], array_keys($tags));

    if ($missingTags) {
        api_error_audit("Missing tags for sms did 736: " . implode(',', $missingTags));
        return true;
    }

    if (isset($tags["callreservation-openhour-" . date("N")]) &&
        isset($tags["callreservation-closehour-" . date("N")]) &&
        (time() > strtotime(date("Y-m-d") . " " . $tags[$openingHourTagName])) &&
        (time() < strtotime(date("Y-m-d") . " " . $tags[$closingHourTagName]))
    ) {
        $campaignId = 203962; // AlintaEnergy-CallMe-January20
        $elements = api_data_merge_get_all($array["target"]["campaignid"], $array["target"]["targetkey"]);
        $elements["date"] = date("Y-m-d H:i:s");
        $elements["customernumber"] = $array["from"];
        $elements['accountnumber'] = $array["target"]["targetkey"];
        $targetkey = api_misc_uniqueid();
        try {
            $array["targetid"] = api_targets_add_single($campaignId, $tags["callme-destination"], $targetkey, 1, $elements);
        } catch (Exception $exception) {
            api_error_raise("Error when adding target from sms did script 736 :" . $exception->getMessage());
        }

        if($array["targetid"]) {

            api_sms_apisend($array['from'], $tags["callme-pleasewait-message"], $tags["autoreply-apiaccountid"]);

            api_data_responses_add($campaignId, 0, $array["targetid"], $targetkey, "source", "sms");
            api_data_responses_add($campaignId, 0, $array["targetid"], $targetkey, "sourcecampaign", $array["target"]["campaign"]);
            api_campaigns_setting_set($campaignId, "status", "ACTIVE");
        }

        return true;
    }

    api_sms_apisend($array['from'], $tags["callme-afterhours-message"], $tags["autoreply-apiaccountid"]);

    return true;
}
