<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Exceptions\AutoloadFileProcessorException;
use Services\Autoload\Exceptions\AutoloadStrategyException;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;
use Services\Exceptions\File\CryptoException;
use Services\File\Interfaces\EncryptorInterface;
use Services\Reports\Interfaces\ArrayToFileConverterInterface;

/**
 * Class AbstractLineItemProcessorStrategy
 */
abstract class AbstractLineItemProcessorStrategy extends AbstractAutoloadStrategy
{
    /**
     * @var AutoloadFileProcessorInterface
     */
    private $fileProcessor;

    /** @var array */
    private $badRecords = [];

    /** @var integer */
    private $totalProcessed = 0;

    /**
     * AbstractLineItemProcessorStrategy constructor.
     * @param AutoloadFileProcessorInterface $fileProcessor
     */
    public function __construct(
        AutoloadFileProcessorInterface $fileProcessor
    ) {
        $this->fileProcessor = $fileProcessor;
    }

    /**
     * @param string $filePath
     * @return boolean
     * @throws AutoloadStrategyException
     */
    protected function process($filePath)
    {
        try {
            $data = $this->fileProcessor->convertFileToArray($filePath);
        } catch (AutoloadFileProcessorException $e) {
            $this->addToLogs($e->getMessage());
            return false;
        }

        if (!$data) {
            $this->addToLogs('Could not fetch data from file '. $filePath);
            return false;
        }

        $this->addToLogs('Starting to process file '. $filePath);

        $requiredColumns = $this->getRequiredColumns();

        foreach ($data as $line) {
            if (!$line || !array_filter(array_values($line))) {
                // empty line
                continue;
            }
            foreach ($requiredColumns as $column) {
                if (!isset($line[$column])) {
                    throw new AutoloadStrategyException(
                        sprintf('Missing column %s in file', $column)
                    );
                }
            }

            try {
                if (!$this->processLine($line)) {
                    $this->badRecords[] = $line;
                    continue;
                }
                $this->totalProcessed++;
            } catch (\Exception $e) {
                $this->addToLogs($e->getMessage());
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getBadRecords()
    {
        return $this->badRecords;
    }

    /**
     * @return integer
     */
    public function getTotalProcessedCount()
    {
        return $this->totalProcessed;
    }

    /**
     * @param ArrayToFileConverterInterface $arrayToFileConverter
     * @param string                        $filePath
     * @param EncryptorInterface|null       $encryptor
     * @return boolean
     */
    public function writeBadRecordsToFile(
        ArrayToFileConverterInterface $arrayToFileConverter,
        $filePath,
        EncryptorInterface $encryptor = null
    ) {
        $this->addToLogs('Writing bad records to file');
        if (!$this->getBadRecords()) {
            $this->addToLogs('No bad records found');
            throw new \RuntimeException('No bad records found');
        }

        $header = array_keys($this->getBadRecords()[0]);

        $records = array_map(function ($record) {
            return array_values($record);
        }, $this->getBadRecords());

        array_unshift($records, $header);
        if (!$arrayToFileConverter->convertArrayToFile($records, $filePath)) {
            return false;
        }

        if ($encryptor) {
            try {
                $encrypted = $encryptor->setFile($filePath)->encrypt();
            } catch (CryptoException $exception) {
                $this->addToLogs('Encryption error:' . $exception->getMessage());
                throw new \RuntimeException('Error while encrypting file.');
            }

            file_put_contents($filePath, $encrypted);
        }

        return $this->addToLogs('Bad records written to file');
    }

    /**
     * Mandatory headers on the csv
     * @return array
     */
    abstract protected function getRequiredColumns();

    /**
     * @param array $line
     * @return boolean
     * @throws \Exception
     */
    abstract protected function processLine(array $line);
}
