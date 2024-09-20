<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190624043145 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_types');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(50)
            ->setNotnull(true);

        $table
            ->addColumn('description', Type::TEXT)
            ->setNotnull(false);

        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_types');

    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $data = [
            ['id' => 1, 'name' => 'Daily', 'description' => 'Products sent daily'],
            ['id' => 2, 'name' => 'Adhoc', 'description' => 'One-off products'],
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_types', $row);
        }
    }
}
