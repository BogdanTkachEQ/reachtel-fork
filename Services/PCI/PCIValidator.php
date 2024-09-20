<?php
/**
 * @author kevin.ohayon@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\PCI;

/**
 * Class PCIValidator
 */
class PCIValidator
{
    /**
     * Regexp to match PAN data.
     *  - Must start with 4 digits with optional separator
     *  - Contains digits or separators
     *    * min length=5 matches 12 digits maestro cards
     *    * max length=20 matches 19 digits maestro + unionpay cards
     *  - Ends with 3 digits
     *
     * @var string
     * @see ./Services/Config/pci.config.yml
     */
    const PAN_REGEXP = '\d{4}[ \-\/]?[\d \-\/]{5,20}\d{3}';

    /**
     * @var string
     */
    const TAG_NAME_WHITELIST = 'pci-cards-whitelist';

    /** @var array */
    private $whitelists;

    /**
     * @param array $whitelist
     * @return $this
     */
    public function setPANWhitelist($whitelist)
    {
        $this->whitelists[PCICreditCard::class] = (array) $whitelist;

        return $this;
    }

    /**
     * Mask credit card
     *
     * @param string  $string
     * @param boolean $stopOnFirstMatch
     * @return false|array
     *
     * @see https://baymard.com/checkout-usability/credit-card-patterns
     */
    public function matchAllPANData($string, $stopOnFirstMatch = false)
    {
        /*
         * before match: space, colon
         * after match: space, dot (end of sentence)
         */
        if (preg_match_all(sprintf('/(^|\s|\:)(%s)(\.|\s|$)/', self::PAN_REGEXP), $string, $matches)) {
            $cards = [];
            foreach ($matches[2] as $match) {
                $match = trim($match);
                if (self::isPANData($match)) {
                    if ($stopOnFirstMatch) {
                        return $match;
                    }

                    $cards[] = $match;
                }
            }

            if ($cards) {
                return $cards;
            }
        }

        return false;
    }

    /**
     * Check if data is a valid credit card
     *
     * @param string $data
     * @return boolean
     */
    public function isPANData($data)
    {
        if ($data) {
            $data = trim($data);

            // ** AVOID PAN DATA **
            //  - checks CC contains digits, dash or spaces
            //  - checks length between min and max (for performance)
            // @see Inacho\CreditCard::$cards
            if (preg_match(sprintf('/^%s$/', self::PAN_REGEXP), $data)) {
                // removes non-digits
                $digits = preg_replace('/[^\d]+/', '', $data);
                $length = strlen($digits);
                if (12 <= $length && 19 >= $length) {
                    $whitelist = false;
                    if (isset($this->whitelists[PCICreditCard::class])) {
                        $whitelist = $this->whitelists[PCICreditCard::class];
                    }

                    // this function does include the Luhn algo check
                    // @see https://en.wikipedia.org/wiki/Luhn_algorithm
                    if (PCICreditCard::getInstance()->validate($digits, $whitelist)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Mask credit card
     *
     * @param string  $data
     * @param boolean $unique
     * @return string
     */
    public function maskPANData($card, $unique = false)
    {
        $length = strlen($card);

        if ($length > 4) {
            $masked = str_repeat('X', $length - 4) . substr($card, -4);
            if ($unique) {
                $masked .= '-' . api_misc_crypt_safe($card);
            }

            return $masked;
        }

        return $card;
    }
}
