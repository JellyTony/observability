<?php

namespace JellyTony\Observability\Contracts;

interface LogRecord
{
    public function set($key, $value);

    public function has(string $key): bool;

    public function get(string $key);

    public function delete(string $key);

    public function clear(): void;

    public function all(): array;
}