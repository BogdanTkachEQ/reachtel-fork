<?php
/**
 * @author kevin.ohayon@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\PCI;

use Services\ConfigReader;

/**
 * Class PCIRecorder
 *
 * This class use the singleton design pattern to record any PCI data
 * during multiple function calls.
 */
final class PCIRecorder
{
    /** @var string */
    const DEFAULT_NOTIFICATION_EMAIL = 'ReachTEL Support <support@ReachTEL.com.au>';

    /** @var integer */
    const STARTED_MANUALLY = 1;

    /** @var integer */
    const STARTED_AUTO = 2;

    /** @var string */
    const KEY_MERGE_DATA = 'merge_data';

    /** @var string */
    const KEY_TARGETKEY = 'targetkey';

    /** @var string */
    const HYDRATE_TARGETS_FILE_UPLOAD = 'targets_fileupload';

    /** @var self */
    private static $instance;

    /**
     * Started can have 3 values:
     *  - false
     *  - STARTED_MANUALLY
     *  - STARTED_AUTO
     *
     * @var boolean
     */
    private $started = false;

    /** @var array */
    private $records = [];

    /** @var array */
    private $config;

    /**
     * @return $this
     */
    public static function getInstance(ConfigReader $config = null)
    {
        if (null === static::$instance) {
            static::$instance = new self($config ?: ConfigReader::getInstance());

            // check if should be auto started
            if (static::$instance->shouldBeAutoStarted()) {
                // set auto start
                static::$instance->start(true);

                // notify on shutdown
                register_shutdown_function([static::$instance, 'notify']);
            }
        }

        return static::$instance;
    }

    /**
     * Destruct any instance
     */
    public static function destruct()
    {
        static::$instance = null;
    }

    /**
     * Start the recorder
     *
     * @param boolean $auto
     *
     * @return $this
     */
    public function start($auto = false)
    {
        $this->started = (true === $auto ? self::STARTED_AUTO : self::STARTED_MANUALLY);

        return $this;
    }

    /**
     * Stop the recorder
     *
     * @return $this
     */
    public function stop()
    {
        $this->started = false;

        return $this;
    }

    /**
     * Is recorder started
     *
     * @return boolean
     */
    public function isStarted()
    {
        return (bool) $this->started;
    }

    /**
     * Is recorder auto started
     *
     * @return boolean
     */
    public function isAutoStarted()
    {
        return self::STARTED_AUTO === $this->started;
    }

    /**
     * @param integer $campaignId
     * @param string  $targetKey
     * @return $this
     */
    public function addTargetKey($campaignId, $targetKey)
    {
        if ($this->isStarted()) {
            // unique targetkey
            if (!isset($this->records[$campaignId][self::KEY_TARGETKEY])
                || !in_array($targetKey, $this->records[$campaignId][self::KEY_TARGETKEY])) {
                $this->records[$campaignId][self::KEY_TARGETKEY][] = $targetKey;
            }
        }

        return $this;
    }

    /**
     * @param integer $campaignId
     * @param string  $targetKey
     * @return $this
     */
    public function addMergeData($campaignId, $targetKey, $element, $value)
    {
        if ($this->isStarted()) {
            $this->records[$campaignId][self::KEY_MERGE_DATA][$targetKey][$element] = $value;
        }

        return $this;
    }

    /**
     * Reset records
     *
     * @return $this
     */
    public function resetRecords()
    {
        $this->records = [];

        return $this;
    }

    /**
     * @param string $hydrator
     * @return array
     */
    public function getRecords($hydrator = null)
    {
        if ($this->isStarted()) {
            switch ($hydrator) {
                case self::HYDRATE_TARGETS_FILE_UPLOAD:
                    $records = [];
                    foreach ($this->records as $campaignId => $record) {
                        $records[$campaignId][self::KEY_TARGETKEY] = isset($record[self::KEY_TARGETKEY])
                            ? $record[self::KEY_TARGETKEY] : [];
                        $records[$campaignId][self::KEY_MERGE_DATA] = isset($record[self::KEY_MERGE_DATA])
                            ? array_keys($record[self::KEY_MERGE_DATA]) : [];
                    }

                    return $records;
                    break;
            }
        }

        return $this->records;
    }

    /**
     * Notification email for each groups
     *
     * @return $this
     */
    public function notify()
    {
        if ($this->isStarted() && $this->records) {
            // group records per user group
            $grouped = [];

            // campaign ids in parameters
            $parameters = array_keys($this->records);
            $sql = "SELECT `id`, `item`, `value` FROM `key_store`
					WHERE `id` IN (" . implode(', ', array_fill(0, count($parameters), '?')) . ")
						AND `type` = ?
						AND `item` IN (?, ?);";
            $parameters = array_merge(
                $parameters,
                [
                    KEYSTORE_TYPE_CAMPAIGNS,
                    CAMPAIGN_SETTING_NAME,
                    CAMPAIGN_SETTING_GROUP_OWNER,
                ]
            );
            $rs = api_db_query_read($sql, $parameters);

            if ($rs) {
                // create a group-campaign map
                $map = [];
                foreach ($rs->GetArray() as $row) {
                    $map[$row['id']][$row['item']] = $row['value'];
                    if (CAMPAIGN_SETTING_GROUP_OWNER === $row['item']) {
                        $email = api_groups_setting_getsingle(
                            $row['value'],
                            USER_GROUP_SETTING_NOTIFICATION_SFTP_EMAIL
                        );

                        // default email
                        $map[$row['id']]['email'] = $email ?: self::DEFAULT_NOTIFICATION_EMAIL;
                    }
                }

                // group per group > campaigns
                foreach ($this->records as $campaignId => $record) {
                    if (isset($map[$campaignId]['email']) && $map[$campaignId]['email']) {
                        $groupId = $map[$campaignId]['groupowner'];
                        if (!isset($grouped[$groupId])) {
                            $grouped[$groupId] = [
                                'email' => $map[$campaignId]['email'],
                            ];
                        }
                        $grouped[$groupId]['campaigns'][$campaignId]['name'] = $map[$campaignId]['name'];
                        $grouped[$groupId]['campaigns'][$campaignId]['records'][] = $record;
                    }
                }

                // email for each groups
                foreach ($grouped as $groupId => $data) {
                    $campaignList = '';
                    foreach ($data['campaigns'] as $campaignId => $campaign) {
                        $campaignList .= " * {$campaign['name']}:\n";
                        foreach ($campaign['records'] as $record) {
                            if (isset($record[self::KEY_TARGETKEY]) && $record[self::KEY_TARGETKEY]) {
                                $campaignList .= sprintf(
                                    "	- %d targetkey(s)\n",
                                    count($record[self::KEY_TARGETKEY])
                                );
                            }
                            if (isset($record[self::KEY_MERGE_DATA]) && $record[self::KEY_MERGE_DATA]) {
                                $columns = [];
                                $count = 0;
                                foreach ($record[self::KEY_MERGE_DATA] as $mergeData) {
                                    $columns = array_merge($columns, array_keys($mergeData));
                                    $count += count($mergeData);
                                }

                                $campaignList .= sprintf(
                                    "	- %1\$d merge data record%2\$s in column%2\$s '%3\$s'\n",
                                    $count,
                                    ($count > 1 ? 's' : ''),
                                    implode("', '", array_unique($columns))
                                );
                            }
                        }
                    }

                    $content = str_replace(
                        ['%date%', '%timezone%', '%pci_data%'],
                        [
                            date('d/m/Y \a\t G:i'),
                            date_default_timezone_get(),
                            $campaignList
                        ],
                        $this->config['email']
                    );

                    $email = [
                        'to' => $data['email'],
                        'from' => 'ReachTEL Support <support@ReachTEL.com.au>',
                        'subject' => '[ReachTEL] PCI data detected',
                        'content' => $content,
                    ];

                    // cc default email if group email is set
                    if (self::DEFAULT_NOTIFICATION_EMAIL !== $data['email']) {
                        $email['cc'] = self::DEFAULT_NOTIFICATION_EMAIL;
                    }

                    api_email_template($email);
                }
            }
        }

        return $this;
    }

    /**
     * Auto start for specific rules
     *
     * @return boolean
     */
    private function shouldBeAutoStarted()
    {
        $isCli = 'cli' === php_sapi_name();

        // CLI scripts
        if ($isCli && isset($_SERVER['argv'][0])) {
            $file = $_SERVER['argv'][0];

            // autoload script auto start for PCI notifications
            // @see REACHTEL-687
            if (preg_match('#scripts/autoload/[\w\-\_]+.php$#', $file, $match)) {
                api_error_audit("PCI_RECORDER", "PCI recorder autoload script auto-start enabled");
                return true;
            }
        }

        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct($config)
    {
        $this->config = $config->getConfig(ConfigReader::PCI_CONFIG_TYPE);
    }

    /**
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * @codeCoverageIgnore
     */
    private function __wakeup()
    {
    }
}
