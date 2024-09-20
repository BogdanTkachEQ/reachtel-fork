<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign;

use Models\CampaignSettings;
use Services\Campaign\Validators\CampaignTimingValidationService;
use Services\Exceptions\Campaign\Validators\PublicHolidayValidationFailure;
use Services\Exceptions\Campaign\Validators\TimingRuleValidationFailure;

/**
 * Class CampaignNextAttemptService
 */
class CampaignNextAttemptService
{
    /** @var CampaignTimingValidationService */
    private $timingValidationService;

    /** @var CampaignTimingAccessor */
    private $campaignTimingAccessor;

    /**
     * CampaignNextAttemptService constructor.
     * @param CampaignTimingValidationService $timingValidationService
     * @param CampaignTimingAccessor          $campaignTimingAccessor
     */
    public function __construct(
        CampaignTimingValidationService $timingValidationService,
        CampaignTimingAccessor $campaignTimingAccessor
    ) {
        $this->timingValidationService = $timingValidationService;
        $this->campaignTimingAccessor = $campaignTimingAccessor;
    }

    /**
     * TODO: This function is incomplete and needs to be updated to handle timing range
     * Gives the next attempt date time in campaigns timezone
     * @param CampaignSettings $campaignSettings
     * @param \DateTime        $currentTime
     * @param \DateInterval    $nextAttemptInterval
     * @return \DateTime
     * @throws \Exception
     */
    public function getValidNextAttemptDateTime(
        CampaignSettings $campaignSettings,
        \DateTime $currentTime,
        \DateInterval $nextAttemptInterval
    ) {
        $nextAttemptTime = $currentTime->add($nextAttemptInterval);

        try {
            $this->timingValidationService->isValidDateTime($nextAttemptTime, $campaignSettings);
        } catch (PublicHolidayValidationFailure $exception) {
            return $this
                ->getValidNextAttemptDateTime(
                    $campaignSettings,
                    $nextAttemptTime->setTime('00', '00', '00'),
                    new \DateInterval('P1D')
                );
        } catch (TimingRuleValidationFailure $exception) {
            $group = $this
                ->campaignTimingAccessor
                ->getTimingGroup($campaignSettings);

            $period = $group->getTimingPeriodByDateTime($nextAttemptTime);

            if ($nextAttemptTime <= $period->getStartByDate($nextAttemptTime)) {
                return $period->getStartByDate($nextAttemptTime);
            }

            return $this
                ->getValidNextAttemptDateTime(
                    $campaignSettings,
                    $nextAttemptTime->setTime('00', '00', '00'),
                    new \DateInterval('P1D')
                );
        }

        return $nextAttemptTime;
    }
}
