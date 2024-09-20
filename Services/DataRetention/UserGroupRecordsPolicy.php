<?php

namespace Services\DataRetention;

class UserGroupRecordsPolicy extends AbstractPolicy
{
        
    /**
     * Delete records from `do_not_contact_data` with ListIds that belongs to the specified Group Owner Id.
     */
    public function removeDoNotContactLists()
    {
        $lists = api_restrictions_donotcontact_lists(true, [$this->groupId]);
        $listIds = array_keys($lists);
        $accept_params = "";
        foreach ($listIds as $listId) {
            $accept_params .= "?, ";
        }
        $accept_params = trim($accept_params);
        
        $sql = "DELETE FROM `do_not_contact_data` 
        WHERE `listid` IN (
            ".substr($accept_params, 0, strlen($accept_params) - 1)."
        );";
        return api_db_query_write($sql, $listIds);
    }

    /**
     * Delete records from `sms_status` with eventids that belongs to the specified Group Owner Id identified from
     * `sms_sent` table using the obtained sms_accounts.
     */
    public function removeSMSAndCampaignRecords()
    {
        $sql = "DELETE FROM `sms_status` WHERE `eventid` IN (
            SELECT `eventid` from `call_results` where `value` = 'SENT' and `campaignid` in (
                SELECT `id` FROM `key_store` WHERE `type` = 'CAMPAIGNS' AND `item` = 'groupowner' and value = ?
            )
        );";
        if (api_db_query_write($sql, $this->groupId)) {
            $sql = "DELETE FROM `sms_sent` WHERE `eventid` IN (
                SELECT `eventid` from `call_results` where `value` = 'SENT' and `campaignid` in (
                    SELECT `id` FROM `key_store` WHERE `type` = 'CAMPAIGNS' AND `item` = 'groupowner' and value = ?
                )
            );";
            if (api_db_query_write($sql, $this->groupId)) {
                $campaignIds = api_groups_get_all_campaignids($this->groupId);

                $table_names_with_campaignid_field = [
                    'response_data',
                    'response_data_archive',
                    'call_results',
                    'call_results_archive',
                    'merge_data',
                    'merge_data_archive',
                    'targets',
                    'targets_archive',
                ];
                foreach ($campaignIds as $campaign) {
                    $campaignid = $campaign['id'];
                    $name = api_campaigns_setting_getsingle($campaignid, 'name');

                    foreach ($table_names_with_campaignid_field as $table) {
                        $sql = "DELETE FROM `{$table}` WHERE `campaignid` = ?;";
                        $rs = api_db_query_write($sql, [$campaignid]);
                        if (!$rs) {
                            return api_error_raise("ERROR: Query failed: {$sql}");
                        }
                    }

                    // Delete campaign
                    api_keystore_purge("CAMPAIGNS", $campaignid);
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Delete records from `sms_received` with sms_accounts that belongs to the specified Group Owner Id.
     */
    public function removeSMSReceived()
    {
        $sms_dids = api_groups_get_all_dids($this->groupId, KEY_STORE_TYPE_SMS_DIDS);
        $sms_accounts = array_keys($sms_dids);
        $accept_params = "";
        foreach ($sms_accounts as $sa) {
            $accept_params .= "?, ";
        }
        $accept_params = trim($accept_params);
        
        $sql = "DELETE FROM `sms_received` 
        WHERE `sms_account` IN (
            ".substr($accept_params, 0, strlen($accept_params) - 1)."
        ) AND `timestamp` <= ".date('Y-m-d H:i:s', strtotime('-'.QUEUE_NOT_BEFORE_DELETE_GROUP_RECORDS, time())).";";
        return api_db_query_write($sql, $sms_accounts);
    }

    /**
     * Delete records from `targets_out` with userids that belongs to the specified Group Owner Id.
     */
    public function removeTargetsOut()
    {
        $userids = api_users_list_all_by_groupowner($this->groupId);
        $accept_params = "";
        foreach ($userids as $userid) {
            $accept_params .= "?, ";
        }
        $accept_params = trim($accept_params);
        
        $sql = "DELETE FROM `targets_out` 
        WHERE `userid` IN (
            ".substr($accept_params, 0, strlen($accept_params) - 1)."
        );";
        return api_db_query_write($sql, $userids);
    }

    /**
     * Delete records from `sms_out_status` with ids that belongs to the specified Group Owner Id identified from
     * `sms_out` table using the obtained userids.
     */
    public function removeSMSOutRecords()
    {
        $userids = api_users_list_all_by_groupowner($this->groupId);
        $accept_params = "";
        foreach ($userids as $userid) {
            $accept_params .= "?, ";
        }
        $accept_params = trim($accept_params);
        
        $sql = "DELETE FROM `sms_out_status` 
        WHERE `id` IN (
            SELECT DISTINCT `id` FROM `sms_out` 
            WHERE `userid` IN (
                ".substr($accept_params, 0, strlen($accept_params) - 1)."
            )
        );";
        if (api_db_query_write($sql, $userids)) {
            $sql = "DELETE FROM `sms_out` 
            WHERE `userid` IN (
                ".substr($accept_params, 0, strlen($accept_params) - 1)."
            );";
            return api_db_query_write($sql, $userids);
        } else {
            return false;
        }
    }

    /**
     * Delete records from `wash_out` with userids that belongs to the specified Group Owner Id.
     */
    public function removeWashOut()
    {
        $userids = api_users_list_all_by_groupowner($this->groupId);
        $accept_params = "";
        foreach ($userids as $userid) {
            $accept_params .= "?, ";
        }
        $accept_params = trim($accept_params);
        
        $sql = "DELETE FROM `wash_out` 
        WHERE `userid` IN (
            ".substr($accept_params, 0, strlen($accept_params) - 1)."
        );";
        return api_db_query_write($sql, $userids);
    }
}
