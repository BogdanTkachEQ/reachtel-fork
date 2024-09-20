<?php

use Services\Campaign\Hooks\Cascading\Creators\TemplateBasedCascadingCampaignCreator;
use Services\Customers\Toyota\Autoload\AutoloadStrategy;

function toyotabrand_handle_sms($array){

    // Set the user id to 83 unless it's passed in by one of the other toyota brand sms functions (which provide their own)
    $user_id = (isset($array['userid']) && is_numeric($array['userid'])) ? $array['userid'] : 83;

    $sms_account = $array['sms_account'];

    if(isset($array["target"]) AND is_array($array["target"])) {

        $array["target"]["campaignname"] = api_campaigns_setting_getsingle($array["target"]["campaignid"], "name");
        api_data_responses_add($array["target"]["campaignid"], 0, $array["target"]["targetid"], $array["target"]["targetkey"], "RESPONSE", $array['contents']);

        $elements = api_data_merge_get_all($array["target"]["campaignid"], $array["target"]["targetkey"]);
    }

    if(isset($array["target"]["campaignname"]) &&
        (preg_match("/SMSVoice/i", $array["target"]["campaignname"]) ||
            preg_match("/SMSHardship/i", $array["target"]["campaignname"])
        )
    ){

        if (preg_match("/call/i", $array["contents"])){

            if((date("N") < 6) AND (time() > strtotime(date("Y-m-d") . " " . api_sms_dids_tags_get($sms_account, "callreservation-openhour-" . date("N")))) AND (time() < strtotime(date("Y-m-d") . " " . api_sms_dids_tags_get($sms_account, "callreservation-closehour-" . date("N"))))){

                $campaignname = "ToyotaFS-CallMe-" . date("FY");
                $campaignid = api_campaigns_checkorcreate($campaignname, 31539);

                $targetkey = api_misc_uniqueid();

                $mergedata = array("date" => date("Y-m-d H:i:s"),
                    "customerrefnum" => $elements["chLease"],
                    "customernumber" => $array["target"]["destination"],
                    "unsuccessfulsms" => api_data_merge_process(api_sms_dids_tags_get($sms_account, "callreservation-unsuccessfulsms"), $array["target"]["targetid"], true));

                if (isset($elements[AutoloadStrategy::BRAND_COLUMN_NAME])) {
                    $mergedata[AutoloadStrategy::BRAND_COLUMN_NAME] = $elements[AutoloadStrategy::BRAND_COLUMN_NAME];
                }

                if($array["targetid"] = api_targets_add_single($campaignid, api_sms_dids_tags_get($sms_account, "callreservation-transfer"), $targetkey, 1, $mergedata)) {

                    api_sms_apisend($array["target"]["destination"], api_sms_dids_tags_get($sms_account, "callreservation-precallsms"), $user_id);

                    api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "source", "sms");
                    api_data_responses_add($campaignid, 0, $array["targetid"], $targetkey, "sourcecampaign", $array["target"]["campaignname"]);
                    api_campaigns_setting_set($campaignid, "status", "ACTIVE");

                }

                return true;

            } else {

                api_sms_apisend($array["from"], api_sms_dids_tags_get($sms_account, "callreservation-outofhours"), $user_id);

                return true;

            }

        } elseif (preg_match("/yes/i", $array["contents"]) && preg_match("/SMSHardship/i", $array["target"]["campaignname"])) {
            $mergedata = api_data_merge_get_all($array["target"]["campaignid"], $array["target"]["targetkey"]);
            $campaignid = api_sms_dids_tags_get($sms_account, "hardship-yes-response-campaign");
            if (!api_targets_add_single($campaignid, $array['from'], api_misc_uniqueid(), null, $mergedata)) {
                api_error_audit('FAILED_ADDING_TARGET', 'Adding target failed for toyota sms hardship response for destination' . $array['from']);
                return true;
            }

            api_campaigns_setting_set($campaignid, CAMPAIGN_SETTING_STATUS, CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE);
            return true;
        }

    } elseif(isset($elements["chBPAYNumber"]) AND isset($array["target"]["campaignname"]) AND preg_match("/Easter/i", $array["target"]["campaignname"])){

        if (preg_match("/^(?:reply )?b?pa[ty]/i", $array["contents"])){

            $message = "Your BPAY biller code is " . api_sms_dids_tags_get($sms_account, "custom_bpay-biller-code") . " and the reference is " . $elements["chBPAYNumber"];

            api_sms_apisend($array["target"]["destination"], $message, $user_id);

            return true;
        }


    } elseif(isset($elements["chBPAYNumber"]) AND isset($array["target"]["campaignname"]) AND preg_match("/Christmas/i", $array["target"]["campaignname"])){

        if (preg_match("/^bpay/i", $array["contents"]) OR preg_match("/^boat/i", $array["contents"]) OR preg_match("/^pa[yt]/i", $array["contents"]) OR preg_match("/^reply pa[yt]/i", $array["contents"])){

            $message = "Your BPAY biller code is " . api_sms_dids_tags_get($sms_account, "custom_bpay-biller-code") . " and the reference is " . $elements["chBPAYNumber"];

            api_sms_apisend($array["target"]["destination"], $message, $user_id);

            return true;
        }


    } elseif(isset($elements["chBPAYNumber"]) AND isset($elements["mArrearsTot"]) AND !empty($array["target"]["campaignname"]) AND preg_match("/[13]to8/i", $array["target"]["campaignname"])){

        // 3to8

        if (preg_match("/^bpay/i", $array["contents"]) OR preg_match("/^boat/i", $array["contents"]) OR preg_match("/^pa[yt]/i", $array["contents"]) OR preg_match("/^reply pa[yt]/i", $array["contents"])){

            $message = "Your BPAY biller code is " . api_sms_dids_tags_get($sms_account, "custom_bpay-biller-code") . " and the reference is " . $elements["chBPAYNumber"] . " for the amount of $" . sprintf("%01.2f", $elements["mArrearsTot"]) . ". To avoid $25 arrears follow up fee please pay within 5 days.";

            api_sms_apisend($array["from"], $message, $user_id);
            return true;

        } elseif (preg_match("/^retry/i", $array["contents"])){

            $message = "Thank you. We will re-attempt your payment of $" . sprintf("%01.2f", $elements["mArrearsTot"]) . " in 3 business days. To stop this payment contact our team on " . api_sms_dids_tags_get($sms_account, 'callback-number');

            api_sms_apisend($array["from"], $message, $user_id);
            return true;

        }

    } elseif(isset($elements["chBPAYNumber"]) AND isset($elements["mArrearsTot"]) AND !empty($array["target"]["campaignname"]) AND (preg_match("/" . TemplateBasedCascadingCampaignCreator::buildCampaignNameWithIteration('Voice', 2) . "/i", $array["target"]["campaignname"]) OR preg_match("/LowRisk/i", $array["target"]["campaignname"]))){

        // FollowUp and LowRisk

        if (preg_match("/^bpay/i", $array["contents"]) OR preg_match("/^boat/i", $array["contents"]) OR preg_match("/^pa[yt]/i", $array["contents"]) OR preg_match("/^reply pa[yt]/i", $array["contents"])){

            $message = "Your BPAY biller code is " . api_sms_dids_tags_get($sms_account, "custom_bpay-biller-code") . " and the reference is " . $elements["chBPAYNumber"] . " for the amount of $" . sprintf("%01.2f", $elements["mArrearsTot"]) . ". To avoid $25 arrears follow up fee please pay within 5 days.";

            api_sms_apisend($array["target"]["destination"], $message, $user_id);

            return true;
        }

    }

    // Everything else

    api_sms_apisend($array["from"], api_sms_dids_tags_get($sms_account, "default-sms-response"), $user_id);

    return true;

}
