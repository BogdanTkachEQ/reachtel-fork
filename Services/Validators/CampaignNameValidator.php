<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Services\Exceptions\Validators\ValidatorRuntimeException;
use Services\Validators\Interfaces\CampaignValidatorInterface;

/**
 * Class CampaignNameValidator
 */
class CampaignNameValidator implements CampaignValidatorInterface
{
    /** @var string */
    private $name;

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return null|string|string[]
     */
    public function sanitizeName()
    {
        $name = preg_replace('/[^0-9^a-z^\-^ ]/i', '', $this->name);
        if (!$this->checkValid($name)) {
            throw new ValidatorRuntimeException('Sanitized name invalid for campaigns');
        }

        return $name;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->checkValid($this->name);
    }

    /**
     * @param $name
     * @return boolean
     */
    private function checkValid($name)
    {
        return preg_match('/^[0-9a-z\- ]{5,75}$/i', $name) != 0;
    }
}
