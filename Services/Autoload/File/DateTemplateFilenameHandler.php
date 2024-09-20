<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload\File;

/**
 * Class DateTemplateFileHander
 * Accepts a file name template in the format {DATE:BASICDATEFORMAT}{TOKEN}
 * e.g
 * 202010xyz123.csv {DATE:Ym}{TOKEN} = date = 202010 token = xyz123
 * xyz12320201015.csv {TOKEN}{DATE:Ymd} = date = 20201015 token = xyz123
 */
class DateTemplateFilenameHandler
{

    private $filename;
    private $template;
    /**
     * @var bool|string
     */
    private $dateFormatPortion;
    /**
     * @var bool|string
     */
    private $tokenPortion;

    /**
     * @var string
     */
    private $templateDateFormat;

    public function __construct($filename, $template)
    {
        $this->filename = $filename;
        $this->template = $template;
        $this->parse();
    }

    public function getDate()
    {
        return \DateTime::createFromFormat($this->templateDateFormat, $this->dateFormatPortion);
    }

    public function getDateFormatPortion()
    {
        return $this->dateFormatPortion;
    }

    public function getToken()
    {
        return $this->tokenPortion;
    }

    /**
     * @throws \Exception
     */
    private function parse()
    {
        $this->templateDateFormat = $this->parseTemplateDateFormat();
        $dateFormat = $this->templateDateFormat;
        $dateLength = strlen((new \DateTime())->format($dateFormat));

        $bareFilename = preg_replace('/\..*/', '', $this->filename);

        // Determine which order {TOKEN} comes in
        if (strpos($this->template, "{DATE:") < strpos($this->template, "{TOKEN}")) { // {TOKEN} is first
            $this->dateFormatPortion = static::trimPunctuation(substr($bareFilename, 0, $dateLength));
            $this->tokenPortion = static::trimPunctuation(substr($bareFilename, $dateLength));
        } else {
            $this->dateFormatPortion = static::trimPunctuation(substr($bareFilename, 0 - $dateLength));
            $this->tokenPortion = static::trimPunctuation(
                preg_replace("/{$this->dateFormatPortion}$/", "", $bareFilename)
            );
        }
    }

    /**
     * @return string
     */
    private function parseTemplateDateFormat()
    {
        $matches = [];
        if (preg_match("/\{DATE:(.*)\}/Ui", $this->template, $matches)) {
            $this->templateDateFormat = $matches[1];
            if (!$this->templateDateFormat) {
                throw new \InvalidArgumentException("{$this->template} does not contain a valid date format");
            }
        } else {
            throw new \InvalidArgumentException("{$this->template} does not contain a valid date tag");
        }
        return $this->templateDateFormat;
    }

    private static function trimPunctuation($string)
    {
        return preg_replace("/^\pP*|\pP*$/u", "", $string);
    }
}
