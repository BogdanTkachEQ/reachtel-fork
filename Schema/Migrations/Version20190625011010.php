<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625011010 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_channels');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('code', Type::STRING)
            ->setLength(10)
            ->setNotnull(true);

        $table
            ->addColumn('name', Type::STRING)
            ->setLength(20)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['id'])
            ->addUniqueIndex(['code']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_channels');
    }

    public function postUp(Schema $schema)
    {
        $data = [
            ['id' => 1, 'code' => 48, 'name' => 'WEB'],
            ['id' => 2, 'code' => 49, 'name' => 'API'],
        ];

        foreach ($data as $row) {
            $this->connection->insert('billing_channels', $row);
        }
    }
}
