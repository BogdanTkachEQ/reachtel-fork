<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Validators\Disclaimers;

use Services\Campaign\Interfaces\Validators\DisclaimerProviderInterface;

/**
 * Class CallerIdDisclaimerProvider
 */
class CallerIdDisclaimerProvider implements DisclaimerProviderInterface
{
    /**
     * @return string
     */
    public function getDisclaimer()
    {
        return "The Telecommunications (Telemarketing and Research Calls) "
            . "Industry Standard 2017 sets out rules around when voice calls can be made and information that must be "
            . "provided during or upon return of a call."
            . "For the type of campaign you have selected Caller ID must not be hidden.";
    }
}
