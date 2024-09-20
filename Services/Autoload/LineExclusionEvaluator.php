<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Doctrine\Common\Collections\ArrayCollection;
use Services\Autoload\Interfaces\LineExclusionRuleInterface;

/**
 * Class LineExclusionEvaluator
 */
class LineExclusionEvaluator
{
    /** @var ArrayCollection */
    private $rules;

    public function __construct()
    {
        $this->rules = new ArrayCollection();
    }

    /**
     * Returns true if line has to be excluded else false
     * @param array $line
     * @return boolean
     */
    public function evaluate(array $line)
    {
        /** @var LineExclusionRuleInterface $rule */
        foreach ($this->rules->toArray() as $rule) {
            if ($rule->shouldExclude($line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param LineExclusionRuleInterface $exclusionRule
     * @return $this
     */
    public function addRule(LineExclusionRuleInterface $exclusionRule)
    {
        $this->rules->add($exclusionRule);
        return $this;
    }
}
