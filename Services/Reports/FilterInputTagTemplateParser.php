<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Reports\Adapters\ArrayRuleBuilderAdapterFactory;
use Services\Rules\ArrayRules\AbstractArrayDataRule;
use Services\Rules\ArrayRules\ArrayDataRuleBuilderFactory;
use Services\Rules\ArrayRules\RuleType;
use Services\Utils\Interfaces\InputStringTemplateParser;

/**
 * Class FilterInputTagTemplateParser
 */
class FilterInputTagTemplateParser
{
    /** @var InputStringTemplateParser */
    private $parser;

    /** @var ArrayDataRuleBuilderFactory */
    private $ruleBuilderFactory;

    /** @var ArrayRuleBuilderAdapterFactory */
    private $ruleBuilderDataAdapterFactory;

    /**
     * FilterInputTagTemplateParser constructor.
     * @param InputStringTemplateParser      $templateParser
     * @param ArrayDataRuleBuilderFactory    $ruleFactory
     * @param ArrayRuleBuilderAdapterFactory $ruleBuilderDataAdapterFactory
     */
    public function __construct(
        InputStringTemplateParser $templateParser,
        ArrayDataRuleBuilderFactory $ruleFactory,
        ArrayRuleBuilderAdapterFactory $ruleBuilderDataAdapterFactory
    ) {
        $this->parser = $templateParser;
        $this->ruleBuilderFactory = $ruleFactory;
        $this->ruleBuilderDataAdapterFactory = $ruleBuilderDataAdapterFactory;
    }

    /**
     * @param $template
     * @return AbstractArrayDataRule
     */
    public function getRuleFromTemplate($template)
    {
        $this->parser->setTemplate($template);
        $ruleType = RuleType::byValue($this->parser->getTemplateType());
        $builder = $this->ruleBuilderFactory->create($ruleType);

        $adapter = $this->ruleBuilderDataAdapterFactory->create($builder);

        return $adapter->buildFromArray($this->parser->getAttributes());
    }
}
