<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Interfaces;

/**
 * Class FixedWidthFileSpecificationInterface
 */
interface FixedWidthFieldSpecificationInterface
{
    /**
     * @return string
     */
    public function getFieldName();

    /**
     * @return integer
     */
    public function getStartPosition();

    /**
     * @return integer
     */
    public function getLength();
}
