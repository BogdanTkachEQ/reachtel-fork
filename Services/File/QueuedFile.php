<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\File;

use InvalidArgumentException;
use Models\Entities\QueueItem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class QueuedFile
{

    /**
     * @var QueueItem
     */
    private $item;

    /**
     * @return QueueItem
     */
    public function getItem()
    {
        return $this->item;
    }

    public function __construct(QueueItem $item)
    {
        if (!$item || !$item->getId()) {
            throw new InvalidArgumentException("The queue must have a valid id");
        }
        $this->item = $item;
    }

    public function getQueuedFilesPath()
    {
        if (!FILEUPLOAD_LOCATION) {
            throw new InvalidArgumentException("The config value FILEUPLOAD_LOCATION is not set!");
        }
        return SAVE_LOCATION
            . DIRECTORY_SEPARATOR
            . FILEUPLOAD_LOCATION
            . DIRECTORY_SEPARATOR
            . "queue"
            . DIRECTORY_SEPARATOR;
    }

    public function deleteFile(Filesystem $fs)
    {
        $fs->remove($this->getQueuedFilesPath());
    }
}
