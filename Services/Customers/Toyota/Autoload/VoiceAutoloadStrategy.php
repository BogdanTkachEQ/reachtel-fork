<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Autoload;

/**
 * Class VoiceAutoloadStrategy
 */
class VoiceAutoloadStrategy extends AutoloadStrategy
{
    /**
     * @param array $line
     * @return false|int
     */
    protected function getNextAttemptFromLine(array $line)
    {
        if (!isset($line[static::STATE_COLUMN_NAME]) || $line[static::STATE_COLUMN_NAME] !== 'WA') {
            return parent::getNextAttemptFromLine($line);
        }

        return '13:00';
    }
}
