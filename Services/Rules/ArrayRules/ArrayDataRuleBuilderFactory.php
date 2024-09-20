<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules;

use Services\Exceptions\Rules\ArrayRuleBuilderFactoryException;
use Services\Rules\ArrayRules\Builders\EqualToRuleBuilder;
use Services\Rules\ArrayRules\Builders\LikeComparisonRuleBuilder;
use Services\Rules\ArrayRules\Builders\NotEqualToRuleBuilder;
use Services\Rules\ArrayRules\Builders\NotLikeComparisonRuleBuilder;
use Services\Rules\Interfaces\ArrayDataRuleBuilderInterface;

/**
 * Class ArrayDataRuleFactory
 */
class ArrayDataRuleBuilderFactory
{
    /**
     * @param RuleType $type
     * @return ArrayDataRuleBuilderInterface
     * @throws ArrayRuleBuilderFactoryException
     */
    public function create(RuleType $type)
    {
        switch ($type) {
            case RuleType::EQUALTO():
                return new EqualToRuleBuilder();

            case RuleType::NOTEQUALTO():
                return new NotEqualToRuleBuilder();

            case RuleType::LIKE():
                return new LikeComparisonRuleBuilder();

            case RuleType::NOTLIKE():
                return new NotLikeComparisonRuleBuilder();

            default:
                throw new ArrayRuleBuilderFactoryException('Invalid Rule type received');
        }
    }
}
