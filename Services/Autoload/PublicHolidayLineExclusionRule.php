<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Interfaces\LineExclusionRuleInterface;
use Services\Utils\PublicHolidayChecker;

/**
 * Class PublicHolidayLineExclusionRule
 */
class PublicHolidayLineExclusionRule implements LineExclusionRuleInterface
{
    /**
     * @var PublicHolidayChecker
     */
    private $publicHolidayChecker;

    /** @var array */
    private $countryColumnNames;

    /** @var \DateTime */
    private $dateTime;

    /**
     * PublicHolidayLineExclusionRule constructor.
     * @param PublicHolidayChecker $publicHolidayChecker
     */
    public function __construct(
        PublicHolidayChecker $publicHolidayChecker
    ) {
        $this->publicHolidayChecker = $publicHolidayChecker;
    }

    /**
     * @param array $columnNames
     * @return $this
     */
    public function setCountryColumnNames(array $columnNames)
    {
        $this->countryColumnNames = $columnNames;
        return $this;
    }

    /**
     * @param \DateTime $dateTime
     * @return $this
     */
    public function setDateTime(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @param array $line
     * @return boolean
     */
    public function shouldExclude(array $line)
    {
        foreach ($this->countryColumnNames as $columnName) {
            if (!isset($line[$columnName])) {
                continue;
            }


            if ($this->publicHolidayChecker->isPublicHolidayByCountryShortCode($this->dateTime, $line[$columnName])) {
                return true;
            }
        }

        return false;
    }
}
