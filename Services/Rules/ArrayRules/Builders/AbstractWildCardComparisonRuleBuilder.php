<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\ArrayRules\Builders;

use Services\Rules\ArrayRules\AbstractWildCardComparisonRule;

/**
 * Class AbstractWildCardComparisonRuleBuilder
 */
abstract class AbstractWildCardComparisonRuleBuilder extends AbstractComparisonRuleBuilder
{
    const FIELD_WILDCARD = 'fieldwildcard';
    const VALUE_WILDCARD = 'valuewildcard';

    /**
     * @param array $data
     * @return AbstractWildCardComparisonRule
     */
    public function buildFromArray(array $data)
    {
        /** @var AbstractWildCardComparisonRule $rule */
        $rule =  parent::buildFromArray($data);

        return $rule
            ->doFieldWildCardComparison(isset($data[self::FIELD_WILDCARD]) && $data[self::FIELD_WILDCARD])
            ->doValueWildCardComparison(isset($data[self::VALUE_WILDCARD]) && $data[self::VALUE_WILDCARD]);
    }
}
