<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200207060825 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable("queue_files");
        $table->addColumn("id", Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true)
            ->setAutoincrement(true);

        $table->addColumn("queue_id", Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(11)
            ->setNotNull(true);

        $table->addColumn("filename", Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn("data", Type::BLOB)
            ->setNotnull(true);

        $table->addColumn("created_at", Type::DATETIME)
            ->setNotnull(true);

        $table->setPrimaryKey(["id"]);
        $table->addIndex(["queue_id", "filename"], "queue_files_queue_id_filename_idx");
        $table->addForeignKeyConstraint("queue", ["queue_id"], ["id"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable("queue_files");
    }
}
