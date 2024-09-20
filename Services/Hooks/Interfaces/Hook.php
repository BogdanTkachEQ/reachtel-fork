<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Hooks\Interfaces;

interface Hook
{
    public function run();

    public function hasRun();

    public function runWasSuccess();

    public function getErrors();

    public function getName();
}
