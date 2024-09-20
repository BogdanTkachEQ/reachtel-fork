<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Plotter\Interfaces;

use Models\Plotter\PlotterExtract;
use RuntimeException;
use InvalidArgumentException;

/**
 * Interface PlotterDataExtractionStrategyInterface
 */
interface PlotterDataExtractionStrategyInterface
{

    /**
     * @param array $extractionParams
     * @return PlotterExtract
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function extractData(array $extractionParams);
}
