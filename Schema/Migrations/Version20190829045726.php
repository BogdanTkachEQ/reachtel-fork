<?php

namespace Morpheus\Schema\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Morpheus\Schema\AbstractReachtelMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190829045726 extends AbstractReachtelMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $ids = [
            2,3,4,7,12,15,17,20,24,28,29,31,32,39,50,54,60,64,65,69,73,79,80,82,83,84,88,91,92,95,108,110,114,
            115,120,125,134,138,142,167,176,177,178,186,187,188,191,207,208,217,225,229,235,239,251,254,256,269,
            272,273,305,307,313,314,319,320,322,325,328,333,339,340,342,345,349,352,357,375,377,384,390,394,399,
            400,401,402,415,421,455,457,460,461,464,478,484,485,486,500,512,514,525,526,527,551,558,568,583,585,
            586,587,590,591,594,596,607,619,622,623,624,625,626,633,636,637,639,641,642,645,649,651,654,655,659,
            662,664,666,669,673,677,681,683,686,687,688,689,692,703,704,706,709,716,736,742,746,749,750,754,755,
            763,764,769,770,774,777,778,780,781,782,783,784,788,789,790,791,793,795,798,803,804,806,807,808,810,
            811,812,816,817,819,821,825,827,828,829,830,831,832,836,837,838,839,840,841,842,843,844,845,846,847,
            848,735,761,650
        ];

        foreach ($ids as $id) {
            $accountNo = [
                840 => 1830001,
                735 => 287750001,
                761 => 40012430,
                650 => 708892998
            ];

            if (isset($accountNo[$id])) {
                $value = $accountNo[$id];
            } else {
                $value = 'RT' . str_pad($id, 6, 0, STR_PAD_LEFT);
            }

            $sql = 'INSERT INTO `key_store` (`type`, `id`, `item`, `value`) VALUES (:t, :id, :i, :v) ON DUPLICATE KEY UPDATE `value` = :v';
            $this->addSql($sql, ['t' => 'GROUPS', 'id' => $id, 'i' => 'selcommaccountno', 'v' => $value]);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            'DELETE FROM `key_store` WHERE `type`=:t AND item=:i',
            ['t' => 'GROUPS', 'i' => 'selcommaccountno']
        );
    }
}
