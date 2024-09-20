<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\Toyota\Autoload;

/**
 * Class SmsAutoloadStrategy
 */
class SmsAutoloadStrategy extends AutoloadStrategy
{
    /**
     * @var integer
     */
    private $minArrear;

    /**
     * @var integer
     */
    private $maxArrear;

    /**
     * @param integer $arrear
     * @return $this
     */
    public function setMinArrear($arrear)
    {
        $this->minArrear = $arrear;
        return $this;
    }

    /**
     * @param integer $arrear
     * @return $this
     */
    public function setMaxArrear($arrear)
    {
        $this->maxArrear = $arrear;
        return $this;
    }

    /**
     * @param $line
     * @return boolean
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function processLine(array $line)
    {
        if (!$this->isValidArrear($line[static::ARREAR_DAYS_COLUMN_NAME])) {
            return true;
        }

        return parent::processLine($line);
    }

    /**
     * @param integer $arrear
     * @return bool
     */
    private function isValidArrear($arrear)
    {
        return ((is_null($this->minArrear) || $arrear >= $this->minArrear) &&
            (is_null($this->maxArrear) || $arrear < $this->maxArrear)
        );
    }

    /**
     * @return array
     */
    protected function getRequiredColumns()
    {
        return array_merge(parent::getRequiredColumns(), [static::ARREAR_DAYS_COLUMN_NAME]);
    }
}
