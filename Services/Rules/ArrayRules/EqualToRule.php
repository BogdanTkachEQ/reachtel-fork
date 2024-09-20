<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

use Services\Exceptions\Rules\RulesException;

/**
 * Class EqualToRule
 */
class EqualToRule extends AbstractComparisonRule
{
    /**
     * @return boolean
     * @throws RulesException
     */
    protected function checkIfSatisfied()
    {
        if (!isset($this->field)) {
            throw new RulesException('Field to compare is not set');
        }

        if (!$this->value && !isset($this->data[$this->field])) {
            // If the field does not exist, treat that it is equal to false or null
            return true;
        }

        return (isset($this->data[$this->field]) && $this->data[$this->field] == $this->value);
    }
}
