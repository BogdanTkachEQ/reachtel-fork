<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Models\Email\Dkim\DkimKey;
use Services\Exceptions\Email\DkimException;

/**
 * Class SystemDkimKeystore
 */
class SystemDkimKeystore extends DkimKeystore
{

    public function __construct($selector = null, DkimKeyFactory $dkimKeyFactory = null)
    {
        parent::__construct(0, $selector, $dkimKeyFactory);
    }

    /**
     * System settings have an keystore id of 0
     *
     * @return bool
     */
    protected function validateOwnerId()
    {
        return $this->id === 0;
    }

    /**
     * @param string $privateKey
     * @param string $publicKey
     * @return bool
     * @throws DkimException
     */
    protected function saveToOwner($privateKey, $publicKey)
    {
        if (api_system_setting_getsingle(DkimKeystore::getPrivateItemName($this->selector)) ||
            api_system_setting_getsingle(DkimKeystore::getPublicItemName($this->selector))
        ) {
            throw new DkimException("A selector with that key name already exists, remove it first");
        }
        if (!api_system_setting_set(
            DkimKeystore::getPrivateItemName($this->selector),
            $privateKey
        )) {
            throw new DkimException("Could not save the private key");
        }

        if (!api_system_setting_set(DkimKeystore::getPublicItemName($this->selector), $publicKey)) {
            throw new DkimException("Could not save the public key");
        }
        return true;
    }

    /**
     * @param DkimKeyTypeEnum $type
     * @return array|bool
     */
    public function getKeys(DkimKeyTypeEnum $type = null)
    {
        return api_email_get_dkim_keys($this->id, KEY_STORE_TYPE_SYSTEM, $this->selector, $type);
    }

    /**
     * @param DkimKeyTypeEnum|null $type
     * @return array|bool
     */
    public function getAllKeys(DkimKeyTypeEnum $type = null)
    {
        return api_email_get_dkim_keys($this->id, KEY_STORE_TYPE_SYSTEM, null, $type);
    }

    /**
     * @return bool
     */
    protected function deleteFromOwner()
    {
        $groupsUsingKey = api_keystore_getidswithvalue(
            KEY_STORE_TYPE_GROUPS,
            GroupDkimKeystore::SYSTEM_KEY_ITEM,
            $this->selector
        );
        if (!empty($groupsUsingKey)) {
            throw new DkimException(
                "There are groups currently using that key, 
                            won't delete it as it will potentially break their email. 
                            Group Ids using the key: ".implode(", ", $groupsUsingKey)
            ) ;
        }

        return api_system_setting_delete_single(
            DkimKeystore::getPrivateItemName($this->selector)
        ) &&
            api_system_setting_delete_single(
                DkimKeystore::getPublicItemName($this->selector)
            );
    }
}
