<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;


/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20230712220018 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = "ALTER TABLE `event_queue` MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync','kml_export','disable_all_users_from_group','delete_all_rest_tokens_from_group','bulk_export','webhook','fileupload', 'delete_all_records_from_group', 'wash_out_result') NOT NULL";
            $this->addSql($sql);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = "ALTER TABLE `event_queue` MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync','kml_export','disable_all_users_from_group','delete_all_rest_tokens_from_group','bulk_export','webhook','fileupload', 'delete_all_records_from_group') NOT NULL";
            $this->addSql($sql);

    }
}

