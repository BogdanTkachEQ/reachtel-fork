<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Builders;

use Services\Reports\ArrayRulesEngineDecorator;
use Services\Reports\FilterInputTagTemplateParser;
use Services\Utils\TagTemplateParser;

/**
 * Class FilterRulesEngineBuilder
 */
class FilterRulesEngineBuilder
{
    /** @var ArrayRulesEngineDecorator */
    private $rulesEngine;

    /** @var FilterInputTagTemplateParser */
    private $parser;

    /**
     * FilterRulesEngineBuilder constructor.
     * @param FilterInputTagTemplateParser $parser
     * @param ArrayRulesEngineDecorator    $rulesEngine
     */
    public function __construct(
        FilterInputTagTemplateParser $parser,
        ArrayRulesEngineDecorator $rulesEngine
    ) {
        $this->parser = $parser;
        $this->rulesEngine = $rulesEngine;
    }

    /**
     * @param $filterString
     * @return ArrayRulesEngineDecorator
     */
    public function buildFilterRulesEngine($filterString)
    {
        $templates = TagTemplateParser::splitTemplates($filterString);
        foreach ($templates as $template) {
            $rule = $this->parser->getRuleFromTemplate($template);
            $this->rulesEngine->addArrayDataRules($rule);
        }

        return $this->rulesEngine;
    }
}
