<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

use Services\Exceptions\Rules\RulesException;

/**
 * Class AbstractWildCardComparisonRule
 */
abstract class AbstractWildCardComparisonRule extends AbstractComparisonRule
{
    /** @var boolean */
    protected $fieldWildCardComparison = false;

    /** @var boolean */
    protected $valueWildCardComparison = false;

    /**
     * @param $value
     * @return boolean
     */
    abstract protected function checkValue($value);

    /**
     * @param boolean $fieldWildCardComparison
     * @return $this
     */
    public function doFieldWildCardComparison($fieldWildCardComparison)
    {
        $this->fieldWildCardComparison = $fieldWildCardComparison;
        return $this;
    }

    /**
     * @param boolean $valueWildCardComparison
     * @return $this
     */
    public function doValueWildCardComparison($valueWildCardComparison)
    {
        $this->valueWildCardComparison = $valueWildCardComparison;
        return $this;
    }

    /**
     * @return boolean
     */
    public function shouldDoValueWildCardComparison()
    {
        return $this->valueWildCardComparison;
    }

    /**
     * @return boolean
     */
    public function shouldDoFieldWildCardComparison()
    {
        return $this->fieldWildCardComparison;
    }

    /**
     * @return boolean
     * @throws RulesException
     */
    protected function checkIfSatisfied()
    {
        if (is_null($this->value)) {
            return false;
        }

        if (is_null($this->field)) {
            throw new RulesException('Field to compare is not set');
        }

        if ($this->fieldWildCardComparison) {
            foreach ($this->data as $key => $value) {
                if (substr($key, 0, strlen($this->field)) !== $this->field) {
                    continue;
                }

                if ($this->checkValue($value)) {
                    return true;
                }
            }

            return false;
        }

        return isset($this->data[$this->field]) && $this->checkValue($this->data[$this->field]);
    }
}
