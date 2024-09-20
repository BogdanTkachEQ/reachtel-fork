<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Interfaces\LineExclusionRuleInterface;

/**
 * Class GenericLineExclusionRule
 */
class GenericLineExclusionRule implements LineExclusionRuleInterface
{
    /** @var array */
    private $exclusionColumns;

    /**
     * GenericLineExclusionRule constructor.
     * @param array $exclusionColumns
     */
    public function __construct(array $exclusionColumns)
    {
        $this->exclusionColumns = $exclusionColumns;
    }

    /**
     * @param array $line
     * @return boolean
     */
    public function shouldExclude(array $line)
    {
        foreach ($this->exclusionColumns as $column => $values) {
            if (!isset($line[$column])) {
                continue;
            }

            if (!is_array($values)) {
                $values = [$values];
            }

            if (in_array($line[$column], $values)) {
                return true;
            }
        }

        return false;
    }
}
