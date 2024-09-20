<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Campaign\Name;

use Doctrine\Common\Collections\ArrayCollection;
use Services\Campaign\Name\Interfaces\CampaignNameInterface;
use Services\Exceptions\Campaign\CampaignNameRuntimeException;

/**
 * Class CompositeTokenDateTemplateCampaignName
 */
class CompositeCampaignNameCollection implements CampaignNameInterface
{
    /** @var CampaignNameInterface[] */
    private $campaignNameCollection;

    /** @var string */
    private $item;

    /**
     * CompositeCampaignNameCollection constructor.
     */
    public function __construct()
    {
        $this->campaignNameCollection = new ArrayCollection();
    }

    /**
     * @param string                $item
     * @param CampaignNameInterface $campaignName
     * @return $this
     */
    public function add($item, CampaignNameInterface $campaignName)
    {
        $this->campaignNameCollection->set($item, $campaignName);
        return $this;
    }

    /**
     * @param string $item
     * @return $this
     * @throws CampaignNameRuntimeException
     */
    public function setItem($item)
    {
        if (!$this->campaignNameCollection->containsKey($item)) {
            throw new CampaignNameRuntimeException('Item does not exist in the collection of campaign names');
        }
        $this->item = $item;
        return $this;
    }

    /**
     * @return string
     * @throws CampaignNameRuntimeException
     */
    public function getName()
    {
        $this->validateItem();
        return $this
            ->campaignNameCollection
            ->get($this->item)
            ->getName();
    }

    /**
     * @return string
     * @throws CampaignNameRuntimeException
     */
    public function getSearchableName()
    {
        $this->validateItem();
        return $this
            ->campaignNameCollection
            ->get($this->item)
            ->getSearchableName();
    }

    /**
     * @return boolean
     * @throws CampaignNameRuntimeException
     */
    private function validateItem()
    {
        if (is_null($this->item)) {
            throw new CampaignNameRuntimeException('Item to return campaign name is not set');
        }
        return true;
    }
}
