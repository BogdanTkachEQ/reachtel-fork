<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Reports\Interfaces;

/**
 * interface DataModifierInterface
 */
interface RowDataModifierInterface
{
    /**
     * @param array $data
     * @return RowDataModifierInterface
     */
    public function setRowData(array $data);

    /**
     * @return mixed
     */
    public function getModifiedData();

    /**
     * @return string
     */
    public function getHeaderName();
}
