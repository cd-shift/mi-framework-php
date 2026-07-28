<?php

declare(strict_types=1);

namespace Session;

use RuntimeException;

class FileStorage implements SessionStorage
{
    private string $sessionId;
    private string $directory;
    private array $data = [];
    private bool $loaded = false;
    private bool $dirty = false;

    public function __construct(?string $sessionId = null, ?string $directory = null)
    {
        $this->sessionId = $sessionId ?? bin2hex(random_bytes(20));
        $this->directory = $directory ?? sys_get_temp_dir() . '/mi-framework/sessions';
    }

    public function start()
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException("Failed to create session directory: {$this->directory}");
        }

        $this->load();
    }

    public function save()
    {
        if (!$this->dirty) {
            return;
        }

        $file = $this->path();
        $tempFile = $file . '.tmp';

        $written = file_put_contents($tempFile, $this->encode($this->data), LOCK_EX);

        if ($written === false) {
            throw new RuntimeException("Failed to write session file: {$tempFile}");
        }

        if (!rename($tempFile, $file)) {
            unlink($tempFile);

            throw new RuntimeException("Failed to rename session file: {$tempFile} -> {$file}");
        }

        $this->dirty = false;
    }

    public function id(): string
    {
        return $this->sessionId;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->load();
        $this->data[$key] = $value;
        $this->dirty = true;
    }

    public function has(string $key): bool
    {
        $this->load();

        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): void
    {
        $this->load();

        if (!array_key_exists($key, $this->data)) {
            return;
        }

        unset($this->data[$key]);
        $this->dirty = true;
    }

    public function destroy()
    {
        $file = $this->path();

        if (file_exists($file) && !unlink($file)) {
            throw new RuntimeException("Failed to delete session file: {$file}");
        }

        $this->data = [];
        $this->loaded = true;
        $this->dirty = false;
    }

    private function path(): string
    {
        return $this->directory . '/sess_' . $this->sessionId . '.sess';
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $file = $this->path();

        if (file_exists($file)) {
            $handle = fopen($file, 'r');

            if ($handle === false) {
                throw new RuntimeException("Failed to open session file: {$file}");
            }

            try {
                flock($handle, LOCK_SH);

                $contents = stream_get_contents($handle);

                if ($contents === false) {
                    throw new RuntimeException("Failed to read session file: {$file}");
                }

                $this->data = $this->decode($contents);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }

        $this->loaded = true;
    }

    private function encode(array $data): string
    {
        return serialize($data);
    }

    private function decode(string $contents): array
    {
        set_error_handler(function (int $errno, string $errstr): bool {
            throw new RuntimeException("Corrupted session data: {$errstr}");
        });

        try {
            $data = unserialize($contents, ['allowed_classes' => false]);
        } finally {
            restore_error_handler();
        }

        if (!is_array($data)) {
            throw new RuntimeException('Corrupted session data: expected array, got ' . gettype($data));
        }

        return $data;
    }
}
