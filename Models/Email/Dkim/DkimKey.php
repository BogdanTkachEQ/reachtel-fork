<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Email\Dkim;

/**
 * Class DkimKey
 *
 * Represents a Dkimkey
 *
 */
class DkimKey {

	/**
	 * @var resource
	 */
	private $privateKey;
	private $details;

	/**
	 * @var resource
	 */
	private $publicKey;

	public function __construct($privateKey) {
		if(!($this->privateKey = openssl_pkey_get_private($privateKey))) {
			throw new \InvalidArgumentException("Invalid private key supplied");
		}

		$this->details = openssl_pkey_get_details($this->privateKey);
		$this->publicKey = openssl_pkey_get_public($this->details['key']);
	}

	/**
	 * @return bool|resource
	 */
	public function getPrivateKey() {
		return $this->privateKey;
	}

	/**
	 * @return resource
	 */
	public function getPublicKey() {
		return $this->publicKey;
	}

	/**
	 * @param $openSSLKeyType (OPENSSL_KEYTYPE_RSA, etc)
	 * @return bool
	 */
	public function isType($openSSLKeyType) {
		$privateDetails = openssl_pkey_get_details($this->privateKey);
		if(!$privateDetails) {
			return \InvalidArgumentException("Invalid private key supplied");
		}
		return $privateDetails['type'] === $openSSLKeyType;
	}


	/**
	 * Verify the given public key is derived from this key
	 *
	 * @param $publicKey
	 */
	public function verifyPublicKey($publicKey) {
		$privateDetails = $this->details;
		$cleanedUserPublicKey = str_replace("\r\n", "\n", $publicKey) ;
		return isset($privateDetails['key']) ? strcmp(trim($privateDetails['key']), trim($cleanedUserPublicKey)) === 0 : false;
	}

	/**
	 * @return mixed
	 */
	public function exportPrivate() {
		openssl_pkey_export($this->privateKey, $key);
		return $key;
	}

	/**
	 * @return mixed
	 */
	public function exportPublic() {
		return $this->details['key'];
	}

}
