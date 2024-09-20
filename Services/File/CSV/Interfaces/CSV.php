<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\File\CSV\Interfaces;

interface CSV
{
    public function parseString($string);

    public function parseFile($file);

    public function getRowData();

    public function getRowCount();

    public function getHeader();

    /**
     * @param string $delim
     * @return $this
     */
    public function setDelimiter($delim);

    public function setQuoteChar($quoteChar);

    /**
     * @param string $newline
     * @return $this;
     */
    public function setNewLineChar($newline);

    public function setEncoding($encoding);

    public function setHasHeader($hasHeader);

    public function unparse(array $data);
}
