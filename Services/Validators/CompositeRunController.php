<?php

namespace Services\Validators;

use Services\Validators\Interfaces\RunControllerInterface;

/**
 * Class CompositeRunController
 * @package Services\Cron
 */
class CompositeRunController implements RunControllerInterface
{
    /**
     * @var RunControllerInterface[]
     */
    private $runControllers = [];

    /**
     * @var string
     */
    private $stopReason = '';

    /**
     * @param RunControllerInterface $runController
     * @return $this
     */
    public function addRunController(RunControllerInterface $runController)
    {
        $this->runControllers[] = $runController;
        return $this;
    }

    /**
     * @return boolean
     */
    public function stopRun()
    {
        foreach ($this->runControllers as $runController) {
            if ($runController->stopRun()) {
                $this->stopReason = $runController->getStopReason();
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getStopReason()
    {
        return $this->stopReason;
    }
}
