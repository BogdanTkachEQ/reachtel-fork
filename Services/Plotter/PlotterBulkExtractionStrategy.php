<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Plotter;

use InvalidArgumentException;
use Models\Plotter\PlotterBulkDataExtractionParams;
use Models\Plotter\PlotterExtract;
use RuntimeException;
use Services\Plotter\Interfaces\PlotterDataExtractionStrategyInterface;
use Services\Plotter\traits\PlotterDataExtractionHelper;
use Services\Utils\Plotter\PlotterFunctions;

/**
 * Class PlotterBulkExtractionStrategy
 */
class PlotterBulkExtractionStrategy implements PlotterDataExtractionStrategyInterface
{
    use PlotterDataExtractionHelper;

    /**
     * @param array $extractionParams
     * @return PlotterExtract
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function extractData(array $extractionParams)
    {
        try {
            $extractionParams = PlotterBulkDataExtractionParams::fromArray($extractionParams);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        $dbName = $this->getDbName();
        $tableName = $this->getTableName();

        list($filterSql, $filterParameters) = $this->buildDataExtractionSqlFilter($extractionParams);

        $subquery = 'SELECT * from `' . $tableName . '` WHERE 1' . $filterSql;

        // SQL to find random rows is adopted from the following thread in stackoverflow:
        // https://stackoverflow.com/questions/1244555/how-can-i-optimize-mysqls-order-by-rand-function

        $limit = $extractionParams->getNumRecords() ? : 'COUNT(*)';
        $datetime = new \DateTime();
        $uniqueid = $datetime->format('U');
        $sql = '
            SELECT `id`, `prospectid`, `suburb`, `postcode`, `phone1`, `phone2`, `mobile1`
            FROM (SELECT @cnt := COUNT(*) + 1, @lim := ' . $limit . ' FROM (' . $subquery . ') d1) vars
            STRAIGHT_JOIN (SELECT  r.*, @lim := @lim - 1 FROM (' . $subquery . ') r 
            WHERE (@cnt := @cnt - 1) AND RAND(' . $uniqueid . ') < @lim / @cnt) i 
        ';

        api_db_switch_connection(null, null, $dbName, DB_MYSQL_READ_HOST_FORCED);

        $rs = api_db_query_read($sql, array_merge($filterParameters, $filterParameters));

        // Reset the connection back.
        api_db_reset_connection();

        $extract = new PlotterExtract();

        if (!$rs) {
            api_error_raise('Invalid sql generated');
            return $extract;
        }

        if (!$rs->RecordCount()) {
            return $extract;
        }

        $row = PlotterFunctions::getPlotterHeaderArray(
            $extractionParams->getReturnMobiles()
        );

        $data = [$row];

        while ($value = $rs->FetchRow()) {
            $row = PlotterFunctions::getPlotterDataArray(
                $value,
                $extractionParams->getReturnMobiles()
            );

            array_push($data, $row);
        }

        return $extract->setExtractedData($data);
    }
}
