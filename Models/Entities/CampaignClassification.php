<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use Services\Campaign\Classification\CampaignClassificationEnum;

/**
 * Class CampaignClassification
 */
class CampaignClassification
{
    /** @var integer */
    private $id;

    /** @var CampaignClassificationEnum */
    private $name;

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CampaignClassificationEnum
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param CampaignClassificationEnum $name
     * @return $this
     */
    public function setName(CampaignClassificationEnum $name)
    {
        $this->name = $name;
        return $this;
    }
}
