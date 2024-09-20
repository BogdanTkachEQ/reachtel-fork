<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190625015625 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema
            ->createTable('billing_products_config_email');

        $table
            ->addColumn('id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true)
            ->setAutoIncrement(true);

        $table
            ->addColumn('billing_product_id',Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true);

        $table
            ->setPrimaryKey(['id'])
            ->addForeignKeyConstraint(
                'billing_products',
                ['billing_product_id'],
                ['id']
            );

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('billing_products_config_email');
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $this->connection->insert('billing_products_config_email', ['billing_product_id' => 9]);
    }
}
