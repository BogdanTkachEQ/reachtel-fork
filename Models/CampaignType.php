<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models;

use MabeEnum\Enum;

/**
 * Class CampaignType
 */
class CampaignType extends Enum
{
    const PHONE = CAMPAIGN_TYPE_VOICE;
    const WASH = CAMPAIGN_TYPE_WASH;
    const EMAIL = CAMPAIGN_TYPE_EMAIL;
    const SMS = CAMPAIGN_TYPE_SMS;
}
