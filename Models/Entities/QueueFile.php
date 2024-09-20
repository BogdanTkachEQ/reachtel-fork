<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Models\Entities;

use Symfony\Component\Filesystem\Exception\IOException;

class QueueFile
{

    /**
     * @var $id int
     */
    public $id;

    /**
     * @var $queueId int
     */
    public $queueId;
    /**
     * @var $fileName string
     */
    public $fileName;
    /**
     * @var $data resource
     */
    public $data;
    /**
     * @var $createdAt \DateTime
     */
    public $createdAt;

    /**
     * @var $queueItem QueueItem
     */
    public $queueItem;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getQueueId()
    {
        return $this->queueId;
    }

    /**
     * @param int $queueId
     * @return QueueFile
     */
    public function setQueueId($queueId)
    {
        $this->queueId = $queueId;
        return $this;
    }

    /**
     * @return QueueItem
     */
    public function getQueueItem()
    {
        return $this->queueItem;
    }

    /**
     * @param QueueItem $queueItem
     * @return QueueFile
     */
    public function setQueueItem($queueItem)
    {
        $this->queueItem = $queueItem;
        $this->setQueueId($queueItem->getId());
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
     * @return QueueFile
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return QueueFile
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return resource
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param resource $data
     * @return QueueFile
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return QueueFile
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @param $path
     */
    public function storeTmpFile($path)
    {
        if (is_resource($this->getData())) {
            rewind($this->getData());
        }
        if (!file_put_contents($path, $this->getData())) {
            throw new IOException("Could not create $path");
        }
        return $path;
    }

    /**
     * Doctrine stores blob files as a php resource, on clone we need to reconvert
     * the resource back into data
     */
    public function __clone()
    {
        if (is_resource($this->getData())) {
            $resource = $this->getData();
            rewind($resource);
            $this->setData(stream_get_contents($resource));
        }
    }

}
