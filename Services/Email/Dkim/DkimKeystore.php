<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Models\Email\Dkim\DkimKey;
use Services\Exceptions\Email\DkimException;

/**
 * Class DkimKeystore
 * Utility class for interacting with the keystore with dkim keys
 */
abstract class DkimKeystore
{

    protected $id;
    protected $selector;

    const ITEM_NAME_TEMPLATE = "dkim-key-[SELECTOR]";
    const PRIVATE_ITEM_NAME_TEMPLATE = self::ITEM_NAME_TEMPLATE."-private";
    const PUBLIC_ITEM_NAME_TEMPLATE = self::ITEM_NAME_TEMPLATE."-public";
    /**
     * @var DkimKeyFactory|null
     */
    private $keyFactory;

    /**
     * @param $selector
     * @return mixed
     */
    public static function getPrivateItemName($selector)
    {
        static::validateSelectorName($selector);
        return str_replace("[SELECTOR]", $selector, self::PRIVATE_ITEM_NAME_TEMPLATE);
    }

    /**
     * @param $selector
     * @return mixed
     */
    public static function getPublicItemName($selector)
    {
        static::validateSelectorName($selector);
        return str_replace("[SELECTOR]", $selector, self::PUBLIC_ITEM_NAME_TEMPLATE);
    }

    /**
     * @param $selector
     * @return mixed
     */
    public static function getItemName($selector)
    {
        static::validateSelectorName($selector);
        return str_replace("[SELECTOR]", $selector, self::ITEM_NAME_TEMPLATE);
    }

    /**
     * @param $selector
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function validateSelectorName($selector)
    {
        if (!strlen($selector) || !preg_match("/^[a-zA-Z0-9_]+$/", $selector)) {
            throw new \InvalidArgumentException("Invalid selector name: {$selector}");
        }
        return true;
    }

    public function __construct($id, $selector = null, DkimKeyFactory $keyFactory = null)
    {
        $this->id = $id;
        $this->selector = $selector;
        $this->keyFactory = $keyFactory ;
    }

    /**
     * @return boolean
     */
    abstract protected function validateOwnerId();

    /**
     * @param string $privateKey
     * @param string $publicKey
     * @return boolean
     */
    abstract protected function saveToOwner($privateKey, $publicKey);

    /**
     * @return boolean
     */
    abstract protected function deleteFromOwner();

    /**
     * @param DkimKeyTypeEnum $type
     * @return array|boolean
     */
    abstract public function getKeys(DkimKeyTypeEnum $type = null);

    /**
     * @param DkimKeyTypeEnum|null $type
     * @return array|boolean
     */
    abstract public function getAllKeys(DkimKeyTypeEnum $type = null);

    /**
     * @return DkimKey|null
     * @throws DkimException
     */
    public function getDkimKey()
    {
        if (!$this->keyFactory) {
            throw new DkimException("No key factory provided");
        }
        $keys = $this->getKeys(DkimKeyTypeEnum::PRIVATE_KEY());
        if (isset($keys[0]['value'])) {
            return $this->keyFactory->createKey($keys[0]['value']);
        }
        return null;
    }

    /**
     * @param DkimKey $key
     * @return bool
     */
    public function saveKey(DkimKey $key)
    {
        self::validateSelectorName($this->selector);
        $this->validateOwnerId();

        if (!$key->isType(OPENSSL_KEYTYPE_RSA)) {
            return api_error_raise("Invalid private key supplied - must be RSA");
        }

        api_db_starttrans();
        try {
            $this->saveToOwner($key->exportPrivate(), $key->exportPublic());
            if (!api_db_endtrans()) {
                throw new \Exception("Could not complete transation!");
            }
            return true;
        } catch (\Exception $e) {
            api_db_failtrans();
            api_error_raise($e->getMessage());
            throw $e ;
        }
    }

    /**
     * @return bool
     * @throws DkimException
     */
    public function deleteKey()
    {
        self::validateSelectorName($this->selector);
        $this->validateOwnerId();

        api_db_starttrans();
        if ($this->deleteFromOwner()) {
            api_db_endtrans();
            return true;
        } else {
            api_db_failtrans();
            throw new DkimException("Could not delete the given dkim key: ".$this->selector) ;
        }
    }
}
