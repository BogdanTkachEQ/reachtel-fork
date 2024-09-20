<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Exceptions\Rules\RulesException;
use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\Interfaces\RulesEngineInterface;
use Services\Rules\RulesEngine;

/**
 * Class ArrayRulesEngineDecorator
 */
class ArrayRulesEngineDecorator implements RulesEngineInterface
{
    /** @var RulesEngine */
    private $rulesEngine;

    /**
     * ArrayRulesEngineDecorator constructor.
     * @param RulesEngine $engine
     */
    public function __construct(RulesEngine $engine)
    {
        $this->rulesEngine = $engine;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        /** @var AbstractArrayDataRule $rule */
        foreach ($this->rulesEngine->getRules() as $rule) {
            $rule->setData($data);
        }

        return $this;
    }

    /**
     * @param AbstractArrayDataRule $arrayDataRule
     * @return $this
     */
    public function addArrayDataRules(AbstractArrayDataRule $arrayDataRule)
    {
        $this->rulesEngine->addRule($arrayDataRule);
        return $this;
    }

    /**
     * @return boolean
     * @throws RulesException
     */
    public function runRules()
    {
        return $this->rulesEngine->runRules();
    }
}
