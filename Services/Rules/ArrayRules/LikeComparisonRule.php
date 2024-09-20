<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

/**
 * Class LikeComparisonRule
 */
class LikeComparisonRule extends AbstractWildCardComparisonRule
{
    /**
     * @param $value
     * @return boolean
     */
    protected function checkValue($value)
    {
        if (!$this->valueWildCardComparison) {
            return ($this->value == $value);
        }

        return (substr($value, 0, strlen($this->value)) == $this->value);
    }
}
