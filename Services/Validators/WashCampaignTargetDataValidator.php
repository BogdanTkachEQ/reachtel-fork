<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators;

use Services\Exceptions\Validators\InvalidTargetKeyException;
use Services\Validators\Interfaces\CampaignTargetDataValidatorInterface;

/**
 * Class WashCampaignTargetDataValidator
 */
class WashCampaignTargetDataValidator implements CampaignTargetDataValidatorInterface
{
    /** @var array */
    private $mergeData;

    /** @var string */
    private $targetKey;

    /**
     * @return array
     */
    public function getSanitizedMergeData()
    {
        $mergeData = [];
        foreach ($this->mergeData as $key => $value) {
            $length = strlen($value);
            $mergeData[$key] = str_repeat('x', $length);
        }

        return $mergeData;
    }

    /**
     * @return string
     */
    public function getSanitizedTargetKey()
    {
        try {
            $this->isValid();
        } catch (InvalidTargetKeyException $exception) {
            return preg_replace("/[^\d\-\_ ]/", "", $this->targetKey);
        }

        return $this->targetKey;
    }

    /**
     * @param string $targetKey
     * @return $this
     */
    public function setTargetKey($targetKey)
    {
        $this->targetKey = $targetKey;
        return $this;
    }

    /**
     * @param array $mergeData
     * @return $this
     */
    public function setMergeData(array $mergeData)
    {
        $this->mergeData = $mergeData;
        return $this;
    }

    /**
     * @return boolean
     * @throws InvalidTargetKeyException
     */
    public function isValid()
    {
        // This needs to support the format RT-TEST-* and RT-API-* as it is currently used for system generated targets
        if (preg_match('/^((RT\-TEST\-)|(RT\-API(\-\d+)?\-)|[-_]*)?\d+([-_\d ]*)?$/', $this->targetKey)) {
            return true;
        }
        throw new InvalidTargetKeyException('Wash campaign can not have non numeric target keys');
    }
}
