<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Adapters;

use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\ArrayRules\Builders\AbstractWildCardComparisonRuleBuilder;
use Services\Rules\Interfaces\ArrayDataRuleBuilderInterface;

/**
 * Class LikeComparisonRuleBuilderDataAdapter
 */
class WildCardComparisonRuleBuilderAdapter implements ArrayDataRuleBuilderInterface
{
    /** @var AbstractWildCardComparisonRuleBuilder */
    private $builder;

    public function __construct(AbstractWildCardComparisonRuleBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param array $data
     * @return AbstractArrayDataRule
     */
    public function buildFromArray(array $data)
    {
        if (isset($data['compare'])) {
            $data[AbstractWildCardComparisonRuleBuilder::FIELD_WILDCARD] = in_array('field', $data['compare']);
            $data[AbstractWildCardComparisonRuleBuilder::VALUE_WILDCARD] = in_array('value', $data['compare']);
        }

        return $this->builder->buildFromArray($data);
    }
}
