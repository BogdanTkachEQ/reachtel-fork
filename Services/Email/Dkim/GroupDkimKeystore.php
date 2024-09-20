<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Services\Exceptions\Email\DkimException;

/**
 * Class GroupDkimKeystore
 * @package Services\Email\Dkim
 */
class GroupDkimKeystore extends DkimKeystore
{

    const SYSTEM_KEY_ITEM = "dkimsystemkey";
    const SYSTEM_SELECTOR_KEY_ITEM = "dkimsystemkey-selector";

    public function __construct($id, $selector = null, DkimKeyFactory $dkimKeyFactory = null)
    {
        parent::__construct($id, $selector, $dkimKeyFactory);
    }

    protected function validateOwnerId()
    {
        if (!api_groups_checkidexists($this->id)) {
            throw new \InvalidArgumentException("Group id does not exist: " . $this->id);
        }
    }

    protected function saveToOwner($privateKey, $publicKey)
    {

        if (api_groups_setting_getsingle($this->id, DkimKeystore::getPrivateItemName($this->selector)) ||
            api_groups_setting_getsingle($this->id, DkimKeystore::getPublicItemName($this->selector))
        ) {
            throw new DkimException("A selector with that key name already exists, remove it first");
        }
        if (!api_groups_setting_set(
            $this->id,
            DkimKeystore::getPrivateItemName($this->selector),
            $privateKey
        )) {
            throw new DkimException("Could not save the private key");
        }

        if (!api_groups_setting_set($this->id, DkimKeystore::getPublicItemName($this->selector), $publicKey)) {
            throw new DkimException("Could not save the public key");
        }
        return true;
    }

    /**
     * @param DkimKeyTypeEnum|null $type
     * @return array|bool
     */
    public function getKeys(DkimKeyTypeEnum $type = null)
    {
        self::validateSelectorName($this->selector);
        return api_email_get_dkim_keys($this->id, KEY_STORE_TYPE_GROUPS, $this->selector, $type);
    }

    /**
     * @param DkimKeyTypeEnum|null $type
     * @return array|bool
     */
    public function getAllKeys(DkimKeyTypeEnum $type = null)
    {
        return api_email_get_dkim_keys($this->id, KEY_STORE_TYPE_GROUPS, null, $type);
    }

    /**
     * @return bool
     */
    protected function deleteFromOwner()
    {
        return api_groups_setting_delete_single(
            $this->id,
            DkimKeystore::getPrivateItemName($this->selector)
        ) &&
            api_groups_setting_delete_single(
                $this->id,
                DkimKeystore::getPublicItemName($this->selector)
            );
    }

    /**
     * @return bool
     */
    public function hasSystemKey()
    {
        return (bool)api_groups_setting_getsingle($this->id, self::SYSTEM_KEY_ITEM);
    }

    /**
     * @param DkimKeyTypeEnum|null $type
     * @return array|bool
     */
    public function getSystemKey(DkimKeyTypeEnum $type = null)
    {
        $systemSelectorName = api_groups_setting_getsingle($this->id, self::SYSTEM_KEY_ITEM);
        if ($systemSelectorName) {
            $keystore = (new DkimKeystoreFactory())->build(DkimKeystoreTypeEnum::SYSTEM(), null, $systemSelectorName);
            return $keystore->getKeys($type);
        }
    }

    /**
     * @return false|string
     */
    public function getSystemSelector()
    {
        return api_groups_setting_getsingle($this->id, self::SYSTEM_SELECTOR_KEY_ITEM);
    }

    /**
     * @return bool
     */
    public function removeSystemKey()
    {
        return api_groups_setting_delete_single($this->id, self::SYSTEM_KEY_ITEM)
            && api_groups_setting_delete_single($this->id, self::SYSTEM_SELECTOR_KEY_ITEM);
    }

    /**
     * @param $systemKeyName
     * @return bool
     */
    public function setSystemKey($systemKeyName)
    {
        return api_groups_setting_set($this->id, self::SYSTEM_KEY_ITEM, $systemKeyName) &&
            api_groups_setting_set($this->id, self::SYSTEM_SELECTOR_KEY_ITEM, $this->selector);
    }
}
