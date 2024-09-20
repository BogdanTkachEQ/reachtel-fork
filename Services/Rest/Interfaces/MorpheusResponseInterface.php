<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest\Interfaces;

/**
 * Interface ResponseInterface
 */
interface MorpheusResponseInterface
{
    /**
     * @return string
     */
    public function getBody();

    /**
     * @return array
     */
    public function getHeaders();

    /**
     * @return integer
     */
    public function getStatusCode();

    /**
     * @return string
     */
    public function getMessage();
}
