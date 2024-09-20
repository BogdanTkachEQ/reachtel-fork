<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625010635 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_products_destination_type');

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
        $schema->dropTable('billing_products_destination_type');
    }

    public function postUp(Schema $schema)
    {
        $data = [
            ['id' => 1, 'name' => 'Mobile'],
            ['id' => 2, 'name' => 'Landline'],
            ['id' => 3, 'name' => 'Toll-Free'],
            ['id' => 4, 'name' => 'Premium Rate'],
            ['id' => 5, 'name' => 'Shared Cost'],
            ['id' => 6, 'name' => 'VOIP'],
            ['id' => 7, 'name' => 'Personal Number'],
            ['id' => 8, 'name' => 'Pager'],
            ['id' => 9, 'name' => 'UAN'],
            ['id' => 10, 'name' => 'Emergency'],
            ['id' => 11, 'name' => 'Short Code'],
            ['id' => 12, 'name' => 'Standard Rate'],
            ['id' => 13, 'name' => 'Unknown'],
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_products_destination_type', $row);
        }
    }
}
