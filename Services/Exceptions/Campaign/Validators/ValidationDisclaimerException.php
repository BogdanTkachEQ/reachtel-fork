<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Exceptions\Campaign\Validators;

/**
 * Class ValidationDisclaimerException
 */
class ValidationDisclaimerException extends \Exception
{
    /** @var string */
    private $disclaimer = '';

    /**
     * @param string $disclaimer
     * @return $this
     */
    public function setDisclaimer($disclaimer)
    {
        $this->disclaimer = $disclaimer;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisclaimer()
    {
        return $this->disclaimer;
    }
}
