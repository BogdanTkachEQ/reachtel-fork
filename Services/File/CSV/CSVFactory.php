<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\File\CSV;

class CSVFactory
{
    public function createBasicCSV()
    {
        return new BasicCSV(new \ParseCsv\Csv());
    }
}
