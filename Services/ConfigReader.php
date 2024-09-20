<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigReader
 */
class ConfigReader
{
    const CONFIG_DIRECTORY = __DIR__ . '/Config/';
    const CONFIG_EXTENSION = '.config.yml';

    const ACTIVITY_LOGGER_CONFIG_TYPE = 'activitylogger';
    const BSV_IMPORTER_CONFIG_TYPE = 'bsvimporter';
    const DATA_RETENTION_CONFIG_TYPE = 'dataretentionpolicy';
    const SMS_RECEIPT_RECONCILER_CONFIG_TYPE = 'smsreceiptreconciler';
    const PCI_CONFIG_TYPE = 'pci';

    /** @var ConfigReader */
    private static $instance;

    /** @var array */
    private $config;

    /** @var Parser */
    private $yamlParser;

    /**
     * ConfigReader constructor.
     *
     * @param Parser $yamlParser For testing purpose only.
     */
    private function __construct(Parser $yamlParser = null)
    {
        $this->config = [];
        $this->yamlParser = $yamlParser ?: new Parser();
    }

    /**
     * @param Parser $yamlParser For testing purpose only.
     * @return self
     */
    public static function getInstance(Parser $yamlParser = null)
    {
        if ($yamlParser || !static::$instance) {
            static::$instance = new self($yamlParser);
        }

        return static::$instance;
    }

    /**
     * @param string $configType
     * @return array
     */
    public function getConfig($configType)
    {
        if (!isset($this->config[$configType])) {
            $this->config[$configType] = $this->yamlParser->parseFile(
                $this->getConfigFile($configType),
                Yaml::PARSE_CONSTANT
            );
        }

        return $this->config[$configType];
    }

    /**
     * @param string $configType
     * @return string
     */
    private function getConfigFile($configType)
    {
        return self::CONFIG_DIRECTORY . $configType . self::CONFIG_EXTENSION;
    }
}
