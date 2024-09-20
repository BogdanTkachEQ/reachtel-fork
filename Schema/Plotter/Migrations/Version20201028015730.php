<?php

namespace Morpheus\Schema\Plotter\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20201028015730 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('DROP TABLE `veda-scored`');
        $this->addSql('DROP TABLE `veda15_BACKUP`');
        $this->addSql('DROP TABLE `veda15_myisam`');
        $this->addSql('DROP TABLE `veda15_original`');
        $this->addSql('DROP TABLE `veda-qld-scored`');
        $this->addSql('DROP TABLE `veda-wa-scored`');

        $sql = 'CREATE TABLE `veda15_new` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `prospectid` int(11) DEFAULT NULL,
          `state` enum(\'WA\',\'QLD\',\'NSW\',\'SA\',\'TAS\',\'VIC\',\'NT\',\'ACT\') DEFAULT NULL,
          `surname` varchar(100) DEFAULT NULL,
          `initial` varchar(100) DEFAULT NULL,
          `gender` enum(\'M\',\'F\') DEFAULT NULL,
          `agebracket` enum(\'18 - 24\',\'25 - 29\',\'30 - 34\',\'35 - 39\',\'40 - 44\',\'45 - 49\',\'50 - 54\',\'55 - 59\',\'60 - 64\',\'65 - 69\',\'70 - 74\',\'75 - 79\',\'80 - 84\',\'85+\') DEFAULT NULL,
          `street` varchar(100) DEFAULT NULL,
          `suburb` varchar(100) DEFAULT NULL,
          `postcode` varchar(4) DEFAULT NULL,
          `phone1` varchar(15) DEFAULT NULL,
          `phone2` varchar(15) DEFAULT NULL,
          `mobile1` varchar(15) DEFAULT NULL,
          `lat` float(12,6) DEFAULT NULL,
          `long` float(12,6) DEFAULT NULL,
          `point` point NOT NULL,
          PRIMARY KEY (`id`),
          KEY `postcode` (`postcode`),
          KEY `phone1` (`phone1`),
          KEY `lat` (`lat`,`long`),
          KEY `prospectid` (`prospectid`),
          KEY `mobile1` (`mobile1`),
          SPATIAL KEY `spidx_point` (`point`),
          KEY `phone2` (`phone2`)
        ) ENGINE=MyISAM AUTO_INCREMENT=45973961 DEFAULT CHARSET=utf8mb4';

        $this->addSql($sql);

        $sql = 'INSERT INTO `veda15_new` SELECT `id`, `prospectid`, `state`, `surname`, `initial`, `gender`, `agebracket`, 
            `street`, `suburb`, `postcode`, `phone1`, `phone2`, `mobile1`, `lat`, `long`, `point` FROM `veda15`';

        $this->addSql($sql);

        $this->addSql('RENAME TABLE `veda15` to `veda15_original`');

        $this->addSql('RENAME TABLE `veda15_new` to `veda15`');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('veda15');
        $table
            ->addColumn('Coalition_Score', Type::FLOAT)
            ->setPrecision(20)
            ->setScale(18)
            ->setNotnull(true);

        $table
            ->addColumn('Labor_Score', Type::FLOAT)
            ->setPrecision(20)
            ->setScale(18)
            ->setNotnull(true);

        $table
            ->addColumn('Green_Score', Type::FLOAT)
            ->setPrecision(20)
            ->setScale(18)
            ->setNotnull(true);

        $table
            ->addColumn('Undecided_Score', Type::FLOAT)
            ->setPrecision(20)
            ->setScale(18)
            ->setNotnull(true);
    }
}
