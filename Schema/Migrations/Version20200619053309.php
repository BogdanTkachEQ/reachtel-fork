<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200619053309 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $campaignIds = $this->getCampaignIds();

        if (!$campaignIds) {
            print 'No campaign ids returned';
        }

        foreach ($campaignIds as $campaignId) {
            $this->connection->insert('key_store', ['id' => $campaignId, 'type' => 'CAMPAIGNS', 'item' => 'disabledownload', 'value' => 1]);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $campaignIds = $this->getCampaignIds();

        if (!$campaignIds) {
            print 'No campaign ids returned';
        }

        $sql = 'DELETE FROM key_store WHERE type="CAMPAIGNS" AND item="disabledownload"';

        $this->addSql($sql);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getCampaignIds()
    {
        $sql = 'SELECT id FROM key_store where type="CAMPAIGNS" AND item="lastupload" and (`value` like "Plotter%" OR `value` like "BulkExporter%")';

        $data = $this->connection->query($sql)->fetchAll();

        return array_map(
            function ($row) {
                return $row['id'];
            },
            $data
        );
    }
}
