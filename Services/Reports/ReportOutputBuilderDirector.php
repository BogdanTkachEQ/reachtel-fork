<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Services\Reports\Builders\FilterRulesEngineBuilder;
use Services\Reports\Builders\ReportOutputBuilder;
use Services\Reports\Exceptions\DataModifierTemplateParserInvalidTemplateException;
use Services\Utils\TagTemplateParser;

/**
 * Class ReportOutputBuilderDirector
 */
class ReportOutputBuilderDirector
{
    /** @var ReportOutputBuilder */
    private $builder;

    /** @var RowDataModifierTemplateParser */
    private $rowDataModifierTemplateParser;

    /** @var FilterRulesEngineBuilder */
    private $filterRulesEngineBuilder;

    /**
     * ReportOutputBuilderDirector constructor.
     * @param ReportOutputBuilder           $builder
     * @param RowDataModifierTemplateParser $rowDataModifierTemplateParser
     * @param FilterRulesEngineBuilder      $filterRulesEngineBuilder
     */
    public function __construct(
        ReportOutputBuilder $builder,
        RowDataModifierTemplateParser $rowDataModifierTemplateParser,
        FilterRulesEngineBuilder $filterRulesEngineBuilder
    ) {
        $this->builder = $builder;
        $this->rowDataModifierTemplateParser = $rowDataModifierTemplateParser;
        $this->filterRulesEngineBuilder = $filterRulesEngineBuilder;
    }

    /**
     * @return ReportOutputBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param string $outputColumnString
     * @return ReportOutputBuilderDirector
     */
    public function setOutputColumnString($outputColumnString)
    {
        $outputColumns = TagTemplateParser::splitTemplates($outputColumnString);
        $this->builder->setOutputColumns($this->prepareOutputColumns($outputColumns));
        return $this;
    }

    /**
     * @param string $filterString
     * @return ReportOutputBuilderDirector
     * @throws \Exception
     */
    public function setFilterString($filterString)
    {
        $rulesEngine = $this->filterRulesEngineBuilder->buildFilterRulesEngine($filterString);
        $this->builder->setFilterRulesEngine($rulesEngine);
        return $this;
    }

    /**
     * @param string $headerStringMap
     * @return $this
     */
    public function setHeaderMapString($headerStringMap)
    {
        $headerMap = [];
        $headerMapArray = array_map('trim', explode(',', $headerStringMap));
        foreach ($headerMapArray as $map) {
            list($key, $value) = array_map('trim', explode(':', $map));
            $headerMap[$key] = $value;
        }

        $this->builder->setHeaderMap($headerMap);
        return $this;
    }

    /**
     * @param array $outputColumns
     * @return array
     */
    protected function prepareOutputColumns(array $outputColumns)
    {
        foreach ($outputColumns as &$column) {
            try {
                $modifier = $this->rowDataModifierTemplateParser->getModifierFromTemplate($column);
                $column = $modifier;
            } catch (DataModifierTemplateParserInvalidTemplateException $exception) {
                // Not a template
                continue;
            }
        }

        return $outputColumns;
    }
}
