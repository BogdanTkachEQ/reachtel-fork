<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Hooks;

use Exception;
use Services\Hooks\Interfaces\Hookable;
use Services\Hooks\Interfaces\PostHook;
use Services\Hooks\Interfaces\PreHook;

/**
 * A generic hooks collection class
 *
 * Allows Pre and Post implementations of hooks to be setup and run.
 *
 * Class Hooks
 * @package Services\Hooks
 */
class Hooks implements Hookable
{

    /**
     * @var array
     */
    private $preHooks = [];
    /**
     * @var array
     */
    private $postHooks = [];

    /**
     * @return bool
     */
    public function hasHooks()
    {
        return !empty($this->preHooks) || !empty($this->postHooks);
    }

    /**
     * @return bool
     */
    public function hasPreHooks()
    {
        return !empty($this->preHooks);
    }

    /**
     * @return bool
     */
    public function hasPostHooks()
    {
        return !empty($this->postHooks);
    }

    /**
     * @return void
     */
    public function runPreHooks()
    {
        if ($this->hasPreHooks()) {
            foreach ($this->preHooks as $name => $hook) {
                try {
                    $hook->run();
                } catch (Exception $e) {
                    api_error_raise($e->getMessage() . "hook: {$hook->getName()}");
                    continue;
                }
            }
        }
    }

    /**
     * @return void
     */
    public function runPostHooks()
    {
        if ($this->hasPostHooks()) {
            foreach ($this->postHooks as $name => $hook) {
                try {
                    $hook->run();
                } catch (Exception $e) {
                    api_error_raise($e->getMessage() . "hook: {$hook->getName()}");
                    continue;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        foreach ($this->getErrors() as $type => $errors) {
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    if ($error) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns an array of all of the current hooks error messages
     * ['pre'][hookname] => error
     * ['post'][hookname] => error
     *
     * @return array
     */
    public function getErrors()
    {
        $statuses = [];
        foreach ($this->preHooks as $hook) {
            $statuses["pre"][] = $hook->getErrors();
        }
        foreach ($this->postHooks as $hook) {
            $statuses["post"][] = $hook->getErrors();
        }
        return $statuses;
    }

    /**
     * Returns an array of all of the current hooks statuses
     * ['pre'][hookname] => status
     * ['post'][hookname] => status
     *
     * @return array
     */
    public function getHooksStatus()
    {
        $statuses = [];
        foreach ($this->preHooks as $hook) {
            $statuses["pre"][] = $hook->runWasSuccess();
        }
        foreach ($this->postHooks as $hook) {
            $statuses["post"][] = $hook->runWasSuccess();
        }
        return $statuses;
    }

    /**
     * @param PostHook $hook
     */
    public function addPostHook(PostHook $hook)
    {
        $this->postHooks[] = $hook;
    }

    /**
     * @param PreHook $hook
     */
    public function addPreHook(PreHook $hook)
    {
        $this->preHooks[] = $hook;
    }
}
