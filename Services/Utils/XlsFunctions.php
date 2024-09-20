<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

use PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Cell;
use PHPExcel_Cell_AdvancedValueBinder;
use PHPExcel_IOFactory;
use PHPExcel_Settings;
use PHPExcel_Style_NumberFormat;

/**
 * Class XlsFunctions
 */
class XlsFunctions
{
    /**
     * @param string $file
     * @return array
     * @throws \Exception
     * @throws \PHPExcel_Exception
     */
    public static function xlsToArray($file)
    {
        try {
            PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

            $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip;
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod);

            $excelReader = PHPExcel_IOFactory::createReaderForFile($file);

            if (!method_exists($excelReader, 'setReadDataOnly')) {
                throw new \Exception("Sorry, that Excel spreadsheet seems to be incompatible.");
            }

            $excelReader->setReadDataOnly(false);

            $excelFile = $excelReader->load($file);
            $excelWorkSheet = $excelFile->getActiveSheet();

            if ($excelWorkSheet == false) {
                throw new \Exception("Sorry, that Excel spreadsheet seems to be incompatible or password protected.");
            }

            $highest = [
                'column' => $excelWorkSheet->getHighestDataColumn(),
                'row' => $excelWorkSheet->getHighestDataRow()
            ];

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highest['column']);
        } catch (\Exception $e) {
            throw new \Exception("Sorry, that Excel spreadsheet seems to be incompatible.");
        }

        $exceldata = [];
        $invalidColumns = [];
        for ($row = 0; $row < $highest['row']; $row++) {
            for ($col = 0; $col <= $highestColumnIndex; $col++) {
                $cell = $excelWorkSheet->getCellByColumnAndRow($col, $row+1);
                $style = $excelWorkSheet->getStyle($cell->getCoordinate())->getNumberFormat()->getFormatCode();

                // PHPExcel defaults to US dates so change this to Australian otherwise treat everything else as text
                if ($style == "mm-dd-yy") {
                    $style = "dd-mm-yy";
                } elseif ($style == "mm/dd/yy") {
                    $style = "dd/mm/yyyy";
                } elseif ($style == "dd/mm/yy") {
                    $style = "dd/mm/yy";
                } elseif ($style == "dd/mm/yyyy") {
                    $style = "dd/mm/yyyy";
                } elseif ($style == "d/mm/yyyy") {
                    $style = "dd/mm/yyyy";
                } elseif ($style == "d/mm/yyyy;@") {
                    $style = "dd/mm/yyyy";
                } elseif ($style == "dd/mm/yyyy;@") {
                    $style = "dd/mm/yyyy";
                } elseif ($style == "d-mmm") {
                    $style = "d-mmm";
                } else {
                    $style = "General";
                }

                try {
                    $formattedValue = PHPExcel_Style_NumberFormat::toFormattedString(
                        $cell->getCalculatedValue(),
                        $style
                    );
                } catch (\Exception $e) {
                    throw new \Exception(
                        "Sorry, that Excel spreadsheet seems to be incompatible. Please check '" .
                        $e->getMessage() .
                        "'"
                    );
                }

                if (preg_match("/[0-9]+\.[0-9]+E\+[0-9]+$/", $formattedValue)) {
                    $formattedValue = (string) intval($formattedValue);
                }

                if ($row === 0 && is_null($formattedValue)) {
                    $invalidColumns[] = $col;
                }

                if (!in_array($col, $invalidColumns)) {
                    $exceldata[$row][$col] = $formattedValue;
                }
            }
        }

        if (!$exceldata) {
            return [];
        }

        $header = array_shift($exceldata);

        $data = [];
        foreach ($exceldata as $line) {
            $data[] = array_combine($header, $line);
        }

        return $data;
    }

    /**
     * @param array  $data
     * @param string $file
     * @return mixed
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public static function arrayToXls(array $data, $file)
    {
        PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_DefaultValueBinder());
        $doc = new \PHPExcel();
        $doc->setActiveSheetIndex(0)->fromArray($data);

        return \PHPExcel_IOFactory::createWriter($doc, 'Excel2007')->save($file);
    }
}
