<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Plotter;

/**
 * Class PlotterExtract
 */
class PlotterExtract
{
    /**
     * @var array
     */
    private $extractedData;

    /**
     * @var array
     */
    private $warnings;

    /**
     * PlotterExtract constructor.
     */
    public function __construct()
    {
        $this->extractedData = [];
        $this->warnings = [];
    }

    /**
     * @return array
     */
    public function getExtractedData()
    {
        return $this->extractedData;
    }

    /**
     * @param array $extractedData
     * @return PlotterExtract
     */
    public function setExtractedData($extractedData)
    {
        $this->extractedData = $extractedData;
        return $this;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @param array $warnings
     * @return PlotterExtract
     */
    public function addWarnings($warnings)
    {
        $this->warnings[] = $warnings;
        return $this;
    }
}
