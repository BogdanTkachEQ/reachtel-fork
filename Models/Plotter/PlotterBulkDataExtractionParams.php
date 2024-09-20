<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Plotter;

use InvalidArgumentException;

/**
 * Class PlotterBulkDataExtractionParams
 */
class PlotterBulkDataExtractionParams extends PlotterDataExtractionParams
{
    const RECORDS_KEY = 'records';

    /** @var string */
    private $numRecords;

    /**
     * @param array $data
     * @return PlotterBulkDataExtractionParams
     */
    public static function fromArray(array $data)
    {
        $extractionParams = parent::fromArray($data);

        $extractionParams
            ->setNumRecords(static::$resolvedOptions[static::RECORDS_KEY]);

        return $extractionParams;
    }

    /**
     * @return array
     */
    protected static function getDefaultData() {
        return array_merge(parent::getDefaultData(), [
            static::RECORDS_KEY => null,
        ]);
    }

    /**
     * Number of records that need to be returned
     * @return string
     */
    public function getNumRecords()
    {
        return $this->numRecords;
    }

    /**
     * Number of records that need to be returned. This has to be a numeric value.
     * @param string $numRecords
     * @return PlotterBulkDataExtractionParams
     */
    public function setNumRecords($numRecords)
    {
        if (!is_numeric($numRecords)) {
            throw new InvalidArgumentException('Non-numeric value passed for record.');
        }
        $this->numRecords = $numRecords;
        return $this;
    }
}
