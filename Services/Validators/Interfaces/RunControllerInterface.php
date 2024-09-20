<?php

namespace Services\Validators\Interfaces;

/**
 * Interface RunControllerInterface
 */
interface RunControllerInterface
{
    /**
     * @return boolean
     */
    public function stopRun();

    /**
     * @return string
     */
    public function getStopReason();
}
