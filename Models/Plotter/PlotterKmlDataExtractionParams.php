<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Plotter;

/**
 * Class PlotterKmlDataExtractionParams
 */
class PlotterKmlDataExtractionParams extends PlotterDataExtractionParams {

    const KML_KEY = 'kml';
    const PERCENT_KEY = 'percent';

    /** @var \SimpleXMLElement */
    private $kml;

    /** @var integer */
    private $percent;

    /**
     * @param array $data
     * @return PlotterKmlDataExtractionParams
     */
    public static function fromArray(array $data) {
        $extractionParams = parent::fromArray($data);

        $extractionParams
            ->setKml(static::$resolvedOptions[static::KML_KEY])
            ->setPercent(static::$resolvedOptions[static::PERCENT_KEY]);

        return $extractionParams;
    }

    /**
     * @return array
     */
    protected static function getDefaultData() {
        return array_merge(parent::getDefaultData(), [
            static::KML_KEY => null,
            static::PERCENT_KEY => null
        ]);
    }

    /**
     * @param string $kml
     * @return PlotterKmlDataExtractionParams
     */
    public function setKml($kml) {
        $this->kml = new \SimpleXMLElement($kml);
        return $this;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getKml() {
        return $this->kml;
    }

    /**
     * @param mixed $percent
     * @return PlotterKmlDataExtractionParams
     */
    public function setPercent($percent) {
        if (is_numeric($percent) && ($percent > 0) && ($percent < 100)) {
            $this->percent = $percent;
        }
        return $this;
    }

    /**
     * @return integer
     */
    public function getPercent() {
        return $this->percent;
    }
}
