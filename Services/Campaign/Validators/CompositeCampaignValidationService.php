<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators;

use Doctrine\Common\Collections\ArrayCollection;
use Models\CampaignSettings;
use Services\Campaign\Interfaces\Validators\CampaignValidationServiceInterface;

/**
 * Class CompositeCampaignValidationService
 */
class CompositeCampaignValidationService implements CampaignValidationServiceInterface
{
    /** @var CampaignValidationServiceInterface[] */
    private $campaignValidationServices;

    /** @var CampaignValidationServiceInterface */
    private $validationServiceInProgress;

    /**
     * CompositeCampaignValidationService constructor.
     */
    public function __construct()
    {
        $this->campaignValidationServices = new ArrayCollection();
    }

    public function add(CampaignValidationServiceInterface $campaignValidationService)
    {
        $this->campaignValidationServices->add($campaignValidationService);
        if (!$this->validationServiceInProgress) {
            $this->validationServiceInProgress = $campaignValidationService;
        }
        return $this;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return boolean
     */
    public function violatesValidationRules(CampaignSettings $campaignSettings)
    {
        foreach ($this->campaignValidationServices as $validationService) {
            if ($validationService->violatesValidationRules($campaignSettings)) {
                $this->validationServiceInProgress = $validationService;
                return true;
            }
        }

        return false;
    }

    /**
     * @param CampaignSettings $campaignSettings
     * @return string
     */
    public function getDisclaimer(CampaignSettings $campaignSettings)
    {
        if (!$this->validationServiceInProgress) {
            return '';
        }

        return $this->validationServiceInProgress->getDisclaimer($campaignSettings);
    }

    /**
     * @return ArrayCollection|CampaignValidationServiceInterface[]
     */
    public function getValidationServices()
    {
        return $this->campaignValidationServices;
    }
}
