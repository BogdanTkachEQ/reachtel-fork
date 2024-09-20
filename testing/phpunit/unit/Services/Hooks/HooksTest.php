<?php
/**
 * @author phillip.berry@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Hooks;

use Phake;
use Services\Hooks\Interfaces\PostHook;
use Services\Hooks\Interfaces\PreHook;
use testing\AbstractPhpunitTest;

/**
 * Class HooksTest
 */
class HooksTest extends AbstractPhpunitTest {

	/**
	 * @return void
	 */
	public function testRunPostHooks() {
		$postHook = Phake::mock(PostHook::class);
		$postHook2 = Phake::mock(PostHook::class);
		$hook = new Hooks();
		$hook->addPostHook($postHook);
		$hook->addPostHook($postHook2);
		$hook->runPostHooks();
		Phake::verify($postHook)->run();
		Phake::verify($postHook2)->run();
	}

	/**
	 * @return void
	 */
	public function testHasHooks() {
		$hook = new Hooks();
		$hook->addPreHook(Phake::mock(PreHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$this->assertTrue($hook->hasHooks());
	}

	/**
	 * @return void
	 */
	public function testAddPostHook() {
		$hook = new Hooks();
		$hook->addPreHook(Phake::mock(PreHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$this->assertTrue($hook->hasPostHooks());
	}

	/**
	 * @return void
	 */
	public function testAddPreHook() {
		$hook = new Hooks();
		$hook->addPreHook(Phake::mock(PreHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$this->assertTrue($hook->hasPreHooks());
	}

	/**
	 * @return void
	 */
	public function testHasPreHooks() {
		$hook = new Hooks();
		$hook->addPreHook(Phake::mock(PreHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$this->assertTrue($hook->hasPreHooks());
	}

	/**
	 * @return void
	 */
	public function testRunPreHooks() {
		$preHook = Phake::mock(PreHook::class);
		$hook = new Hooks();
		$hook->addPreHook($preHook);
		$hook->runPreHooks();
		Phake::verify($preHook)->run();
	}

	/**
	 * @return void
	 */
	public function testHasPostHooks() {
		$hook = new Hooks();
		$hook->addPreHook(Phake::mock(PreHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$hook->addPostHook(Phake::mock(PostHook::class));
		$this->assertTrue($hook->hasPostHooks());
	}

	/**
	 * @return void
	 */
	public function testHasPostHooksHasErrors() {
		$preHook = Phake::mock(PreHook::class);
		Phake::when($preHook)->runWasSuccess()->thenReturn(true);
		Phake::when($preHook)->getErrors()->thenReturn("errors");
		$postHook = Phake::mock(PostHook::class);
		Phake::when($postHook)->runWasSuccess()->thenReturn(false);
		Phake::when($postHook)->getErrors()->thenReturn("errors");
		$hook = new Hooks();
		$hook->addPreHook($preHook);
		$hook->addPostHook($postHook);
		$hook->runPreHooks();
		$hook->runPostHooks();
		$this->assertTrue($hook->hasErrors());
	}

	/**
	 * @return void
	 */
	public function testHasPostHooksHasNoErrors() {
		$preHook = Phake::mock(PreHook::class);
		Phake::when($preHook)->runWasSuccess()->thenReturn(true);
		Phake::when($preHook)->getErrors()->thenReturn(false);
		$postHook = Phake::mock(PostHook::class);
		Phake::when($postHook)->runWasSuccess()->thenReturn(true);
		Phake::when($postHook)->getErrors()->thenReturn(false);
		$hook = new Hooks();
		$hook->addPreHook($preHook);
		$hook->addPostHook($postHook);
		$hook->runPreHooks();
		$this->assertFalse($hook->hasErrors());
	}

	/**
	 * @return void
	 */
	public function testGetHooksStatus() {
		$preHook = Phake::mock(PreHook::class);
		Phake::when($preHook)->runWasSuccess()->thenReturn(true);
		$postHook = Phake::mock(PostHook::class);
		Phake::when($postHook)->runWasSuccess()->thenReturn(false);
		$hook = new Hooks();
		$hook->addPreHook($preHook);
		$hook->addPostHook($postHook);
		$hook->runPreHooks();
		$statuses = $hook->getHooksStatus();
		Phake::verify($preHook)->runWasSuccess();
		Phake::verify($postHook)->runWasSuccess();
		$this->assertArrayHasKey("pre", $statuses);
		$this->assertArrayHasKey("post", $statuses);
		$this->assertTrue($statuses['pre'][0]);
		$this->assertFalse($statuses['post'][0]);
	}
}
