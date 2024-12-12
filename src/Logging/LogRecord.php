<?php

namespace JellyTony\Observability\Logging;

use JellyTony\Observability\Contracts\LogRecord as API;

class LogRecord implements API
{
    private $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key)
    {
        unset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        return !empty($this->data[$key]);
    }

    public function get(string $key)
    {
        return $this->data[$key] ?? "";
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function all(): array
    {
        return $this->data;
    }
}