<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules\Builders;

use Services\Exceptions\Rules\RuleBuilderInvalidArgumentException;
use Services\Rules\ArrayRules\AbstractComparisonRule;
use Services\Rules\Interfaces\ArrayDataRuleBuilderInterface;

/**
 * Class AbstractComparisonRuleBuilder
 */
abstract class AbstractComparisonRuleBuilder implements ArrayDataRuleBuilderInterface
{
    const FIELD = 'field';
    const VALUE = 'value';

    /**
     * @return AbstractComparisonRule
     */
    abstract protected function getRule();

    /**
     * @param array $data
     * @return AbstractComparisonRule
     */
    public function buildFromArray(array $data)
    {
        $rule = $this->getRule();

        if (!(isset($data[static::FIELD]) && $data[static::FIELD]) || !isset($data[static::VALUE])) {
            throw new RuleBuilderInvalidArgumentException('Data does not contain required field');
        }

        return $rule
            ->setField($data[static::FIELD])
            ->setValue($data[static::VALUE]);
    }
}
