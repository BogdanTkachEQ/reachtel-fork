<?php

function inbound_724($message) {
    // 61488822647

    $didId = 724;

    if (!isset($message['target']) || !isset($message['target']['destination'])) {
        // The inbound is not for a campaign
        return true;
    }

    $autoreplyMessageTagName = 'autoreply-message';
    $autoreplyExpiredTagName = 'autoreply-expired';
    $autoreplyOptOutTagName = 'autoreply-optout';
    $autoreplyApiAccountIdTagName = 'autoreply-apiaccountid';
    $expiryDateTagName = 'expiry-date';

    $tags = api_sms_dids_tags_get($didId);

    // Opt out handling
    $optOut = false;
    if(preg_match("/stop/i", trim($message["contents"]))) {
        // opt out should be done before anything
        // but to send SMS, needs to check $autoreplyApiAccountIdTagName so set a flag
        $optOut = true;
        api_restrictions_donotcontact_add("phone", $message["e164"], $tags["dnclist"]);
    }

    if (!isset($tags[$autoreplyApiAccountIdTagName])) {
        api_error_raise(sprintf('Missing tag %s in inbound script %d', $autoreplyApiAccountIdTagName, $didId));
        return true;
    }

    // opt out SMS needs to check $autoreplyApiAccountIdTagName
    if ($optOut) {
        if (isset($tags[$autoreplyOptOutTagName])) {
            api_sms_apisend($message['target']['destination'], $tags[$autoreplyOptOutTagName], $tags[$autoreplyApiAccountIdTagName]);
        } else {
            api_error_raise(sprintf('Missing tag %s in inbound script %d', $autoreplyOptOutTagName, $didId));
        }
        return true;
    }

    return true;
}