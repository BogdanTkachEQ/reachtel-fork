<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Hooks\Interfaces;

interface Hookable
{
    public function hasHooks();

    public function hasPreHooks();

    public function hasPostHooks();

    public function runPreHooks();

    public function runPostHooks();

    public function getHooksStatus();

    public function addPostHook(PostHook $hook);

    public function addPreHook(PreHook $hook);
}
