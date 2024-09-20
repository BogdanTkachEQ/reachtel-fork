<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Hooks;

use Services\Hooks\Interfaces\PostHook;

/**
 * Class TagCampaignHook
 * @package Services\Campaign\Hooks
 */
class TagCampaignHook implements PostHook
{

    private $campaignId;
    private $hasRun = false;
    private $errors = false;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return bool
     */
    public function run()
    {
        $this->hasRun = true;

        $postCompletionHook = api_campaigns_tags_get($this->campaignId, "post-completion-hook");
        if (!empty($postCompletionHook)) {
            $filePath = __DIR__ . "/../../../scripts/hooks/" . $postCompletionHook . ".php";

            if (is_readable($filePath)) {
                include_once($filePath);
                $function = "api_campaigns_hooks_" . $postCompletionHook;
                if (!is_callable($function)) {
                    $this->errors = "Unable to run the post completion hook for campaignId " . $this->campaignId;
                    return api_error_raise($this->errors);
                } else {
                    if (!$function($this->campaignId)) {
                        $this->errors = "The post completion hook for campaignId " . $this->campaignId . " failed";
                        return api_error_raise($this->errors);
                    }
                }
                return true;
            } else {
                $this->errors = "Unable to run the post completion hook {$postCompletionHook} for campaignId:" ;
                $this->errors .= $this->campaignId." does it exist?";
                return api_error_raise($this->errors);
            }
        }
    }

    /**
     * @return bool
     */
    public function hasRun()
    {
        return $this->hasRun;
    }

    /**
     * @return bool
     */
    public function runWasSuccess()
    {
        return $this->hasRun() && empty($this->errors);
    }

    /**
     * @return bool
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return get_class($this);
    }
}
