<?php

namespace Services\DataRetention;

use Services\ConfigReader;
use Services\Exceptions\DataRetentionPolicyException;

abstract class AbstractPolicy
{
    /** @var ConfigReader */
    protected $config = null;

    /** @var integer */
    protected $groupId = null;

    /**
     * @param ConfigReader $config
     */
    public function __construct($groupId, ConfigReader $config = null)
    {
        if (!api_groups_checkidexists($groupId)) {
            throw new DataRetentionPolicyException(
                "Data retention: Group id {$groupId} does not exists"
            );
        }

        if (!$config) {
            $config = ConfigReader::getInstance()->getConfig(ConfigReader::DATA_RETENTION_CONFIG_TYPE);
        }

        $this->config = $config;
        $this->groupId = $groupId;
    }
}
