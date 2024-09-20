<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Plotter;

/**
 * Class PlotterFunctions
 */
class PlotterFunctions
{
    const FILE_DROP_LOCATION = SAVE_LOCATION . PLOTTER_EXPORT_LOCATION . '/';

    /**
     * This function is copied over from plotter.
     * @param array   $rsRow
     * @param boolean $returnMobiles
     * @return array
     */
    public static function getPlotterDataArray(array $rsRow, $returnMobiles = false)
    {
        $row = [
            $rsRow['id'],
            $rsRow['phone1'],
            $rsRow['phone2'],
        ];
    
        if ($returnMobiles) {
            $row[] = $rsRow['mobile1'];
        }
    
        $row[] = $rsRow['prospectid'];
        $row[] = $rsRow['suburb'];
        $row[] = $rsRow['postcode'];
    
        return $row;
    }

    /**
     * This function is copied over from plotter.
     * @param boolean $returnMobiles
     * @param array
     */
    public static function getPlotterHeaderArray($returnMobiles = false)
    {
        $row = [
            'targetkey',
            'phone1',
            'phone2'
        ];
    
        if ($returnMobiles) {
            $row[] = 'mobile1';
        }
    
        $row[] = 'ProspectId';
        $row[] = 'suburb';
        $row[] = 'postcode';
    
        return $row;
    }

    /**
     * @param string $remoteFilename
     * @param string $localFile
     * @return string
     */
    public static function plotterDataExportGetFile($remoteFilename, $localFile)
    {
        return copy(static::FILE_DROP_LOCATION . $remoteFilename, $localFile);
    }

    /**
     * @param string $remoteFilename
     * @return boolean
     */
    public static function plotterDataExportRemoveFile($remoteFilename)
    {
        if (!preg_match('/^\/?([A-z0-9-_+]+\/)*([A-z0-9-_]+(\.[A-z0-9]+)?)$/', $remoteFilename)) {
            return api_error_raise("Invalid remote file name received for remove function for plotter");
        }
        return @unlink(static::FILE_DROP_LOCATION . $remoteFilename);
    }

    /**
     * @param string $remoteFileName
     * @param string $localFile
     * @return string
     */
    public static function plotterDataExportPutFile($remoteFileName, $localFile)
    {
        return @copy($localFile, static::FILE_DROP_LOCATION . $remoteFileName);
    }
}
