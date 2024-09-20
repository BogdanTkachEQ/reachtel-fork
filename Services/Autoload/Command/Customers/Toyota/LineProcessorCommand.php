<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\Command\Customers\Toyota;

use InvalidArgumentException;
use Services\Autoload\Command\GenericLineProcessorCommand;

/**
 * Class LineProcessorCommand
 */
class LineProcessorCommand extends GenericLineProcessorCommand
{
    /**
     * @var string
     */
    protected $nextAttempt;

    /**
     * @param array $line
     * @return null|string
     */
    protected function getNextAttemptFromLine(array $line)
    {
        try {
            return (
                new \DateTime(
                    $this->nextAttempt,
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
     * @param $nextAttempt
     * @return $this
     */
    public function setNextAttempt($nextAttempt)
    {
        $this->nextAttempt = $nextAttempt;
        return $this;
    }
}
