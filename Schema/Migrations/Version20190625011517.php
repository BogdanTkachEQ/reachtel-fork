<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;
use Morpheus\Schema\Types\YesNoEnumType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625011517 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('billing_transactions');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true)
            ->setAutoIncrement(true);

        $table
            ->addColumn('group_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true);

        $table
            ->addColumn('billing_product_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true);

        $table
            ->addColumn('billing_channel_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(4)
            ->setNotNull(true);

        $table
            ->addColumn('transaction_timestamp', Type::DATETIME)
            ->setNotnull(true);

        $table
            ->addColumn('quantity',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(7)
            ->setNotNull(true);

        $table
            ->addColumn('subject', Type::STRING)
            ->setNotnull(false)
            ->setLength(80)
            ->setDefault(null);

        $table
            ->addColumn('username', Type::STRING)
            ->setNotnull(false)
            ->setLength(50)
            ->setDefault(null);

        $table
            ->addColumn('client_defined1', Type::STRING)
            ->setNotnull(false)
            ->setLength(64)
            ->setDefault(null);

        $table
            ->addColumn('processed', YesNoEnumType::TYPE_NAME)
            ->setNotnull(true)
            ->setDefault('n');

        $table
            ->addColumn('timestamp', Type::DATETIME)
            ->setNotnull(true)
            ->setDefault($this->connection->getDatabasePlatform()->getCurrentTimestampSQL());



        $table
            ->setPrimaryKey(['id'])
            ->addIndex(['group_id', 'billing_channel_id', 'billing_product_id'])
            ->addIndex(['timestamp'])
            ->addIndex(['billing_channel_id'])
            ->addIndex(['billing_product_id'])
            ->addForeignKeyConstraint(
                'billing_products',
                ['billing_product_id'],
                ['id']
            )
            ->addForeignKeyConstraint(
                'billing_channels',
                ['billing_channel_id'],
                ['id']
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_transactions');
    }
}
