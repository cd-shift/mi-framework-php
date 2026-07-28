<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Session\FileStorage;

class FileStorageTest extends TestCase
{
    private string $tempDir;
    private string $sessionId;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/mi-framework-test-' . uniqid();
        $this->sessionId = 'test-session-id';
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function test_set_and_get(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $storage->set('key1', 'value1');
        $this->assertSame('value1', $storage->get('key1'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $this->assertNull($storage->get('nonexistent'));
        $this->assertSame('default', $storage->get('nonexistent', 'default'));
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();
        $storage->set('key1', 'value1');

        $this->assertTrue($storage->has('key1'));
    }

    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $this->assertFalse($storage->has('nonexistent'));
    }

    public function test_remove_deletes_key(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();
        $storage->set('key1', 'value1');
        $storage->remove('key1');

        $this->assertFalse($storage->has('key1'));
    }

    public function test_save_persists_data_across_instances(): void
    {
        $storage1 = new FileStorage($this->sessionId, $this->tempDir);
        $storage1->start();
        $storage1->set('key1', 'value1');
        $storage1->set('key2', ['nested' => 'data']);
        $storage1->save();

        $storage2 = new FileStorage($this->sessionId, $this->tempDir);
        $storage2->start();

        $this->assertSame('value1', $storage2->get('key1'));
        $this->assertSame(['nested' => 'data'], $storage2->get('key2'));
    }

    public function test_destroy_deletes_file_and_clears_data(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();
        $storage->set('key1', 'value1');
        $storage->save();

        $filePath = $this->tempDir . '/sess_' . $this->sessionId . '.sess';
        $this->assertFileExists($filePath);

        $storage->destroy();

        $this->assertFileDoesNotExist($filePath);
        $this->assertFalse($storage->has('key1'));
    }

    public function test_destroy_on_unsaved_data(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();
        $storage->set('key1', 'value1');

        $storage->destroy();

        $this->assertFalse($storage->has('key1'));
    }

    public function test_start_creates_directory(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $this->assertDirectoryExists($this->tempDir);
    }

    public function test_generates_session_id_when_not_provided(): void
    {
        $storage = new FileStorage(null, $this->tempDir);

        $this->assertNotEmpty($storage->id());
    }

    public function test_generated_session_id_is_hex_string(): void
    {
        $storage = new FileStorage(null, $this->tempDir);

        $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $storage->id());
    }

    public function test_generated_session_id_is_40_characters(): void
    {
        $storage = new FileStorage(null, $this->tempDir);

        $this->assertSame(40, strlen($storage->id()));
    }

    public function test_save_does_not_write_if_not_dirty(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $filePath = $this->tempDir . '/sess_' . $this->sessionId . '.sess';

        $storage->save();

        $this->assertFileDoesNotExist($filePath);
    }

    public function test_save_writes_file_when_dirty(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();
        $storage->set('key1', 'value1');

        $storage->save();

        $filePath = $this->tempDir . '/sess_' . $this->sessionId . '.sess';
        $this->assertFileExists($filePath);
    }

    public function test_multiple_keys(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $storage->set('a', 1);
        $storage->set('b', 2);
        $storage->set('c', 3);

        $this->assertSame(1, $storage->get('a'));
        $this->assertSame(2, $storage->get('b'));
        $this->assertSame(3, $storage->get('c'));
    }

    public function test_preserves_null_values(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();
        $storage->set('null_key', null);

        $this->assertTrue($storage->has('null_key'));
        $this->assertNull($storage->get('null_key'));
    }

    public function test_preserves_all_data_types(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $data = [
            'string' => 'hello',
            'int' => 42,
            'float' => 3.14,
            'bool_true' => true,
            'bool_false' => false,
            'array' => [1, 2, 3],
            'assoc' => ['key' => 'value'],
            'null_val' => null,
        ];

        foreach ($data as $key => $value) {
            $storage->set($key, $value);
        }

        $storage->save();

        $storage2 = new FileStorage($this->sessionId, $this->tempDir);
        $storage2->start();

        foreach ($data as $key => $value) {
            $this->assertSame($value, $storage2->get($key), "Failed asserting key '{$key}' is preserved");
        }
    }

    public function test_start_loads_existing_data(): void
    {
        $storage1 = new FileStorage($this->sessionId, $this->tempDir);
        $storage1->start();
        $storage1->set('key1', 'value1');
        $storage1->save();

        $storage2 = new FileStorage($this->sessionId, $this->tempDir);
        $storage2->start();

        $this->assertTrue($storage2->has('key1'));
        $this->assertSame('value1', $storage2->get('key1'));
    }

    public function test_remove_then_save_persists_removal(): void
    {
        $storage1 = new FileStorage($this->sessionId, $this->tempDir);
        $storage1->start();
        $storage1->set('key1', 'value1');
        $storage1->set('key2', 'value2');
        $storage1->save();

        $storage2 = new FileStorage($this->sessionId, $this->tempDir);
        $storage2->start();
        $storage2->remove('key1');
        $storage2->save();

        $storage3 = new FileStorage($this->sessionId, $this->tempDir);
        $storage3->start();

        $this->assertFalse($storage3->has('key1'));
        $this->assertSame('value2', $storage3->get('key2'));
    }

    public function test_works_with_session_lifecycle(): void
    {
        $storage = new FileStorage($this->sessionId, $this->tempDir);
        $storage->start();

        $storage->set('counter', 0);
        $storage->save();

        for ($i = 1; $i <= 3; $i++) {
            $s = new FileStorage($this->sessionId, $this->tempDir);
            $s->start();
            $counter = $s->get('counter');
            $s->set('counter', $counter + 1);
            $s->save();
        }

        $final = new FileStorage($this->sessionId, $this->tempDir);
        $final->start();

        $this->assertSame(3, $final->get('counter'));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
