<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Morpheus\Schema\Types;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Class CampaignClassificationEnumType
 */
class CampaignClassificationEnumType extends AbstractEnumType
{
    const TYPE_NAME = 'campaign_classification';

    /**
     * @return array
     */
    protected function getValues()
    {
        return [
            CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_TELEMARKETING,
            CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_RESEARCH,
            CampaignClassificationEnum::CAMPAIGN_SETTING_CLASSIFICATION_EXEMPT
        ];
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return mixed
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return CampaignClassificationEnum::byValue($value);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (!($value instanceof CampaignClassificationEnum)) {
            throw new \InvalidArgumentException('Invalid value for campaign classification received');
        }

        return $value->getValue();
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     *
     * @todo Needed?
     */
    public function getName()
    {
        return static::TYPE_NAME;
    }
}
