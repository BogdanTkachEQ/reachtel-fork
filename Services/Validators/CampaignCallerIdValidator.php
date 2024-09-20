<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Services\Campaign\Classification\CampaignClassificationEnum;
use Services\Validators\Interfaces\CampaignValidatorInterface;

/**
 * Class CampaignCallerIdValidator
 */
class CampaignCallerIdValidator implements CampaignValidatorInterface
{
    /** @var CampaignClassificationEnum */
    private $classification;

    /** @var boolean */
    private $isCallerIdWithHeld;

    /**
     * @param CampaignClassificationEnum $classification
     * @return $this
     */
    public function setClassification(CampaignClassificationEnum $classification)
    {
        $this->classification = $classification;
        return $this;
    }

    /**
     * @param boolean $isCallerIdWithHeld
     * @return $this
     */
    public function isCallerIdWithHeld($isCallerIdWithHeld)
    {
        $this->isCallerIdWithHeld = $isCallerIdWithHeld;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        if ($this->classification->is(CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT())) {
            return true;
        }
        return $this->isCallerIdWithHeld != true;
    }
}
