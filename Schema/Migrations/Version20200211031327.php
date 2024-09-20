<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200211031327 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
	    $sql = "ALTER TABLE `event_queue` MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync','kml_export','disable_all_users_from_group','delete_all_rest_tokens_from_group','bulk_export','webhook','fileupload') NOT NULL";
	    $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = "ALTER TABLE `event_queue` MODIFY `queue` enum('sms','cron','sms_out','email','report','postback','restpostback','addtarget','wash','wash_out','imsi_out','emailvalidate_out','pbxcomms','smsdr','filesync','kml_export','disable_all_users_from_group','delete_all_rest_tokens_from_group','bulk_export','webhook') NOT NULL";
	    $this->addSql($sql);
    }
}
