<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils;

use Services\Exceptions\Utils\TemplateParserException;
use Services\Utils\Interfaces\InputStringTemplateParser;

/**
 * Class TagTemplateParser
 */
class TagTemplateParser implements InputStringTemplateParser
{
    /** @var string */
    private $templateType;

    /** @var array */
    private $attributes;

    /** @var string */
    private $argumentTemplate;

    /**
     * @param string $template
     * @return $this
     * @throws TemplateParserException
     */
    public function setTemplate($template)
    {
        $this->resetData();
        if (!preg_match('/\{\%(\w*):(.*)\%\}/', $template, $matches)) {
            throw new TemplateParserException('Unable to parse template');
        }

        $this->templateType = $matches[1];
        $this->argumentTemplate = $matches[2];
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateType()
    {
        return $this->templateType;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if (!$this->argumentTemplate) {
            return [];
        }

        if (is_null($this->attributes)) {
            $arguments = preg_split('/\[[^\]]*(])(*SKIP)(*F)|[,]+/', $this->argumentTemplate);

            $data = [];
            foreach ($arguments as $argument) {
                list($key, $value) = explode('=', $argument);
                $value = (trim($value) !== '') ? trim($value) : $value;

                if (!preg_match('/\[(.*)\]/', $value, $matches)) {
                    $data[trim($key)] = $value;
                    continue;
                }

                $data[trim($key)] = array_map('trim', explode(',', $matches[1]));
            }

            $this->attributes = $data;
        }

        return $this->attributes;
    }

    /**
     * @return $this
     */
    protected function resetData()
    {
        $this->templateType = null;
        $this->attributes = null;
        $this->argumentTemplate = null;
        return $this;
    }


    /**
     * @param string $templatesString
     * @return array
     */
    public static function splitTemplates($templatesString)
    {
        $templates = preg_split('/{%[^%}]*(%})(*SKIP)(*F)|[,]+/', $templatesString);
        return array_map('trim', $templates);
    }
}
