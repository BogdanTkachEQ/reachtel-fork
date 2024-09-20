<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\File\CSV;

use Goodby\CSV\Import\Standard\Interpreter;
use Goodby\CSV\Import\Standard\Lexer;
use ParseCsv\Csv;

class BasicCSV implements Interfaces\CSV
{

    private $parser;

    public function __construct(Csv $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param $str
     * @return array
     */
    public function parseString($str)
    {
        return $this->parseFile($str);
    }

    /**
     * @param $file
     * @return array
     */
    public function parseFile($file)
    {
        return $this->parser->parse($file);
    }

    /**
     * @return mixed
     */
    public function getRowData()
    {
        return $this->parser->data;
    }

    /**
     * @return bool|int
     */
    public function getRowCount()
    {
        return $this->parser->getTotalDataRowCount();
    }

    public function setDelimiter($delim)
    {
        $this->parser->delimiter = $delim;
        return $this;
    }

    public function setNewLineChar($newline)
    {
        $this->parser->linefeed = $newline;
        return $this;
    }

    public function setEncoding($encoding)
    {
        $this->parser->input_encoding = $encoding;
        return $this;
    }

    public function setHasHeader($hasHeader)
    {
        $this->parser->heading = $hasHeader;
        return $this;
    }

    public function setQuoteChar($quoteChar)
    {
        $this->parser->enclosure = $quoteChar;
        return $this;
    }

    public function getHeader()
    {
        return $this->parser->titles;
    }

    /**
     * @param array $data
     * @return string
     */
    public function unparse(array $data)
    {
        return $this
            ->parser
            ->unparse($data, [], false, false, $this->parser->delimiter);
    }
}
