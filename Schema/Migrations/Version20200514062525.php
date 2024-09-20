<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds groupownerid to DONOTCONTACT records in key_store.   ACTU DNC lists are set
 */
class Version20200514062525 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
     $sql = "INSERT INTO key_store (`id`, `type`, `item`, `value`) 
                SELECT k.id, 'DONOTCONTACT', 'groupownerid', '2'
                FROM key_store k
                WHERE type='DONOTCONTACT'
                AND item = 'name'
                AND k.id NOT IN ('510')
                AND NOT EXISTS (SELECT 1
                                  FROM key_store l
                                  WHERE k.id = l.id AND l.type = 'DONOTCONTACT' AND l.item = 'groupownerid'
                                 );";

        $ACTUsql = "INSERT INTO key_store (`id`, `type`, `item`, `value`) 
                SELECT k.id, 'DONOTCONTACT', 'groupownerid', '217'
                FROM key_store k
                WHERE type='DONOTCONTACT'
                AND item = 'name'
                AND k.id IN ('510')
                AND NOT EXISTS (SELECT 1
                                  FROM key_store l
                                  WHERE k.id = l.id AND l.type = 'DONOTCONTACT' AND l.item = 'groupownerid'
                                 );";
        $this->addSql($sql);
        $this->addSql($ACTUsql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE from key_store 
                            WHERE type='DONOTCONTACT' 
                            AND item = 'groupownerid' 
                            AND value IN('2', '217')");
    }
}
