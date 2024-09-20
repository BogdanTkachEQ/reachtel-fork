<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Plotter\traits;

use Models\Plotter\PlotterDataExtractionParams;
use Services\ConfigReader;

/**
 * Trait PlotterDataExtractionHelper
 */
trait PlotterDataExtractionHelper
{
    /**
     * @return string
     */
    protected function getDbName()
    {
        return ConfigReader::getInstance()
            ->getConfig(ConfigReader::BSV_IMPORTER_CONFIG_TYPE)['databaseName'];
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        return ConfigReader::getInstance()
            ->getConfig(ConfigReader::BSV_IMPORTER_CONFIG_TYPE)['sourceTableName'];
    }

    /**
     * @param PlotterDataExtractionParams $extractionParams
     * @return array
     */
    protected function buildDataExtractionSqlFilter(PlotterDataExtractionParams $extractionParams)
    {
        $postCodeSql = '';
        $postCodeParams = [];
        $excludePostCodeSql = '';
        $excludePostCodeParams = [];
        $phoneSql = '';
        $phoneParams = [];
        $agesToReturnSql = '';

        if ($extractionParams->getPostCodes()) {
            $postCodeSql .= " AND (0 ";
            foreach ($extractionParams->getPostcodes() as $postcode) {
                $postCodeSql .= ' OR `postcode` LIKE ?';
                $postCodeParams[] = $postcode . "%";
            }
            $postCodeSql .= ')';
        }

        if ($extractionParams->getExcludePostCodes()) {
            $excludePostCodeSql .= ' AND (1 ';
            foreach ($extractionParams->getExcludePostCodes() as $postCode) {
                $excludePostCodeSql .= ' AND `postcode` NOT LIKE ?';
                $excludePostCodeParams[] = $postCode . "%";
            }

            $excludePostCodeSql .= ')';
        }

        if ($extractionParams->getPhone()) {
            $phoneSql .= " AND ((`phone1` LIKE ?) OR (`phone2` LIKE ?) OR (`mobile1` LIKE ?))";
            $phoneSqlString = $extractionParams->getPhone() . '%';
            for ($i = 0; $i < 3; $i++) {
                $phoneParams[] = $phoneSqlString;
            }
        }

        if ($extractionParams->getAgesToReturn()) {
            $agesToReturnSql .= ' AND `agebracket` IN (' .
                implode(',', array_fill('0', count($extractionParams->getAgesToReturn()), '?')) .
                ')';
        }

        return [
            $postCodeSql . $excludePostCodeSql . $phoneSql . $agesToReturnSql,
            array_merge(
                $postCodeParams,
                $excludePostCodeParams,
                $phoneParams,
                $extractionParams->getAgesToReturn()
            )
        ];
    }
}
