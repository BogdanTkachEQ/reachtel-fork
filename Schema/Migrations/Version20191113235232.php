<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20191113235232 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('smtp_events');
        $table->addColumn('event_type', Type::STRING)
            ->setLength(255)
            ->setNotnull(false);
        $table->addIndex(["guid"], "smtp_events_guid_idx");
        $table->addIndex(["userid"], "smtp_events_userid_idx");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('smtp_events');
        $table->dropColumn("event_type");
        $table->dropIndex("smtp_events_guid_idx");
        $table->dropIndex("smtp_events_userid_idx");
    }
}
