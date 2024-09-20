<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Plotter;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Class PlotterDataExtractionParams
 */
class PlotterDataExtractionParams {

    const RETURN_MOBILES_KEY = 'returnmobiles';
    const POST_CODE_KEY = 'postcode';
    const EXCLUDE_POST_CODE_KEY = 'excludepostcode';
    const PHONE_KEY = 'phone';
    const AGES_TO_RETURN_KEY = 'agestoreturn';
    const CAMPAIGN_ID_KEY = 'campaignid';
    const NOTIFICATION_EMAILS_KEY = 'notificationemails';
    const USER_ID_KEY = 'userid';

    /** @var boolean */
    private $returnMobiles = false;

    /** @var array */
    private $postCodes = [];

    /** @var array */
    private $excludePostCodes;

    /** @var string */
    private $phone;

    /** @var array */
    private $agesToReturn = [];

    /** @var array */
    protected static $resolvedOptions = [];

    /** @var OptionsResolver */
    protected static $optionsResolver;

    /**
     * @param array $data
     * @return PlotterDataExtractionParams
     */
    public static function fromArray(array $data) {
        $resolver = static::getOptionsResolver();
        $resolver->setDefined(array_keys($data));
        $resolver->setDefaults(static::getDefaultData());

        foreach ([static::AGES_TO_RETURN_KEY, static::POST_CODE_KEY, static::EXCLUDE_POST_CODE_KEY] as $param) {
            $resolver->setNormalizer($param, function (Options $options, $value) {
                return static::convertToArray($value);
            });
        }

        static::$resolvedOptions = $resolver->resolve($data);
        
        $extractionParams = new static();
        $extractionParams
            ->setReturnMobiles(static::$resolvedOptions[static::RETURN_MOBILES_KEY])
            ->setPhone(static::$resolvedOptions[static::PHONE_KEY])
            ->setAgesToReturn(static::$resolvedOptions[static::AGES_TO_RETURN_KEY])
            ->setPostCodes(static::$resolvedOptions[static::POST_CODE_KEY])
            ->setExcludePostCodes(static::$resolvedOptions[static::EXCLUDE_POST_CODE_KEY]);

        return $extractionParams;
    }

    /**
     * @return array
     */
    protected static function getDefaultData() {
        return [
            static::RETURN_MOBILES_KEY => false,
            static::PHONE_KEY => null,
            static::AGES_TO_RETURN_KEY => [],
            static::POST_CODE_KEY => [],
            static::EXCLUDE_POST_CODE_KEY => null
        ];
    }

    /**
     * @return OptionsResolver
     */
    protected static function getOptionsResolver() {
        if (!static::$optionsResolver) {
            static::$optionsResolver = new OptionsResolver();
        }

        return static::$optionsResolver;
    }

    /**
     * @param boolean $returnMobiles
     * @return PlotterDataExtractionParams
     */
    public function setReturnMobiles($returnMobiles) {
        $this->returnMobiles = (bool) $returnMobiles;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getReturnMobiles() {
        return $this->returnMobiles;
    }

    /**
     * @param array $postCodes
     * @return PlotterDataExtractionParams
     */
    public function setPostCodes(array $postCodes = []) {
        $this->postCodes = $this->processPostCodes($postCodes);
        return $this;
    }

    /**
     * @return array
     */
    public function getPostCodes() {
        return $this->postCodes;
    }

    /**
     * @param array $excludePostCodes
     * @return PlotterDataExtractionParams
     */
    public function setExcludePostCodes(array $excludePostCodes) {
        $this->excludePostCodes = $this->processPostCodes($excludePostCodes);
        return $this;
    }

    /**
     * @return array
     */
    public function getExcludePostCodes() {
        return $this->excludePostCodes;
    }

    /**
     * @param string $phone
     * @return PlotterDataExtractionParams
     */
    public function setPhone($phone) {
        if (is_numeric($phone)) {
            $this->phone = $phone;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * @param array $agesToReturn
     * @return PlotterDataExtractionParams
     */
    public function setAgesToReturn(array $agesToReturn = []) {
        $this->agesToReturn = $agesToReturn;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getAgesToReturn() {
        return $this->agesToReturn;
    }

    /**
     * @param mixed $data
     * @return array
     */
    protected static function convertToArray($data) {
        if (is_array($data)) {
            return $data;
        }

        return array_unique(array_map('trim', explode(',', $data)));
    }

    private function processPostCodes(array $postCodes) {
        $processedPostCodes = [];
        foreach ($postCodes as $postCode) {
            if(preg_match("/(\d+)\s*\-\s*(\d+)/", $postCode, $matches) && ($matches[1] < $matches[2])) {
                for($i = $matches[1]; $i <= $matches[2]; $i++) {
                    $processedPostCodes[] = (int)$i;
                }
            } else if (is_numeric($postCode)) {
                $processedPostCodes[] = $postCode;
            }
        }

        return $processedPostCodes;
    }
}
