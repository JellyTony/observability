<?php

namespace JellyTony\Observability\Contracts;

interface MetadataStorageInterface
{
    public function add(string $key, string $value): void;

    public function set(string $key, string $value): void;

    public function get(string $key): string;

    public function getAll(): array;

    public function values(string $key): array;

    public function range(callable $f): void;

    public function merge(array $newMetadata): array;

    public function clear(): void;
}