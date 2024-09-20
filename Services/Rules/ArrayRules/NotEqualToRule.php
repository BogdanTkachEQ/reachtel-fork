<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

use Services\Exceptions\Rules\RulesException;

/**
 * Class NotEqualToRule
 */
class NotEqualToRule extends AbstractComparisonRule
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

        return (isset($this->data[$this->field]) && $this->data[$this->field] != $this->value);
    }
}
