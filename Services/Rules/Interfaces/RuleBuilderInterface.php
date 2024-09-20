<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rules\Interfaces;

/**
 * interface RuleBuilderInterface
 */
interface RuleBuilderInterface
{
    /**
     * @param array $data
     * @return RulesInterface
     */
    public function buildFromArray(array $data);
}
