<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Validators\Interfaces;

/**
 * interface CampaignTargetDataSanitizerInterface
 */
interface CampaignTargetDataSanitizerInterface
{
    /**
     * @return array
     */
    public function getSanitizedMergeData();

    /**
     * @return string
     */
    public function getSanitizedTargetKey();
}
