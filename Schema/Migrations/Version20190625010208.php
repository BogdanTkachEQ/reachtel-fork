<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625010208 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_products_region');

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
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['name']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_products_region');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $data = [
            ['id' => 1, 'name' => 'Australia'],
            ['id' => 2, 'name' => 'New Zealand'],
            ['id' => 3, 'name' => 'Singapore'],
            ['id' => 4, 'name' => 'Great Britain'],
            ['id' => 5, 'name' => 'Philippines'],
            ['id' => 6, 'name' => 'Other'],
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_products_region', $row);
        }
    }
}
