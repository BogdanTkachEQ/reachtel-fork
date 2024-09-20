<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Email\Dkim;

use testing\unit\AbstractPhpunitUnitTest;

/**
 * Class DkimKeyTest
 */
class DkimKeyTest extends AbstractPhpunitUnitTest {

	private static $testPrivate = "-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAwJTdP46DSLmKhKam8IMlA8mfUFTVSocoKQAToQV4f2eTEnHv
T2PchDwddHUj0m6tk71+SM+ZvD3R4YlAAPjzm3dcS5/1AwZ/Ddnr/yKIJlP28BVz
YzrNJtluh4igAuwueG4bC8aCK5R1DaptEMYHD7FKlcYVCcBSwgThg7h8a0kkHHGc
9b6s+Czr2zd2ntGjGUmD7XLfOuK8yEWRVqJOYEdELfHnyZ3OlvQfYaIid69grlOg
HlYKjfd/JlzfSSgwkUmPs5A8+tRX92tWdfko75uItOt79UZJ8xTAaj32AKrDYwEg
53k+jJ4Piz+oZWqNT/5ZZ7tSTtBu1JmonyTITwIDAQABAoIBAEM5c8YE3GUdh3Ho
rquUS+53iipDgrTiWy18vl1eCIMIx7kPlobzB/4M8gc+AMQrKGJDKnsGJNcmCUae
X343aOojD0/CxYdJ1D4kjRHhnv8qHjAOfNnto8Fk8wVStvBuO4aEgOJqZ/QIfZfI
nwvU5JAgxjVkEOH3hav+gi41zxCvu2qMFNNIFYDq9w68tEX/VXcIOs/MjbnKSXRn
GvHv7ja3KWZ8vevYfjtK7+fp2qd2o7FWyU9weZA6mMFjJ4JOCxiugcJLMuWVYN+J
T7jNd/XfIsLO5SSg5ycUOgoJtBjlzPOnylib0ZesIkOAuBikP869jj+eZ1eSXH7k
+UCuvRECgYEA4BFV1+lMbSGwmU8wCyX+nUgDcUYAEkQQA45rvcV+5VYRC6eBh7AY
ZMNZ0Acy9ixVkvDW1r1L6+oXL8ckC99lY0j54bUMJVb5KzX+x+286Ls4aSnU0jZu
EBURYTDKj1MZMSngsdT+DkcWRu0wfrFuGf2S2I/JFi8Qzfvt+DO6OkcCgYEA3AbR
KPOMhsudWGFljWSeeqxnuUnIHuJjuXQIuJNVQAgPHuRkFXnxcYcjQR7zDifGC6Uh
zWqNvV3KKic87w8lpv4Ol5I0rDz7GHfyVWpsHUDrUv8xByUW9hDHevNygR0ow8TD
Ci/3OwUcbMKvaYRmtVVhnHhrMOaDetSDiWoEfbkCgYEAvGF2tab8TUgkzFAKSWUx
MpSH6GT8zF6SxBqqNItli3SXsh+rRCPl7llbGg3jZ5qQe5CmXzYZLYfK5K1dfenc
uONyyrNHOeFsbUrhIL+csmItJCU6O13tnPHJgdfehS9NH8tgkMJMsj5L2Wey/OE5
evp4yj/gxRje3P8w7Bq5OCMCgYAWdPeqJ1mDdIrFt4Mm8EsgmDIp/jbXuCGXjxlI
xXjhBTGN5J+2dXDINpPJlMAYBMU48QzHK4X1+vmkXcbhW3lrVujkXX7UyZCTScLJ
JwiL39Fk8jjt0sJKMSI7EVfxh6leedmyU3z3YCkrjJ9ctK/K+EDzOHMwVYa75a7b
Op/kuQKBgGwv8aQY/goTAXc1gKX5iCKRPDxiHeVQsFb2QOWMy7aQJmpHdGoP/cjY
Wq+f4tcwMfm4WBaPgvL+Z/N985vWbOLYyJMXwhrkoUn/uLZyZ808vjql2mqh1K2+
K5rXb8IrAUJbaTV00yzKwdR0AoPfkq48Pi4nePjLH6l1u6XekWdM
-----END RSA PRIVATE KEY-----";

	private static $testPublic = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwJTdP46DSLmKhKam8IMl
A8mfUFTVSocoKQAToQV4f2eTEnHvT2PchDwddHUj0m6tk71+SM+ZvD3R4YlAAPjz
m3dcS5/1AwZ/Ddnr/yKIJlP28BVzYzrNJtluh4igAuwueG4bC8aCK5R1DaptEMYH
D7FKlcYVCcBSwgThg7h8a0kkHHGc9b6s+Czr2zd2ntGjGUmD7XLfOuK8yEWRVqJO
YEdELfHnyZ3OlvQfYaIid69grlOgHlYKjfd/JlzfSSgwkUmPs5A8+tRX92tWdfko
75uItOt79UZJ8xTAaj32AKrDYwEg53k+jJ4Piz+oZWqNT/5ZZ7tSTtBu1JmonyTI
TwIDAQAB
-----END PUBLIC KEY-----";

	/**
	 * @return void
	 */
	public function test_dkim_key_private() {
		$key  = new DkimKey(self::$testPrivate);
		$private = $key->getPrivateKey();
		$this->assertTrue(is_resource($private));
	}

	/**
	 * @return void
	 */
	public function test_dkim_key_public() {
		$key  = new DkimKey(self::$testPrivate);
		$public = $key->getPublicKey();
		$this->assertTrue(is_resource($public));
		$this->assertTrue($key->verifyPublicKey(self::$testPublic));
	}
}
