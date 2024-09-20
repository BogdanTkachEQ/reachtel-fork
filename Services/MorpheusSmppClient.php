<?php
/**
 * @codingStandardsIgnoreStart
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services;

use SMPP;
use SmppAddress;

/**
 * Class MorpheusSmppClient
 * This is temporary file used to monitor the sms getting sent from the library
 */
class MorpheusSmppClient extends \SmppClient
{
    /**
     * @param SmppAddress $source
     * @param SmppAddress $destination
     * @param string      $short_message
     * @param mixed       $tags
     * @param integer     $dataCoding
     * @param integer     $priority
     * @param string      $scheduleDeliveryTime
     * @param string      $validityPeriod
     * @param string      $esmClass
     * @return string
     */
    protected function submit_sm(SmppAddress $source, SmppAddress $destination, $short_message = null, $tags = null, $dataCoding = SMPP::DATA_CODING_DEFAULT, $priority = 0x00, $scheduleDeliveryTime = null, $validityPeriod = null, $esmClass = null)
    {
        $submit_sm_return = parent::submit_sm($source, $destination, $short_message, $tags, $dataCoding, $priority, $scheduleDeliveryTime, $validityPeriod, $esmClass);

        if (defined('SMPP_LOGGING_ON') && SMPP_LOGGING_ON) {
            $this->log_sms($source->value, $destination->value, $short_message);
        }

        return $submit_sm_return;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $short_message
     * @return boolean
     */
    private function log_sms($from, $to, $short_message)
    {
        $substr = substr($short_message, 0, 6);

        // Check if binary
        if (preg_match('~[^\x20-\x7E\t\r\n]~', $substr)) {
            $binary = @unpack('c6', $substr);
            $ref = isset($binary[4]) ? $binary[4] : null;
        } else {
            $ref = null;
        }

        $success = api_db_query_write(
            'INSERT INTO smpp_logs (`from`, `to`, `message`, `ref`) VALUES (?, ?, ?, ?)',
            [$from, $to, $short_message, $ref]
        );

        if (!$success) {
            api_error_raise('SMPP logging failed');
        }

        return true;
    }
}
