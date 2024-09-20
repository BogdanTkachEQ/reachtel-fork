<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Adapters;

use Services\Rules\ArrayRules\Builders\AbstractWildCardComparisonRuleBuilder;
use Services\Rules\Interfaces\ArrayDataRuleBuilderInterface;

/**
 * Class ArrayRuleBuilderAdapterFactory
 */
class ArrayRuleBuilderAdapterFactory
{
    /**
     * @param ArrayDataRuleBuilderInterface $builder
     * @return ArrayDataRuleBuilderInterface
     */
    public function create(ArrayDataRuleBuilderInterface $builder)
    {
        if ($builder instanceof AbstractWildCardComparisonRuleBuilder) {
            return new WildCardComparisonRuleBuilderAdapter($builder);
        }

        return $builder;
    }
}
