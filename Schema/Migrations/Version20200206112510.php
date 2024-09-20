<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200206112510 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable("queue");
	    $table
		    ->addColumn('id',Type::INTEGER, ['autoincrement' => true])
		    ->setUnsigned(true)
		    ->setLength(11)
		    ->setNotNull(true);

	    $table
		    ->addColumn('process_type',Type::STRING)
		    ->setLength(255)
		    ->setNotNull(true);

	    $table
		    ->addColumn('campaign_id', Type::INTEGER)
		    ->setNotnull(true);

	    $table
		    ->addColumn('user_id',Type::INTEGER)
		    ->setNotNull(true);

	    $table
		    ->addColumn("priority", Type::INTEGER)
		    ->setNotnull(true)
		    ->setDefault(0);

	    $table
		    ->addColumn("is_running", Type::BOOLEAN)
		    ->setNotnull(true)
		    ->setDefault(false);

        $table
            ->addColumn("can_run", Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

	    $table
		    ->addColumn("has_run", Type::BOOLEAN)
		    ->setNotnull(true)
		    ->setDefault(false);

	    $table->addColumn("created_at", Type::DATETIME)->setNotnull(true);
	    $table->addColumn("ran_at", Type::DATETIME)->setNotnull(false);
	    $table->addColumn("return_code", Type::INTEGER)->setNotnull(false);
	    $table->addColumn("return_text", Type::TEXT)->setNotnull(false);
        $table->addColumn("data", Type::TEXT)->setNotnull(false);

	    $table->setPrimaryKey(["id"]);
	    $table->addIndex(["process_type", "can_run", "created_at", "has_run"], "queue_process_type_created_has_run_idx");
		$table->addIndex(["is_running"], "queue_is_running_idx");
		$table->addIndex(["user_id"], "queue_user_id_idx");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable("queue");
    }
}
