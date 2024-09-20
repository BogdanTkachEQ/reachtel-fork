<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190724054659 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('billing_runs');
        $table
            ->addColumn('id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true)
            ->setAutoIncrement(true);

        $table
            ->addColumn('status', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(1)
            ->setNotnull(true)
            ->setDefault(0);

        $table
            ->addColumn('errors', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotnull(true)
            ->setDefault(0);

        $table
            ->addColumn('timestamp', Type::DATETIME)
            ->setNotnull(true)
            ->setDefault($this->connection->getDatabasePlatform()->getCurrentTimestampSQL());

        $table
            ->addColumn('billing_period_start', Type::DATETIME)
            ->setNotnull(true);

        $table
            ->addColumn('billing_period_end', Type::DATETIME)
            ->setNotnull(true);

        $table
            ->setPrimaryKey(['id'])
            ->addIndex(['billing_period_start', 'billing_period_end']);

        $table = $schema->getTable('billing_transactions');

        $table
            ->addColumn('billing_run_id', Type::INTEGER)
            ->setUnsigned(true)
            ->setLength(10)
            ->setNotNull(true);

        $table
            ->addIndex(['billing_run_id'], 'billing_run_id_idx')
            ->addForeignKeyConstraint(
            'billing_runs',
            ['billing_run_id'],
            ['id']
        );
    }

    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('billing_transactions');
        $table->dropIndex('billing_run_id_idx');
        $table->dropColumn('billing_run_id');

        $schema->dropTable('billing_runs');
    }
}
