<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Utils\Interfaces;

use Services\Exceptions\Utils\TemplateParserException;

/**
 * interface InputStringTemplateParser
 */
interface InputStringTemplateParser
{
    /**
     * @param string $template
     * @return $this
     * @throws TemplateParserException
     */
    public function setTemplate($template);

    /**
     * @return string
     */
    public function getTemplateType();

    /**
     * @return array
     */
    public function getAttributes();
}
