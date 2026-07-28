<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Session\Session;
use Session\SessionStorage;

/**
 * In-memory session storage for testing flash data without native PHP sessions.
 */
class InMemorySessionStorage implements SessionStorage
{
    private array $data = [];
    private bool $started = false;

    public function start(): void
    {
        $this->started = true;
    }

    public function save(): void
    {
        // No-op for in-memory storage.
    }

    public function id(): string
    {
        return 'test-session-id';
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}

/**
 * Tests session flash data aging behavior.
 */
class SessionTest extends TestCase
{
    /**
     * Verifies that ageFlashData moves new flash keys to the old array.
     *
     * @return void
     */
    public function test_age_flash_data_moves_new_keys_to_old(): void
    {
        $storage = new InMemorySessionStorage();
        $session = new Session($storage);

        $storage->set(Session::FLASH_KEY, ['old' => [], 'new' => ['key1', 'key2']]);

        $session->ageFlashData();

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals(['key1', 'key2'], $flash['old']);
    }

    /**
     * Verifies that ageFlashData resets the new array to empty.
     *
     * @return void
     */
    public function test_age_flash_data_clears_new_array(): void
    {
        $storage = new InMemorySessionStorage();
        $session = new Session($storage);

        $storage->set(Session::FLASH_KEY, ['old' => [], 'new' => ['key1']]);

        $session->ageFlashData();

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals([], $flash['new']);
    }

    /**
     * Verifies that ageFlashData handles multiple flash keys correctly.
     *
     * @return void
     */
    public function test_age_flash_data_with_multiple_keys(): void
    {
        $storage = new InMemorySessionStorage();
        $session = new Session($storage);

        $storage->set(Session::FLASH_KEY, ['old' => ['old_key'], 'new' => ['a', 'b', 'c']]);

        $session->ageFlashData();

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals(['a', 'b', 'c'], $flash['old']);
        $this->assertEquals([], $flash['new']);
    }

    /**
     * Verifies the full flash lifecycle: flash a key then age it into old.
     *
     * @return void
     */
    public function test_flash_then_age_moves_key_to_old(): void
    {
        $storage = new InMemorySessionStorage();
        $session = new Session($storage);

        $session->flash('success', 'Record saved');

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals(['success'], $flash['new']);
        $this->assertEquals([], $flash['old']);

        $session->ageFlashData();

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals(['success'], $flash['old']);
        $this->assertEquals([], $flash['new']);
    }

    /**
     * Verifies that ageFlashData does not break when new is already empty.
     *
     * @return void
     */
    public function test_age_flash_data_with_empty_new(): void
    {
        $storage = new InMemorySessionStorage();
        $session = new Session($storage);

        $storage->set(Session::FLASH_KEY, ['old' => ['previous'], 'new' => []]);

        $session->ageFlashData();

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals([], $flash['old']);
        $this->assertEquals([], $flash['new']);
    }

    /**
     * Verifies that the destructor removes old flash keys and ages the data.
     *
     * @return void
     */
    public function test_destructor_removes_old_keys_and_ages_flash_data(): void
    {
        $storage = new InMemorySessionStorage();
        $session = new Session($storage);

        $storage->set(Session::FLASH_KEY, ['old' => ['expired_key'], 'new' => ['next_key']]);
        $storage->set('expired_key', 'should be removed');
        $storage->set('next_key', 'should survive');

        $session->__destruct();

        $this->assertFalse($storage->has('expired_key'), 'Old flash key should be removed on destruct');
        $this->assertTrue($storage->has('next_key'), 'New flash key should survive destruct');

        $flash = $storage->get(Session::FLASH_KEY);
        $this->assertEquals(['next_key'], $flash['old']);
        $this->assertEquals([], $flash['new']);
    }
}
