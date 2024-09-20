<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

/**
 * Class CsvFunctions
 */
class CsvFunctions
{
    /**
     * @param string $file
     * @return array | boolean
     */
    public static function csvToArray($file)
    {
        $csv = file_get_contents($file);

        if (!$csv) {
            return false;
        }

        return self::csvStringToArray($csv);
    }

    /**
     * @param $csv
     * @return array
     */
    public static function csvStringToArray($csv)
    {
        $lines = explode("\n", $csv);
        $head = str_getcsv(array_shift($lines));

        $data = [];
        foreach ($lines as $line) {
            if (!trim($line)) {
                continue;
            }
            $data[] = array_combine($head, str_getcsv($line));
        }

        return $data;
    }

    /**
     * @param array $data
     * @param string $file
     * @return false|int
     */
    public static function arrayToCsv(array $data, $file)
    {
        return api_csv_file($file, $data);
    }
}
