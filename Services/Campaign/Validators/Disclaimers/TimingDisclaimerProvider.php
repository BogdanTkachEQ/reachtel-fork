<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators\Disclaimers;

use Models\CampaignSettings;
use Models\Entities\TimingPeriod;
use Services\Campaign\CampaignTimingAccessor;
use Services\Campaign\Interfaces\Validators\DisclaimerProviderInterface;

/**
 * Class TimingDisclaimerProvider
 */
class TimingDisclaimerProvider implements DisclaimerProviderInterface
{
    /**
     * @var CampaignTimingAccessor
     */
    private $timingAccessor;

    /**
     * @var CampaignSettings
     */
    private $campaignSettings;

    /**
     * TimingDisclaimerProvider constructor.
     * @param CampaignTimingAccessor $ruleAccessor
     */
    public function __construct(CampaignTimingAccessor $ruleAccessor)
    {
        $this->timingAccessor = $ruleAccessor;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return $this
     */
    public function setCampaignSettings(CampaignSettings $campaignSettings)
    {
        $this->campaignSettings = $campaignSettings;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisclaimer()
    {
        if (!$this->campaignSettings) {
            return null;
        }

        $periods = $this->timingAccessor->getTimingPeriods($this->campaignSettings);

        if (!$periods->count()) {
            return null;
        }

        $message = sprintf(
            "Specific & recurring time periods for %s %s campaigns must match the following rules:\n",
            $this->campaignSettings->getRegion()->getCountry()->getShortName(),
            $this->campaignSettings->getClassificationEnum()->getValue()
        );

        /** @var TimingPeriod $period */
        foreach ($periods as $period) {
            $message .= sprintf(
                "\n * %s: %s to %s",
                ucwords($period->getDay()->getName()),
                $period->getStart()->format('h:i A'),
                $period->getEnd()->format('h:i A')
            );
        }

        // calls on public holidays are never allowed
        $message .= "\n * No calls on public holidays";

        $message .= sprintf(
            "\n\nAll times are relative to campaign timezone: %s",
            $this->campaignSettings->getTimeZone()->getName()
        );

        return $message;
    }
}
