<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JsonSerializable;
use Services\Queue\QueueProcessTypeEnum;

class QueueItem implements JsonSerializable
{

    /**
     * @var $id integer
     */
    private $id;

    /**
     * @var $processType QueueProcessTypeEnum
     */
    private $processType;

    /**
     * @var $campaignId integer
     */
    private $campaignId;

    /**
     * @var $userId integer
     */
    private $userId;

    /**
     * @var $priority integer
     */
    private $priority = 0;

    /**
     * @var $isRunning boolean
     */
    private $isRunning = false;

    /**
     * @var int
     */
    private $version = 0;
    /**
     * @var $canRun bool
     */
    private $canRun = false;
    /**
     * @var $hasRun boolean
     */
    private $hasRun = false;
    /**
     * @var $ranAt DateTime
     */
    private $ranAt;
    /**
     * @var $createdAt DateTime
     */
    private $createdAt;
    /**
     * @var $completedAt DateTime
     */
    private $completedAt;
    /**
     * @var $returnCode integer
     */
    private $returnCode;
    /**
     * @var $returnText string
     */
    private $returnText;
    /**
     * @var
     */
    private $data;
    /** @var Collection queueFile[] */
    private $queueFiles;

    public function __construct()
    {
        $this->queueFiles = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     * @return QueueItem
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCanRun()
    {
        return $this->canRun;
    }

    /**
     * @param bool $canRun
     * @return QueueItem
     */
    public function setCanRun($canRun)
    {
        $this->canRun = $canRun;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getQueueFiles()
    {
        return $this->queueFiles;
    }

    /**
     * @param Collection $queueFiles
     * @return QueueItem
     */
    public function setQueueFiles($queueFiles)
    {
        $this->queueFiles = $queueFiles;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return QueueProcessTypeEnum
     */
    public function getProcessType()
    {
        return $this->processType;
    }

    /**
     * @param QueueProcessTypeEnum $processType
     */
    public function setProcessType(QueueProcessTypeEnum $processType)
    {
        $this->processType = $processType;
        return $this;
    }

    /**
     * @return int
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param int $campaignId
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->isRunning;
    }

    /**
     * @param bool $isRunning
     */
    public function setIsRunning($isRunning)
    {
        $this->isRunning = $isRunning;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasRun()
    {
        return $this->hasRun;
    }

    /**
     * @param bool $hasRun
     */
    public function setHasRun($hasRun)
    {
        $this->hasRun = $hasRun;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRanAt()
    {
        return $this->ranAt;
    }

    /**
     * @param DateTime $ranAt
     */
    public function setRanAt($ranAt)
    {
        $this->ranAt = $ranAt;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getReturnCode()
    {
        return $this->returnCode;
    }

    /**
     * @param int $returnCode
     */
    public function setReturnCode($returnCode)
    {
        $this->returnCode = $returnCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnText()
    {
        return $this->returnText;
    }

    /**
     * @param string $returnText
     */
    public function setReturnText($returnText)
    {
        $this->returnText = $returnText;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    /**
     * @param DateTime $completedAt
     */
    public function setCompletedAt($completedAt)
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    /**
     * @return string
     */
    public function deriveFriendlyStatus()
    {
        $status = "";
        if ($this->isRunning) {
            $status = "Processing";
        } elseif ($this->canRun && !$this->hasRun) {
            $status = "Waiting to process";
        } elseif ($this->hasRun && $this->returnCode > 0) {
            $status = "Complete";
        } elseif ($this->returnCode < 0) {
            $status = "Error";
        }
        return $status;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->id,
            "canRun" => $this->canRun,
            "hasRun" => $this->hasRun,
            "completedAt" => $this->completedAt,
            "createdAt" => $this->createdAt,
            "ranAt" => $this->ranAt,
            "campaignId" => $this->campaignId,
            "userId" => $this->userId,
            "returnCode" => $this->returnCode,
            "returnText" => $this->returnText,
            "data" => $this->data,
            "isRunning" => $this->isRunning,
            "priority" => $this->priority,
            "status" => $this->deriveFriendlyStatus(),
            "messages" => json_decode($this->data),
            "files" => $this->getQueueFiles()->count()
        ];
    }

}