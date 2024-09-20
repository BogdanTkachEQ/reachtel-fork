<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules;

use Services\Exceptions\Rules\RulesException;
use Services\Rules\Interfaces\RulesEngineInterface;
use Services\Rules\Interfaces\RulesInterface;

/**
 * Class RulesEngine
 */
class RulesEngine implements RulesEngineInterface
{
    /**
     * @var RulesInterface[]
     */
    private $rules = [];

    /**
     * @param RulesInterface $rule
     * @return $this
     */
    public function addRule(RulesInterface $rule)
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * @return RulesInterface[]
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @return boolean
     * @throws RulesException
     */
    public function runRules()
    {
        foreach ($this->rules as $rule) {
            if (!$rule->isSatisfied()) {
                return false;
            }
        }

        return true;
    }
}
