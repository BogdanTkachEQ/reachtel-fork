<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

/**
 * Class StringFunctions
 */
class StringFunctions
{

    /**
     * @param string $email
     * @return boolean
     */
    public static function validateEmail($email)
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (!filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * @param string    $string
     * @param \DateTime $dateTime
     * @return string
     */
    public static function parseDateTime($string, \DateTime $dateTime)
    {
        if (!preg_match('/\[([ymd\-\/]*)\]/i', $string, $matches)) {
            return $string;
        }

        $match = $matches[1];

        switch ($match) {
            case 'YYYYMMDD':
                $format = 'Ymd';
                break;

            case 'YYYY-MM-DD':
                $format = 'Y-m-d';
                break;

            case 'YYYY/MM/DD':
                $format = 'Y/m/d';
                break;

            default:
                return $string;
                break;
        }

        $date = $dateTime->format($format);
        return str_replace('[' . $match . ']', $date, $string);
    }
}
