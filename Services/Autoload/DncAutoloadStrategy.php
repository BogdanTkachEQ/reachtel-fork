<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\ActivityLogger;
use Services\Autoload\Interfaces\AutoloadFileProcessorInterface;

/**
 * Class DncAutoloadStrategy
 */
class DncAutoloadStrategy extends AbstractLineItemProcessorStrategy
{
    /** @var boolean */
    private $isSubscription;

    /** @var string */
    private $region;

    /** @var string */
    private $type;

    /** @var integer */
    private $listId;

    /** @var integer */
    private $groupId;

    /** @var ActivityLogger */
    private $activityLogger;

    /**
     * DncAutoloadStrategy constructor.
     * @param AutoloadFileProcessorInterface $fileProcessor
     * @param ActivityLogger                 $activityLogger
     * @param string                         $type
     * @param integer                        $groupId
     * @param integer                        $listId
     * @param boolean                        $isSubscription
     * @param string                         $region
     */
    public function __construct(
        AutoloadFileProcessorInterface $fileProcessor,
        ActivityLogger $activityLogger,
        $type,
        $groupId,
        $listId,
        $isSubscription = true,
        $region = 'AU'
    ) {
        parent::__construct($fileProcessor);

        $this->activityLogger = $activityLogger;
        $this->isSubscription = $isSubscription;
        $this->region = $region;
        $this->type = $type;
        $this->groupId = $groupId;
        $this->listId = $listId;
    }

    /**
     * @return boolean
     */
    protected function preProcessHook()
    {
        if (!api_restrictions_donotcontact_is_valid_type($this->type)) {
            $this->logger->addLog(sprintf('Invalid type %s.', $this->type));
            return false;
        }

        if (!api_restrictions_donotcontact_list_belongs_to_group($this->groupId, $this->listId)) {
            $this
                ->logger
                ->addLog(
                    sprintf(
                        'DNC list id %d does not belong to group id %d and so the file can not be processed.',
                        $this->listId,
                        $this->groupId
                    )
                );
            return false;
        }

        return true;
    }

    /**
     * @return boolean
     */
    protected function postProcessHook()
    {
        $value = $this->getTotalProcessedCount() .
            ' destinations' .
            (!$this->isSubscription ? ' added to the ' : ' removed from the ') .
            'list';

        $this
            ->activityLogger
            ->addLog(
                'DONOTCONTACT',
                'AUTOLOAD',
                $value,
                $this->listId
            );

        return true;
    }

    /**
     * Mandatory headers on the csv
     * @return array
     */
    protected function getRequiredColumns()
    {
        return ['destination'];
    }

    /**
     * @param array $line
     * @return boolean
     * @throws \Exception
     */
    protected function processLine(array $line)
    {
        if ($this->isSubscription) {
            return api_restrictions_donotcontact_remove_single(
                $this->type,
                $line['destination'],
                $this->listId,
                $this->region
            );
        }
        return api_restrictions_donotcontact_add($this->type, $line['destination'], $this->listId, $this->region);
    }
}
