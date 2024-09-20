<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Email\Dkim;

use Services\Exceptions\Email\DkimException;

/**
 * Class DkimKeyResolver
 * Considers various conditions and resolves them back to the relevant key and selector for the given group
 */
class DkimKeyResolver
{
    private $campaignId;
    /**
     * @var DkimKeyFactory
     */
    private $keyFactory;
    /**
     * @var \Models\Email\Dkim\DkimKey
     */
    private $resolvedKey;
    /**
     * @var string
     */
    private $resolvedDkimSelector;
    /**
     * @var DkimKeystoreFactory
     */
    private $keyStoreFactory;
    private $groupId;
    private $fromEmail;
    private $defaultDomain;
    private $defaultSelector;

    public function __construct(DkimKeystoreFactory $keyStoreFactory, DkimKeyFactory $keyFactory)
    {
        $this->keyFactory = $keyFactory;
        $this->keyStoreFactory = $keyStoreFactory;
    }

    /**
     * @param $campaignId
     * @param $groupId
     * @return $this
     */
    public function setCampaign($campaignId, $groupId)
    {
        $this->campaignId = $campaignId;
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * @param $groupId
     * @return $this
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setFromEmail($email)
    {
        $this->fromEmail = $email;
        return $this;
    }

    /**
     * @param $domain
     */
    public function setDefaultDomain($domain, $defaultSelector)
    {
        $this->defaultDomain = $domain;
        $this->defaultSelector = $defaultSelector;
        return $this;
    }

    /**
     *
     * The resolver resolves in this order:
     * has campaign id -> check campaign dkim
     * neither of the above -> check the group has a system dkim key
     * none of the above check if $this->defaultDomain eq the domain in $this->fromEmail, if so check for a matching
     * default system key for $this->defaultSelector
     *
     * otherwise no key
     *
     * @return $this
     * @throws DkimException
     *
     */
    public function resolve()
    {
        // Campaign level selectors are priority
        if (is_numeric($this->campaignId)) {
            $dkimSelector = trim(api_campaigns_setting_getsingle($this->campaignId, "dkim")) ;
            if ($dkimSelector) {
                $key = $this->keyStoreFactory
                                ->build(DkimKeystoreTypeEnum::GROUP(), $this->groupId, $dkimSelector)
                                ->getDkimKey();
                if ($key) {
                    $this->resolvedDkimSelector = $dkimSelector;
                    $this->resolvedKey = $key;
                    return $this;
                }
            }
        }

        // If there's no campaign level selector, see if the group has a system level selector
        if (is_numeric($this->groupId)) {
            $groupKeyStore = $this->keyStoreFactory
                                ->build(DkimKeystoreTypeEnum::GROUP(), $this->groupId);
            $groupSystemKey = $groupKeyStore->getSystemKey(DkimKeyTypeEnum::PRIVATE_KEY());
            if ($groupSystemKey) {
                $this->resolvedDkimSelector = $groupKeyStore->getSystemSelector();
                $this->resolvedKey = $this->keyFactory->createKey($groupSystemKey[0]['value']);
                return $this;
            }
        }

        // Otherwise fallback to checking if the fromEmail address matches the default system domain, if so use that key
        if (($fromDomain = api_email_extract_domain($this->fromEmail))) {
            if ($this->defaultDomain && $fromDomain === $this->defaultDomain) {
                /**
                 * @var $sytemKeyStore SystemDkimKeystore
                 */
                $sytemKeyStore = $this->keyStoreFactory
                    ->build(DkimKeystoreTypeEnum::SYSTEM(), null, $this->defaultSelector);
                $defaultKey = $sytemKeyStore->getDkimKey();

                if ($defaultKey) {
                    $this->resolvedDkimSelector = $this->defaultSelector;
                    $this->resolvedKey = $defaultKey;
                    return $this;
                }
            }
        }
    }

    /**
     * @return \Models\Email\Dkim\DkimKey
     */
    public function getResolvedKey()
    {
        return $this->resolvedKey;
    }

    /**
     * @return string
     */
    public function getResolvedDkimSelector()
    {
        return $this->resolvedDkimSelector;
    }
}
