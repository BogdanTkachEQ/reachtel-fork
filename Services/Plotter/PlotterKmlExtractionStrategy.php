<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Plotter;

use Models\Plotter\PlotterExtract;
use RuntimeException;
use InvalidArgumentException;
use Services\Plotter\traits\PlotterDataExtractionHelper;
use Services\Utils\Plotter\PlotterFunctions;
use Services\Plotter\Interfaces\PlotterDataExtractionStrategyInterface;
use Models\Plotter\PlotterKmlDataExtractionParams;

/**
 * Class PlotterKmlExtractionStrategy
 */
class PlotterKmlExtractionStrategy implements PlotterDataExtractionStrategyInterface
{
    use PlotterDataExtractionHelper;

    /** @var KmlProcessor */
    private $kmlProcessor;

    /**
     * PlotterKmlExtractionStrategy Constructor
     */
    public function __construct()
    {
        $this->kmlProcessor = new KmlProcessor();
    }

    /**
     * @param array $extractionParams
     * @return PlotterExtract
     * @throws InvalidArgumentException
     */
    public function extractData(array $extractionParams)
    {
        try {
            $extractionParams = PlotterKmlDataExtractionParams::fromArray($extractionParams);
        } catch (\Exception $e) {
            throw new InvalidArgumentException($e->getMessage());
        }

        if (!$extractionParams->getKml()) {
            throw new RuntimeException('Can not process as kml is empty');
        }

        $processedKml = $this->kmlProcessor->process($extractionParams->getKml());
        $regions = $processedKml[KmlProcessor::PLACES_KEY];
        $invalidPolygons = $processedKml[KmlProcessor::HAS_INVALID_POLYGONS_KEY];

        $extract = new PlotterExtract();

        if ($invalidPolygons) {
            $extract->addWarnings('Invalid polygons received in the KML');
        }

        if (!$regions) {
            $extract->addWarnings('No regions found from the KML passed');
            return $extract;
        }

        return $extract->setExtractedData($this->fetchPlotterData($regions, $extractionParams));
    }

    /**
     * @param array                          $regions
     * @param PlotterKmlDataExtractionParams $extractionParams
     * @return array
     */
    private function fetchPlotterData(array $regions, PlotterKmlDataExtractionParams $extractionParams)
    {
        $data = [];
        $row = PlotterFunctions::getPlotterHeaderArray(
            $extractionParams->getReturnMobiles()
        );
        // Add KML export specific region field
        $row[] = 'region';
        array_push($data, $row);

        $dbName = $this->getDbName();

        list($filterSql, $filterParameters) = $this->buildDataExtractionSqlFilter($extractionParams);

        // TECHNICAL DEBT:
        // This is not a neat way of getting a read connection. We could do it with DB_SINGLE_CONNECTION set to false.
        // However, this might break our existing code which uses the api_db_query_read function based on the db set up.
        api_db_switch_connection(null, null, $dbName, DB_MYSQL_READ_HOST_FORCED);
        foreach ($regions as $region) {
            foreach ($region[KmlProcessor::PLACE_MARK_POLYGON_KEY] as $polygon) {
                $sql = "SELECT * FROM " .
                    $this->getTableName() .
                    " WHERE ST_CONTAINS(ST_GEOMFROMTEXT('POLYGON(" . $polygon . ")'), `point`)";

                $sql .= $filterSql;

                api_db_ping(null, null, $dbName, DB_MYSQL_READ_HOST_FORCED);
                $rs = api_db_query_read($sql, $filterParameters);

                if (!$rs) {
                    api_error_raise('Invalid sql generated');
                    //Set it back to default db so that any further sql will not fail
                    api_db_reset_connection();
                    return [];
                }

                while ($value = $rs->FetchRow()) {
                    if (!is_null($extractionParams->getPercent()) && rand(0, 100) > $extractionParams->getPercent()) {
                        continue;
                    }

                    $row = PlotterFunctions::getPlotterDataArray(
                        $value,
                        $extractionParams->getReturnMobiles()
                    );
                    $row[] = $region[KmlProcessor::PLACE_MARK_NAME_KEY];
                    array_push($data, $row);
                }
            }
        }

        //Set it back to default db so that any further sql will not fail
        api_db_reset_connection();

        return $data;
    }
}
