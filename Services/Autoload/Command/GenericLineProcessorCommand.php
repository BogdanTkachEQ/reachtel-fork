<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\Command;

use InvalidArgumentException;
use Models\Autoload\AutoloadDTO;
use Services\Autoload\AutoloadLogger;
use Services\Autoload\Exceptions\AutoloadLineProcessorCommandException;
use Services\Autoload\Interfaces\Command\AutoloadLineProcessorCommandInterface;
use Services\Autoload\LineExclusionEvaluator;

/**
 * Class GenericLineProcessorCommand
 */
class GenericLineProcessorCommand implements AutoloadLineProcessorCommandInterface
{
    /** @var AutoloadDTO */
    protected $autoloadDto;

    /** @var AutoloadLogger */
    protected $logger;

    /** @var \DateTimeZone */
    protected $timeZone;

    /** @var integer */
    protected $campaignId;

    /** @var LineExclusionEvaluator */
    protected $exclusionEvaluator;

    /**
     * GenericLineProcessorCommand constructor.
     * @param AutoloadDTO            $autoloadDTO
     * @param \DateTimeZone          $timeZone
     * @param LineExclusionEvaluator $exclusionEvaluator
     */
    public function __construct(
        AutoloadDTO $autoloadDTO,
        \DateTimeZone $timeZone,
        LineExclusionEvaluator $exclusionEvaluator
    ) {
        $this->autoloadDto = $autoloadDTO;
        $this->timeZone = $timeZone;
        $this->exclusionEvaluator = $exclusionEvaluator;
    }

    /**
     * @param array $line
     * @return boolean
     * @throws AutoloadLineProcessorCommandException
     * @throws \Exception
     */
    public function execute(array $line)
    {
        if (!$this->campaignId) {
            throw new AutoloadLineProcessorCommandException('Campaign id not set while processing line');
        }

        $destination = trim($line[$this->autoloadDto->getDestinationColumnName()]);
        if (!$destination) {
            foreach ($this->autoloadDto->getAlternativeDestinationColumnNames() as $destinationColumnName) {
                if (isset($line[$destinationColumnName]) && $line[$destinationColumnName]) {
                    $destination = $line[$destinationColumnName];
                    break;
                }
            }

            if (!$destination) {
                $this->addToLogs("Invalid destination!" . print_r($line, true));
                return false;
            }
        }

        $targetKey = $this->getTargetKeyFromLine($line);

        if ($this->handleLineExclusion($line, $destination, $targetKey)) {
            return true;
        }

        $nextAttempt = $this->getNextAttemptFromLine($line);

        $this->addToLogs(
            "Adding Target: 
                    Campaign: {$this->campaignId}
                    Dest: {$destination}
                    TargetKey: {$targetKey}
                    Next Attempt: " . ($nextAttempt ? : '')
        );

        return api_targets_add_single(
            $this->campaignId,
            $destination,
            $targetKey,
            null,
            $line,
            $nextAttempt ? : null
        );
    }

    /**
     * @param integer $campaignId
     * @return $this
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    /**
     * @param AutoloadLogger $logger
     * @return $this
     */
    public function setLogger(AutoloadLogger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param string $log
     * @return boolean
     */
    protected function addToLogs($log)
    {
        if ($this->logger) {
            $this->logger->addLog($log);
        }

        return true;
    }

    /**
     * @param array $line
     * @return null|string
     */
    protected function getNextAttemptFromLine(array $line)
    {
        if (!$this->autoloadDto->getCallDateColumnName()) {
            return null;
        }

        if (!isset($line[$this->autoloadDto->getCallDateColumnName()]) ||
            trim($line[$this->autoloadDto->getCallDateColumnName()]) === ''
        ) {
            throw new InvalidArgumentException(
                'Call date column not present invalid in line ' .
                print_r($line, true)
            );
        }

        try {
            return (
                new \DateTime(
                    $line[$this->autoloadDto->getCallDateColumnName()] . $this->autoloadDto->getNextAttemptTime(),
                    $this->timeZone
                )
            )
                ->format('d-m-Y H:i:s');
        } catch (\Exception $exception) {
            throw new InvalidArgumentException(
                'Invalid date time format received in call date column: ' .
                print_r($line, true)
            );
        }
    }

    /**
     * @param array $line
     * @return string | null
     */
    protected function getTargetKeyFromLine(array $line)
    {
        return ($this->autoloadDto->getTargetKeyColumnName() &&
            isset($line[$this->autoloadDto->getTargetKeyColumnName()])) ?
            $line[$this->autoloadDto->getTargetKeyColumnName()] :
            null;
    }

    /**
     * @param array  $line
     * @param string $destination
     * @param string $targetKey
     * @return boolean
     * @throws \Exception
     */
    protected function handleLineExclusion(array $line, $destination, $targetKey)
    {
        if ($this->exclusionEvaluator->evaluate($line)) {
            $targetId = api_targets_add_single(
                $this->campaignId,
                $destination,
                $targetKey,
                null,
                $line,
                null
            );
            if ($targetId) {
                api_targets_abandontarget($targetId, 'Excluded');
            }

            $this->addToLogs('Line met exclusion criteria and so excluded: ' . print_r($line, true));
            return true;
        }

        return false;
    }
}
