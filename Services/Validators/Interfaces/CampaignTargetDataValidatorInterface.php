<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Interfaces;

use Services\Validators\Interfaces\CampaignTargetDataSanitizerInterface as SanitizerInterface;

/**
 * interface CampaignTargetDataValidatorInterface
 */
interface CampaignTargetDataValidatorInterface extends SanitizerInterface, CampaignDataValidatorInterface
{
    /**
     * @param string $targetKey
     * @return $this
     */
    public function setTargetKey($targetKey);

    /**
     * @param array $mergeData
     * @return $this
     */
    public function setMergeData(array $mergeData);
}
