<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Autoload;

use Services\Autoload\Interfaces\AutoloadStrategyInterface;
use Services\Exceptions\File\CryptoException;
use Services\File\Interfaces\DecryptorInterface;
use Services\Validators\Interfaces\RunControllerInterface;

/**
 * Class AutoloadContext
 */
class AutoloadContext
{
    const SFTP_HOSTNAME_KEY = 'hostname';
    const SFTP_USERNAME_KEY = 'username';
    const SFTP_PASSWORD_KEY = 'password';
    const SFTP_PATH_KEY = 'path';

    /**
     * @var AutoloadStrategyInterface
     */
    private $strategy;

    /**
     * @var array
     */
    private $sftpData;

    /**
     * @var string
     */
    private $failureNotificationEmail;

    /**
     * @var string
     */
    private $failureNotificationSubject;

    /**
     * @var AutoloadLogger
     */
    private $logger;

    /**
     * @var RunControllerInterface
     */
    private $runController;

    /** @var null|DecryptorInterface */
    private $decryptor;

    /**
     * AutoloadContext constructor.
     * @param AutoloadStrategyInterface $strategy
     * @param array                     $sftpData
     * @param RunControllerInterface    $runController
     * @param DecryptorInterface|null   $decryptor
     * @throws \Exception
     */
    public function __construct(
        AutoloadStrategyInterface $strategy,
        array $sftpData,
        RunControllerInterface $runController,
        DecryptorInterface $decryptor = null
    ) {
        if (array_diff(
            [
                static::SFTP_HOSTNAME_KEY,
                static::SFTP_USERNAME_KEY,
                static::SFTP_PASSWORD_KEY,
                static::SFTP_PATH_KEY
            ],
            array_keys($sftpData)
        )) {
            throw new \Exception('Sftp data is missing required keys.');
        }

        $this->strategy = $strategy;
        $this->sftpData = $sftpData;
        $this->logger = new AutoloadLogger();
        $this->strategy->setLogger($this->logger);
        $this->runController = $runController;
        $this->decryptor = $decryptor;
    }

    /**
     * @param string $email
     * @return AutoloadContext
     */
    public function setFailureNotificationEmail($email)
    {
        $this->failureNotificationEmail = $email;
        return $this;
    }

    /**
     * @param string $subject
     * @return AutoloadContext
     */
    public function setFailureNotificationSubject($subject)
    {
        $this->failureNotificationSubject = $subject;
        return $this;
    }

    /**
     * @return AutoloadLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $filename
     * @return boolean
     * @throws \Exception
     */
    public function process($filename)
    {
        if ($this->runController->stopRun()) {
            $this->addToLogs('Stopping execution: ' . $this->runController->getStopReason());
            return true;
        }

        if (!$this->failureNotificationEmail) {
            throw new \Exception('Failure notification email requires to be set.');
        }
        $file = $this->fetchFile($filename);

        if (!$file) {
            return false;
        }

        try {
            $processed = $this->strategy->processFile($file);
        } catch (\Exception $e) {
            $this->addToLogs($e->getMessage());
            $processed = false;
        }

        $this->addToLogs('Removing file ' . $file);
        if (!unlink($file)) {
            $this->addToLogs('Failed to remove file');
        }

        $this->addToLogs('Job done!!!');
        return $processed;
    }

    /**
     * @return string
     */
    public function flushLogs()
    {
        return $this->logger->flush();
    }

    /**
     * @param string $filename
     * @return null|string
     */
    protected function fetchFile($filename)
    {
        $this->addToLogs('Downloading file...');

        $file = '/tmp/' . $filename;
        $options = [
            'hostname' => $this->sftpData[static::SFTP_HOSTNAME_KEY],
            'username' => $this->sftpData[static::SFTP_USERNAME_KEY],
            'password' => $this->sftpData[static::SFTP_PASSWORD_KEY],
            'localfile' => $file,
            'remotefile' => $this->sftpData[static::SFTP_PATH_KEY] . $filename
        ];

        if (!api_misc_sftp_get($options)) {
            $email["to"]      = $this->failureNotificationEmail;
            $email["cc"]      = "ReachTEL Support <support@ReachTEL.com.au>";
            $email["from"]    = "ReachTEL Support <support@ReachTEL.com.au>";
            $email["subject"] = sprintf(
                "[ReachTEL] Auto-load error %s",
                ($this->failureNotificationSubject ?: '') . ' ' .$filename
            );
            $email["textcontent"] = "
                Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" .
                $filename .
                "\n\nThe auto-load process has failed. 
                Please advise ReachTEL Support if these files are expected at a later time.";
            $email["htmlcontent"] = "
                Hello,\n\nThe following file could not be downloaded from the specified server:\n\n" .
                $filename .
                "\n\nThe auto-load process has failed. 
                Please advise ReachTEL Support if these files are expected at a later time.
            ";

            api_email_template($email);

            $this->addToLogs("Failed to fetch file " . $filename);
            return null;
        }
        $this->addToLogs('OK');

        if (!is_null($this->decryptor)) {
            $this->addToLogs('Decrypting file...');
            try {
                $decrypted = $this->decryptor->setFile($file)->decrypt();
            } catch (CryptoException $exception) {
                $this->addToLogs($exception->getMessage());
                $this->addToLogs('Removing file ' . $file);
                if (!unlink($file)) {
                    $this->addToLogs('Failed to remove file');
                }
                return false;
            }
            file_put_contents($file, $decrypted);
            $this->addToLogs('File decrypted...');
        }

        return $file;
    }

    /**
     * @param string $log
     */
    private function addToLogs($log)
    {
        $this->logger->addLog($log);
    }
}
