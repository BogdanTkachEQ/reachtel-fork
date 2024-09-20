<?php
/**
 * BSV Importer for Plotter
 *
 * @author christopher.colborne@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Cron;

use \RuntimeException;
use \Services\ConfigReader;

/**
 * BSV Importer
 */
class BsvImporter
{
    /** @var resource */
    private $dataFileHandle;

    /** @var resource */
    private $errorFileHandle;

    /** @var string */
    private $dataExtractFilePath;

    /** @var string */
    private $tmpErrorFilePath;

    /** @var resource */
    private $sqlErrorFileHandle;

    /** @var string */
    private $tmpSqlErrorFilePath;

    /** @var array */
    private $dataFileHeaderRowArray;

    /** @var int */
    private $insertedCount = 0;

    /** @var int */
    private $processedCount = 0;

    /** @var int */
    private $errorCount = 0;

    /** @var int */
    private $sqlErrorCount = 0;

    /** @var boolean */
    private $hasErrors = false;

    /** @var boolean */
    private $isSetup = false;

    /** @var integer */
    const CSV_PROSPECT_ID_INDEX = 1;

    /** @var integer */
    const SQL_PROSPECT_ID_INDEX = 0;

    /**
     * @param string $dataExtractFilePath Full file path to the CSV extract passed to fopen().
     */
    public function __construct($dataExtractFilePath)
    {
        $this->dataExtractFilePath = $dataExtractFilePath;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        // cleanup and close open file handles, delete error files
        $this->cleanup();
    }

    /**
     * Actually process the file
     *
     *
     * @throws RuntimeException First row can't be read.
     * @return self
     */
    public function process()
    {
        try {
            $this->setup();

            $count = 0;
            $fields = [];
            $values = [];
            $placeholdersArray = [];

            while (($record = fgetcsv($this->dataFileHandle, $this->config['maxCsvLineLength'], ',', "\0")) !== false) {
                $this->processedCount++;

                if (count($record) > count($this->dataFileHeaderRowArray)) {
                    $this->hasErrors = true;
                    $this->errorCount++;
                    echo "Error count: $this->errorCount\n";
                    fputcsv($this->errorFileHandle, [$record[self::CSV_PROSPECT_ID_INDEX]]);
                    continue;
                }

                // parse a line into a clean csv ready for insert
                $row = $this->processRow($record);

                // skip rows without lat long
                if (!$row['lat'] || !$row['long']) {
                    continue;
                }

                // if we don't have any column fields for the insert, set it
                if (empty($fields)) {
                    $fields = implode('`, `', array_keys($row));
                    // add the point field manually as it doesn't fit in the config map
                    $fields .= '`, `point';
                }

                // maintain an array of sets of value placeholders, since we're inserting multiple rows
                // add the point string manually as it uses the Point function
                $pointString = 'NULL';
                if ($row['lat'] && $row['long']) {
                    $pointString = 'Point(' . $row['long'] . ',' . $row['lat'] . ')';
                }

                $placeholdersArray[] = '(' . implode(', ', array_fill(0, count($row), '?')) . ', ' . $pointString . ')';
                $values = array_merge($values, array_values($row));

                $count++;
                // if we're not at multiple of x - continue
                if ($count % $this->config['batchSize'] !== 0) {
                    continue;
                }

                // actually do the insert
                $this->insertedCount = $this->doInsert($fields, $placeholdersArray, $values, $row);

                // reset arrays after each insert so we know whether there are
                // any values left uninserted for final batch
                $placeholdersArray = [];
                $values = [];
            }

            // if there were any leftover from the final batch
            if (count($values) > 0) {
                $this->insertedCount = $this->doInsert($fields, $placeholdersArray, $values, $row);
            }

            echo sprintf(
                "Successfully inserted %d rows with %d errors and %d SQL errors\n\n",
                $this->insertedCount,
                $this->errorCount,
                $this->sqlErrorCount
            );

            // reset database connection in case of dropout
            api_db_reset_connection();
            $this->setupDatabase(false);

            return $this;
        } catch (RuntimeException $e) {
            $this->cleanup();
            throw $e;
        }
    }

    /**
     * Replace the live table with the newly imported table
     *
     * @return void
     *
     * @throws RuntimeException Not enough rows in new imported table.
     */
    public function replaceTable()
    {
        try {
            $this->setup(false);

            $backupTableName = $this->config['sourceTableName'] . '_BACKUP';

            echo sprintf(
                "Replacing live table `%s` with imported table `%s`.\n",
                $this->config['sourceTableName'],
                $this->config['tableName']
            );

            // sanity check new table
            $result = api_db_query_read("SELECT count(*) as count FROM {$this->config['tableName']}");
            $rows = $result->GetArray();
            $rowCount = $rows[0]['count'];
            if ($rowCount < $this->config['sanityMinimumRows']) {
                throw new RuntimeException("Insufficient rows ($rowCount) in new table `{$this->config['tableName']}`");
            }

            echo "Renaming live table `{$this->config['sourceTableName']}` to `$backupTableName`.\n";
            $result = api_db_query_write("DROP TABLE IF EXISTS $backupTableName");
            if (!$result) {
                throw new RuntimeException("Unable to drop backup table `$backupTableName`");
            }

            $result = api_db_query_write("ALTER TABLE {$this->config['sourceTableName']} RENAME $backupTableName");
            if (!$result) {
                throw new RuntimeException(
                    sprintf(
                        "Unable to rename live table `%s` to backup table `%s`",
                        $this->config['sourceTableName'],
                        $backupTableName
                    )
                );
            }

            echo "Renaming imported table `{$this->config['tableName']}` to `{$this->config['sourceTableName']}`.\n";
            $result = api_db_query_write(
                "ALTER TABLE {$this->config['tableName']} RENAME {$this->config['sourceTableName']}"
            );
            if (!$result) {
                throw new RuntimeException(
                    sprintf(
                        "Unable to rename new table `%s` to live table `%s`",
                        $this->config['tableName'],
                        $this->config['sourceTableName']
                    )
                );
            }

            echo "Done! New table is live!\n";
        } catch (RuntimeException $e) {
            $this->cleanup();
            throw $e;
        }
    }

    /**
     * Do we have errors to email
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return $this->hasErrors;
    }

    /**
     * Get error file path
     *
     * @return string
     */
    public function getErrorFile()
    {
        return $this->tmpErrorFilePath;
    }

    /**
     * Get sql error file path
     *
     * @return string
     */
    public function getSqlErrorFile()
    {
        return $this->tmpSqlErrorFilePath;
    }

    /**
     * Get summary of import
     *
     * @return string
     */
    public function getSummary()
    {
        $errorPercentage =
            $this->processedCount > 0 ?
            number_format($this->errorCount / $this->processedCount * 100, 4) :
            0;
        $sqlErrorPercentage =
            $this->processedCount > 0 ?
            number_format($this->sqlErrorCount / $this->processedCount * 100, 4) :
            0;
        return <<<EOF
Rows processed: {$this->processedCount}
Successful Rows: {$this->insertedCount}
Malformed Rows: {$this->errorCount} ($errorPercentage%)
SQL Error Rows: {$this->sqlErrorCount} ($sqlErrorPercentage%)
EOF;
    }

    /**
     * Do the insert
     *
     * @param string $fields
     * @param array  $placeholdersArray
     * @param array  $values
     *
     * @return integer
     */
    private function doInsert($fields, array $placeholdersArray, array $values)
    {
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES %s',
            $this->config['tableName'],
            $fields,
            implode(', ', $placeholdersArray)
        );

        $result = api_db_query_write($sql, $values);
        if ($result === false) {
            $this->sqlErrorCount++;
            $this->hasErrors = true;
            echo "SQL Error count: $this->sqlErrorCount\n";
            $errorMsg = api_db_last_error_write();
            echo $errorMsg . "\n";
            $errorCsv[] = $values[self::SQL_PROSPECT_ID_INDEX]; // First ProspectId of batch
            $errorCsv[] = $errorMsg;
            fputcsv($this->sqlErrorFileHandle, $errorCsv);
            return $this->insertedCount;
        }

        $this->insertedCount += api_db_affectedrows();

        if ($this->insertedCount % $this->config['insertLogIntervalRows'] == 0) {
            echo sprintf("Inserted %d rows...\n", $this->insertedCount);
        }

        return $this->insertedCount;
    }

    /**
     * Parse a CSV line into a clean array of what we need
     *
     * @param array $record
     *
     * @return array
     */
    private function processRow(array $record)
    {
        $row = [];
        foreach ($record as $i => $field) {
            if (! isset($this->config['columnMap'][$this->dataFileHeaderRowArray[$i]])
                || is_null($this->config['columnMap'][$this->dataFileHeaderRowArray[$i]])) {
                continue;
            }

            $value = trim($field);
            $row[$this->config['columnMap'][$this->dataFileHeaderRowArray[$i]]] = ($value !== '' ? $value : null);
        }

        return $row;
    }

    /**
     * Set up class ready for import
     *
     * @param boolean $clearDb
     *
     * @return void
     */
    private function setup($clearDb = true)
    {
        if (!$this->isSetup) {
            $this->config = $this->getConfig();
            $this->setupFiles($this->dataExtractFilePath);
            $this->setupDatabase($clearDb);

            $this->isSetup = true;
        }
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return ConfigReader::getInstance()->getConfig(ConfigReader::BSV_IMPORTER_CONFIG_TYPE);
    }

    /**
     * Setup file handles for reading and error recording
     *
     * @param string $dataExtractFilePath
     *
     * @return void
     *
     * @throws RuntimeException File open failures.
     */
    private function setupFiles($dataExtractFilePath)
    {
        $this->tmpErrorFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('bsv_errors');
        $this->tmpSqlErrorFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('bsv_sql_errors');

        if (!is_readable($dataExtractFilePath)) {
            throw new RuntimeException('File not readable: ' . $dataExtractFilePath);
        }

        // Add zip protocol if we've got a zip file
        $filename = basename($dataExtractFilePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === 'zip') {
            $textFilename = str_replace('zip', 'txt', $filename);
            $dataExtractFilePath = 'zip://' . $dataExtractFilePath . '#' . $textFilename;
        }

        $this->dataFileHandle = fopen($dataExtractFilePath, 'r');
        if ($this->dataFileHandle === false) {
            throw new RuntimeException('Unable to open file for reading: ' . $dataExtractFilePath);
        }

        $this->errorFileHandle = fopen($this->tmpErrorFilePath, 'w');
        if ($this->errorFileHandle === false) {
            throw new RuntimeException('Unable to open file for writing: ' . $this->tmpErrorFilePath);
        }

        $this->sqlErrorFileHandle = fopen($this->tmpSqlErrorFilePath, 'w');
        if ($this->sqlErrorFileHandle === false) {
            throw new RuntimeException('Unable to open file for writing: ' . $this->tmpSqlErrorFilePath);
        }

        $this->dataFileHeaderRowArray = fgetcsv($this->dataFileHandle, $this->config['maxCsvLineLength']);
        if (!$this->dataFileHeaderRowArray) {
            throw new RuntimeException('Unable to read first row from data file');
        }

        fputcsv(
            $this->errorFileHandle,
            [$this->dataFileHeaderRowArray[self::CSV_PROSPECT_ID_INDEX]]
        );
        fputcsv(
            $this->sqlErrorFileHandle,
            ['First ' . $this->dataFileHeaderRowArray[self::CSV_PROSPECT_ID_INDEX] . ' of batch', 'message']
        );
    }

    /**
     * Prepare the database for import
     *
     * @param boolean $clearDb
     *
     * @return void
     */
    private function setupDatabase($clearDb = true)
    {
        api_db_switch_connection(
            DB_MYSQL_PLOTTER_WRITE_USERNAME,
            DB_MYSQL_PLOTTER_WRITE_PASSWORD,
            DB_MYSQL_PLOTTER_DATABASE
        );
        api_db_query_write(sprintf("USE `%s`;", $this->config['databaseName']));

        if ($clearDb) {
            api_db_query_write("DROP TABLE IF EXISTS {$this->config['tableName']}");
            api_db_query_write("CREATE TABLE {$this->config['tableName']} LIKE {$this->config['sourceTableName']}");
        }
    }

    /**
     * Clean up and close open file handles, delete error files
     *
     * @return void
     */
    private function cleanup()
    {
        // close files
        if (is_resource($this->dataFileHandle)) {
            fclose($this->dataFileHandle);
        }

        if (is_resource($this->errorFileHandle)) {
            fclose($this->errorFileHandle);
        }

        if (is_resource($this->sqlErrorFileHandle)) {
            fclose($this->sqlErrorFileHandle);
        }

        // remove error files
        if (file_exists($this->tmpErrorFilePath)) {
            unlink($this->tmpErrorFilePath);
        }

        if (file_exists($this->tmpSqlErrorFilePath)) {
            unlink($this->tmpSqlErrorFilePath);
        }
    }
}
