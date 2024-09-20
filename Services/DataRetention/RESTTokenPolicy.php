<?php

namespace Services\DataRetention;

class RESTTokenPolicy extends AbstractPolicy
{
    /**
     * Remove all user tokens for a spefici group
     */
    public function removeTokens()
    {
        $sql = "DELETE FROM `rest_tokens`
				WHERE `userid` IN (
					SELECT id
					FROM `key_store`
					WHERE `type` = ? AND `item` = ? AND `value` = ?
				);";

        $rs = api_db_query_write($sql, ['USERS', 'groupowner', $this->groupId]);
    }
}
