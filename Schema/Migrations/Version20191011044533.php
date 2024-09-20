<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Services\Utils\SecurityZone;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191011044533 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = "ALTER TABLE `event_queue` MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync','kml_export','disable_all_users_from_group','delete_all_rest_tokens_from_group','bulk_export', 'webhook') NOT NULL";
        $this->addSql($sql);

        $sql = 'INSERT INTO `key_store` values (:tp, :id, :item, :val)';
        $items = [
            [
                'tp' => 'SECURITYZONE',
                'id' => SecurityZone::SINCH_INBOUND_SMS_SECURITY_ZONE,
                'item' => 'name',
                'val' => 'Sinch inbound SMS'
            ],
            [
                'tp' => 'SECURITYZONE',
                'id' => SecurityZone::SINCH_SMS_DR_SECURITY_ZONE,
                'item' => 'name',
                'val' => 'Sinch SMS delivery receipts'
            ]
        ];

        foreach ($items as $item) {
            $this->addSql($sql, $item);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = "ALTER TABLE `event_queue` MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync','kml_export','disable_all_users_from_group','delete_all_rest_tokens_from_group','bulk_export') NOT NULL";
        $this->addSql($sql);

        $sql = 'DELETE from `key_store` WHERE `type`="SECURITYZONE" AND id IN (:id1, :id2)';
        $this->addSql(
            $sql,
            [
                'id1' => SecurityZone::SINCH_INBOUND_SMS_SECURITY_ZONE,
                ':id2' => SecurityZone::SINCH_SMS_DR_SECURITY_ZONE
            ]
        );
    }
}
