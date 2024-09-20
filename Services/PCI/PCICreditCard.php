<?php
/**
 * @author kevin.ohayon@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\PCI;

use Services\ConfigReader;

/**
 * Class PCICreditCard
 *
 * Based on inacho/php-credit-card-validator and pear/Validate_Finance_CreditCard
 *
 * @see https://github.com/inacho/php-credit-card-validator/blob/master/src/CreditCard.php#L18
 * @see https://github.com/pear/Validate_Finance_CreditCard/blob/master/Validate/Finance/CreditCard.php#L159
 */
class PCICreditCard
{
    /** @var self */
    private static $instance;

    /** @var array */
    private $config;

    /**
     * @param ConfigReader $config For test purpose only.
     * @return self
     */
    public static function getInstance(ConfigReader $config = null)
    {
        if (null === static::$instance) {
            static::$instance = new self(
                $config ?: ConfigReader::getInstance()
            );
        }

        return static::$instance;
    }

    /**
     * Validate a credit card
     *
     * @param string $string
     * @param mixed  $whitelist
     * @return false|string
     */
    public function validate($string, $whitelist = false)
    {
        // Strip non-numeric characters
        $number = preg_replace('/[^\d]+/', '', $string);

        // whitelist as array
        if ($whitelist) {
            $whitelist = array_map('strtolower', (array) $whitelist);
        }

        foreach ($this->config['cards'] as $type => $card) {
            if ($whitelist && in_array($type, $whitelist)) {
                continue;
            }

            // valid pattern
            if (preg_match($card['pattern'], $number)) {
                // valid length
                foreach ($card['length'] as $length) {
                    if (strlen($number) == $length
                        && (!$card['luhn'] || ($card['luhn'] && $this->luhnCheck($number)))
                        ) {
                        return $type;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string $number
     * @return boolean
     */
    private static function luhnCheck($number)
    {
        $checksum = 0;
        for ($i=(2-(strlen($number) % 2)); $i<=strlen($number); $i+=2) {
            $checksum += (int) ($number{$i-1});
        }
        // Analyze odd digits in even length strings or even digits in odd length strings.
        for ($i=(strlen($number)% 2) + 1; $i<strlen($number); $i+=2) {
            $digit = (int) ($number{$i-1}) * 2;
            if ($digit < 10) {
                $checksum += $digit;
            } else {
                $checksum += ($digit-9);
            }
        }
        if (($checksum % 10) == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param ConfigReader $config
     * @return void
     */
    private function __construct(ConfigReader $config)
    {
        $this->config = $config->getConfig(ConfigReader::PCI_CONFIG_TYPE);
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * @return void
     */
    private function __wakeup()
    {
    }
}
