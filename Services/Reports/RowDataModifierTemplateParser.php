<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports;

use Models\Reports\RowDataModifierType;
use Services\Exceptions\Utils\TemplateParserException;
use Services\Reports\Exceptions\DataModifierTemplateParserInvalidTemplateException;
use Services\Reports\Interfaces\RowDataModifierInterface;
use Services\Utils\Interfaces\InputStringTemplateParser;

/**
 * Class InputTemplateParser
 */
class RowDataModifierTemplateParser
{
    /** @var RowDataModifierFactory */
    private $dataModifierFactory;

    /** @var InputStringTemplateParser */
    private $templateParser;

    /**
     * RowDataModifierTemplateParser constructor.
     * @param RowDataModifierFactory    $dataModifierFactory
     * @param InputStringTemplateParser $parser
     */
    public function __construct(
        RowDataModifierFactory $dataModifierFactory,
        InputStringTemplateParser $parser
    ) {
        $this->dataModifierFactory = $dataModifierFactory;
        $this->templateParser = $parser;
    }

    /**
     * @param string $template
     * @return RowDataModifierInterface
     */
    public function getModifierFromTemplate($template)
    {
        try {
            $this->templateParser->setTemplate($template);
        } catch (TemplateParserException $exception) {
            throw new DataModifierTemplateParserInvalidTemplateException($exception->getMessage());
        }

        $modifierType = RowDataModifierType::byValue($this->templateParser->getTemplateType());

        return $this->dataModifierFactory->create($modifierType, $this->templateParser->getAttributes());
    }
}
