<?php
/**
 * @author kevin.ohayon@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

/**
 * Class CampaignUtils
 */
class CampaignUtils
{

    const TIMING_RECURRING_WEEKDAYS_BITWISE = 31;

    /**
     * Transform daysofweek bitwise to array of days.
     * 'daysofweek' in recurring time periods is an integer that represents active days in a full week.
     * eg: 6 => 0000110 => reversed 0110000 (ordered from monday to sunday) => so 6 means tue & wed active
     *
     * @param integer $int
     * @param boolean $keys
     * @return array
     */
    public static function getDaysOfWeekArray($int, $keys = false)
    {
        if (!self::isValidDaysOfWeek($int)) {
            throw new \Exception('Days of week bitwise integer is invalid');
        }

        // (reversed) week as binary
        $week = decbin($int);
        // full week (7 days)
        $week = str_pad($week, 7, '0', STR_PAD_LEFT);
        // ordered from monday to sunday
        $week = strrev($week);
        // week binary to array of boolean
        $week = array_map('boolval', str_split($week));

        if ($keys) {
            $week = array_combine(
                ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                $week
            );
        }

        return $week;
    }

    /**
     * Transform array of days to daysofweek bitwise.
     * 'daysofweek' in recurring time periods is an integer that represents active days in a full week.
     * eg:  6 => 0000110 => reversed 0110000 (ordered from monday to sunday) => so 6 means tue & wed active
     * So [false, true, true, false, false, false, false] => 0110000 => reversed 0000110 => 6
     *
     * @param array $daysOfWeekArray
     * @return integer
     */
    public static function getBitwiseDaysOfWeek(array $daysOfWeekArray)
    {
        // week array of boolean to binary
        $week = implode('', array_map('intval', $daysOfWeekArray));

        // order of binary is reversed sunday to monday
        $week = strrev($week);

        // as decimal
        return bindec($week);
    }

    /**
     * Check if days of week integer is valid
     *
     * @param integer|string $daysOfWeek
     * @return boolean
     */
    public static function isValidDaysOfWeek($daysOfWeek)
    {
        return is_numeric($daysOfWeek) && $daysOfWeek <= 127 && $daysOfWeek >= 0;
    }

    /**
     * Returns campaign name after replacing the place holder with date as per the format
     * mentioned in the placeholder. The date format should respect php datetime format
     * eg. campaign-{{j-F-Y}}-name will be converted to campaign-1-July-2019-name
     *
     * @param string $campaignName
     * @return string
     */
    public static function normalizeCampaignName($campaignName)
    {
        return preg_replace_callback(
            '/(.*)\{\{([\w\-\/\.]+)\}\}(.*)/',
            function ($matches) {
                return $matches[1] . (new \DateTime())->format($matches[2]) . $matches[3];
            },
            $campaignName
        );
    }
}
