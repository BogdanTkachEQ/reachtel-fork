<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

use Services\Exceptions\Rules\RulesException;
use Services\Rules\Interfaces\RulesInterface;

/**
 * Class AbstractArrayDataRule
 */
abstract class AbstractArrayDataRule implements RulesInterface
{
    /** @var array */
    protected $data;

    /**
     * @return boolean
     * @throws RulesException
     */
    abstract protected function checkIfSatisfied();

    /**
     * @return boolean
     * @throws RulesException
     */
    public function isSatisfied()
    {
        if (is_null($this->data)) {
            throw new RulesException('Data not set');
        }

        return $this->checkIfSatisfied();
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
